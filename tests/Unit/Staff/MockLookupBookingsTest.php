<?php

use App\Services\Staff\MockLookupBookings;

function mockLookup(): MockLookupBookings
{
    return new MockLookupBookings(['G', 'H'], ['CB-1' => ['2026-10-15 18:58']], '2026-10-15 18:45');
}

function mockRosterBooking(int $scanned): array
{
    return ['ref' => 'CB-261015-0482', 'name' => 'Alex Morgan', 'show_id' => 4, 'seats' => ['G7', 'C8'], 'scanned' => $scanned];
}

test('it builds one ticket per seat with a code and seat type', function () {
    $tickets = mockLookup()->toLookupBookings([mockRosterBooking(0)])[0]['tickets'];

    expect($tickets)->toHaveCount(2)
        ->and($tickets[0]['code'])->toBe('T-0482-01')
        ->and($tickets[0]['seat_type'])->toBe('Premium')
        ->and($tickets[1]['code'])->toBe('T-0482-02')
        ->and($tickets[1]['seat_type'])->toBe('Standard');
});

test('only the first scanned seats are marked as used', function () {
    $tickets = mockLookup()->toLookupBookings([mockRosterBooking(1)])[0]['tickets'];

    expect($tickets[0]['used_at'])->toBeInstanceOf(DateTimeImmutable::class)
        ->and($tickets[0]['used_at']->format('H:i'))->toBe('18:45')
        ->and($tickets[1]['used_at'])->toBeNull();
});

test('a listed scan time wins over the default', function () {
    $booking = ['ref' => 'CB-1', 'name' => 'A', 'show_id' => 1, 'seats' => ['A1'], 'scanned' => 1];

    $tickets = mockLookup()->toLookupBookings([$booking])[0]['tickets'];

    expect($tickets[0]['used_at']->format('H:i'))->toBe('18:58');
});
