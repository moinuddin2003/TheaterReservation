<?php

use App\Models\Cinema;
use App\Models\TicketScan;
use App\Models\User;
use App\Services\Staff\EloquentStaffData;
use Tests\Support\StaffDbFixture;

function dbDay(): DateTimeImmutable
{
    return new DateTimeImmutable('2026-10-15 18:55');
}

test('shows lists the day\'s scheduled shows with sold and scanned counts', function () {
    $show = StaffDbFixture::show('2026-10-15 19:30', 'The Last Horizon', 'Screen 1');
    StaffDbFixture::booking($show, 'CB-261015-0482', 'Alex Morgan', ['G7' => 'premium', 'G8' => 'premium'], used: ['G7']);
    StaffDbFixture::show('2026-10-15 14:30', 'Night Circuit', 'Screen 2 IMAX');

    $shows = (new EloquentStaffData)->shows(dbDay());

    expect($shows)->toHaveCount(2)
        ->and($shows[0])->toBe(['id' => $shows[0]['id'], 'movie' => 'Night Circuit', 'screen' => 'Screen 2 IMAX', 'starts_at' => '2026-10-15 14:30', 'sold' => 0, 'scanned' => 0])
        ->and($shows[1])->toBe(['id' => $show->id, 'movie' => 'The Last Horizon', 'screen' => 'Screen 1', 'starts_at' => '2026-10-15 19:30', 'sold' => 2, 'scanned' => 1]);
});

test('shows leaves out other days and cancelled shows', function () {
    StaffDbFixture::show('2026-10-16 19:30');
    StaffDbFixture::show('2026-10-15 20:00')->forceFill(['status' => 'cancelled'])->save();

    expect((new EloquentStaffData)->shows(dbDay()))->toBe([]);
});

test('void tickets are not counted as sold', function () {
    $show = StaffDbFixture::show();
    StaffDbFixture::booking($show, 'CB-261015-0001', 'A', ['A1' => 'standard', 'A2' => 'standard']);
    $show->tickets()->where('ticket_number', 'T-0001-02')->update(['status' => 'void']);

    expect((new EloquentStaffData)->shows(dbDay())[0]['sold'])->toBe(1);
});

test('the roster lists confirmed bookings of the day with seats and scanned count', function () {
    $show = StaffDbFixture::show();
    StaffDbFixture::booking($show, 'CB-261015-0482', 'Alex Morgan', ['G7' => 'premium', 'G8' => 'premium'], used: ['G7']);
    StaffDbFixture::booking($show, 'CB-261015-0490', 'Pending Pat', ['A1' => 'standard'], status: 'pending_payment');
    StaffDbFixture::booking(StaffDbFixture::show('2026-10-16 19:30'), 'CB-261016-0001', 'Next Day', ['A1' => 'standard']);

    expect((new EloquentStaffData)->rosterBookings(dbDay()))->toBe([
        ['ref' => 'CB-261015-0482', 'name' => 'Alex Morgan', 'show_id' => $show->id, 'seats' => ['G7', 'G8'], 'scanned' => 1],
    ]);
});

test('recent attempts are grouped by show, newest first, with seat, ticket and first use time', function () {
    $show = StaffDbFixture::show();
    $booking = StaffDbFixture::booking($show, 'CB-261015-0482', 'Alex Morgan', ['G7' => 'premium'], used: ['G7']);
    $ticket = $booking->tickets()->first();
    $staff = User::factory()->staff()->create();

    $scan = fn (string $result, ?int $ticketId, string $at) => TicketScan::create([
        'ticket_id' => $ticketId, 'showtime_id' => $show->id, 'token_hash' => str_repeat('a', 64),
        'scanned_by' => $staff->id, 'result' => $result, 'scanned_at' => $at,
    ]);
    $scan('admitted', $ticket->id, '2026-10-15 18:48:00');
    $scan('already_used', $ticket->id, '2026-10-15 18:54:42');
    $scan('invalid', null, '2026-10-15 18:55:10');

    $attempts = (new EloquentStaffData)->recentAttempts(dbDay())[$show->id];

    expect($attempts)->toBe([
        ['time' => '18:55:10', 'result' => 'invalid', 'seat' => null, 'ticket' => null, 'used_at' => null],
        ['time' => '18:54:42', 'result' => 'already_used', 'seat' => 'G7', 'ticket' => 'T-0482-01', 'used_at' => '18:48'],
        ['time' => '18:48:00', 'result' => 'admitted', 'seat' => 'G7', 'ticket' => 'T-0482-01', 'used_at' => null],
    ]);
});

test('recent attempts keep only the latest ten per show', function () {
    $show = StaffDbFixture::show();
    $staff = User::factory()->staff()->create();

    foreach (range(1, 12) as $second) {
        TicketScan::create(['showtime_id' => $show->id, 'token_hash' => str_repeat('a', 64), 'scanned_by' => $staff->id, 'result' => 'invalid', 'scanned_at' => sprintf('2026-10-15 18:50:%02d', $second)]);
    }

    $attempts = (new EloquentStaffData)->recentAttempts(dbDay())[$show->id];

    expect($attempts)->toHaveCount(10)->and($attempts[0]['time'])->toBe('18:50:12');
});

test('findBooking returns a confirmed booking with its show and tickets, whatever the day', function () {
    $show = StaffDbFixture::show('2026-10-20 19:30');
    StaffDbFixture::booking($show, 'CB-261020-0001', 'Alex Morgan', ['G7' => 'premium', 'C8' => 'standard'], used: ['G7']);

    $booking = (new EloquentStaffData)->findBooking('CB-261020-0001');

    expect($booking['name'])->toBe('Alex Morgan')
        ->and($booking['show'])->toBe(['id' => $show->id, 'movie' => 'The Last Horizon', 'screen' => 'Screen 1', 'starts_at' => '2026-10-20 19:30'])
        ->and($booking['tickets'][0])->toMatchArray(['seat' => 'G7', 'seat_type' => 'Premium', 'code' => 'T-0001-01'])
        ->and($booking['tickets'][0]['used_at']->format('H:i'))->toBe('18:48')
        ->and($booking['tickets'][1])->toMatchArray(['seat' => 'C8', 'seat_type' => 'Standard', 'used_at' => null]);
});

test('findBooking ignores unknown and unconfirmed references', function () {
    StaffDbFixture::booking(StaffDbFixture::show(), 'CB-261015-0499', 'Pending Pat', ['A1' => 'standard'], status: 'pending_payment');

    expect((new EloquentStaffData)->findBooking('CB-261015-0499'))->toBeNull()
        ->and((new EloquentStaffData)->findBooking('CB-000000-0000'))->toBeNull();
});

test('the venue is the cinema name', function () {
    Cinema::factory()->create(['name' => 'CineBook Downtown']);

    expect((new EloquentStaffData)->venue())->toBe('CineBook Downtown');
});
