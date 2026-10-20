<?php

use App\Services\Staff\BookingLookupService;

function lookupShows(): array
{
    return [[
        'id' => 4,
        'movie' => 'The Last Horizon',
        'screen' => 'Screen 1',
        'starts_at' => new DateTimeImmutable('2026-10-15 19:30'),
        'time' => '19:30',
        'window' => '18:30–20:00',
    ]];
}

function lookupBookings(): array
{
    return [[
        'ref' => 'CB-261015-0482',
        'name' => 'Alex Morgan',
        'show_id' => 4,
        'tickets' => [
            ['seat' => 'G7', 'seat_type' => 'Premium', 'code' => 'T-0482-01', 'used_at' => new DateTimeImmutable('2026-10-15 18:58')],
            ['seat' => 'G8', 'seat_type' => 'Premium', 'code' => 'T-0482-02', 'used_at' => null],
        ],
    ]];
}

test('an empty reference gives the idle state', function () {
    $data = (new BookingLookupService)->build('   ', lookupBookings(), lookupShows());

    expect($data)->toBe(['state' => 'idle', 'reference' => '', 'booking' => null]);
});

test('a known reference returns the booking with show details and totals', function () {
    $data = (new BookingLookupService)->build('CB-261015-0482', lookupBookings(), lookupShows());
    $booking = $data['booking'];

    expect($data['state'])->toBe('found')
        ->and($booking['name'])->toBe('Alex Morgan')
        ->and($booking['movie'])->toBe('The Last Horizon')
        ->and($booking['date'])->toBe('Oct 15, 2026')
        ->and($booking['window'])->toBe('18:30–20:00')
        ->and($booking['seats'])->toBe(2)
        ->and($booking['scanned'])->toBe(1);
});

test('each ticket shows its seat, code and admission status', function () {
    $tickets = (new BookingLookupService)->build('CB-261015-0482', lookupBookings(), lookupShows())['booking']['tickets'];

    expect($tickets[0])->toBe(['seat' => 'G7 · Premium', 'code' => 'T-0482-01', 'admission' => 'Used · Oct 15 at 18:58', 'used' => true])
        ->and($tickets[1])->toBe(['seat' => 'G8 · Premium', 'code' => 'T-0482-02', 'admission' => 'Not scanned', 'used' => false]);
});

test('the reference ignores case and spaces', function () {
    $data = (new BookingLookupService)->build("  cb-261015-\t0482 ", lookupBookings(), lookupShows());

    expect($data['state'])->toBe('found')->and($data['reference'])->toBe('CB-261015-0482');
});

test('an unknown reference is not found and keeps the normalised reference', function () {
    $data = (new BookingLookupService)->build('cb-000000-0000', lookupBookings(), lookupShows());

    expect($data)->toBe(['state' => 'not_found', 'reference' => 'CB-000000-0000', 'booking' => null]);
});

test('a booking whose show is not in today\'s list is not found', function () {
    $data = (new BookingLookupService)->build('CB-261015-0482', lookupBookings(), []);

    expect($data['state'])->toBe('not_found');
});

test('a very long reference is cut to the maximum length', function () {
    $data = (new BookingLookupService)->build(str_repeat('a', 100), [], []);

    expect(strlen($data['reference']))->toBe(BookingLookupService::MAX_REFERENCE_LENGTH);
});
