<?php

use App\Services\Staff\BookingLookupService;

function lookupService(): BookingLookupService
{
    return new BookingLookupService(60, 30);
}

function lookupBooking(): array
{
    return [
        'ref' => 'CB-261015-0482',
        'name' => 'Alex Morgan',
        'show' => ['id' => 4, 'movie' => 'The Last Horizon', 'screen' => 'Screen 1', 'starts_at' => '2026-10-15 19:30'],
        'tickets' => [
            ['seat' => 'G7', 'seat_type' => 'Premium', 'code' => 'T-0482-01', 'used_at' => new DateTimeImmutable('2026-10-15 18:58')],
            ['seat' => 'G8', 'seat_type' => 'Premium', 'code' => 'T-0482-02', 'used_at' => null],
        ],
    ];
}

test('an empty reference gives the idle state', function () {
    expect(lookupService()->build('', null))->toBe(['state' => 'idle', 'reference' => '', 'booking' => null]);
});

test('a booking is formatted with show details, entry window and totals', function () {
    $data = lookupService()->build('CB-261015-0482', lookupBooking());
    $booking = $data['booking'];

    expect($data['state'])->toBe('found')
        ->and($booking['name'])->toBe('Alex Morgan')
        ->and($booking['show_id'])->toBe(4)
        ->and($booking['movie'])->toBe('The Last Horizon')
        ->and($booking['date'])->toBe('Oct 15, 2026')
        ->and($booking['time'])->toBe('19:30')
        ->and($booking['window'])->toBe('18:30–20:00')
        ->and($booking['seats'])->toBe(2)
        ->and($booking['scanned'])->toBe(1);
});

test('each ticket shows its seat, code and admission status', function () {
    $tickets = lookupService()->build('CB-261015-0482', lookupBooking())['booking']['tickets'];

    expect($tickets[0])->toBe(['seat' => 'G7 · Premium', 'code' => 'T-0482-01', 'admission' => 'Used · Oct 15 at 18:58', 'used' => true])
        ->and($tickets[1])->toBe(['seat' => 'G8 · Premium', 'code' => 'T-0482-02', 'admission' => 'Not scanned', 'used' => false]);
});

test('no booking means not found and keeps the reference', function () {
    expect(lookupService()->build('CB-000000-0000', null))->toBe(['state' => 'not_found', 'reference' => 'CB-000000-0000', 'booking' => null]);
});

test('the reference ignores case and spaces', function () {
    expect(lookupService()->normalize("  cb-261015-\t0482 "))->toBe('CB-261015-0482');
});

test('a very long reference is cut to the maximum length', function () {
    expect(strlen(lookupService()->normalize(str_repeat('a', 100))))->toBe(BookingLookupService::MAX_REFERENCE_LENGTH);
});

test('a booking for another day is formatted like any other', function () {
    $booking = lookupBooking();
    $booking['show']['starts_at'] = '2026-10-16 10:00';

    $view = lookupService()->build('CB-261015-0482', $booking)['booking'];

    expect($view['date'])->toBe('Oct 16, 2026')->and($view['window'])->toBe('09:00–10:30');
});
