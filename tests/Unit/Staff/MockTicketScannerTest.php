<?php

use App\Services\Staff\MockTicketScanner;

function staffMockTickets(): array
{
    return ['DEMO-TOKEN' => ['show_id' => 4, 'seat' => 'G7', 'seat_type' => 'Premium', 'code' => 'T-0482-01']];
}

function staffMockShows(): array
{
    return [['id' => 4, 'movie' => 'The Last Horizon', 'screen' => 'Screen 1']];
}

function staffMockScan(string $token): ?array
{
    return (new MockTicketScanner())->scan($token, staffMockTickets(), staffMockShows(), new DateTimeImmutable('2026-10-15 18:58:00'));
}

test('a known token is admitted with its show, seat and time', function () {
    expect(staffMockScan('DEMO-TOKEN'))->toMatchArray([
        'status' => 'admitted',
        'show_id' => 4,
        'movie' => 'The Last Horizon',
        'screen' => 'Screen 1',
        'seat' => 'G7',
        'seat_label' => 'Premium seat',
        'ticket' => 'T-0482-01',
        'admitted_at' => '18:58',
        'time' => '18:58:00',
    ]);
});

test('surrounding whitespace is ignored when scanning', function () {
    expect(staffMockScan("  DEMO-TOKEN\n"))->not->toBeNull();
});

test('an unknown token is not admitted', function () {
    expect(staffMockScan('nope'))->toBeNull();
});

test('an admission adds one scanned guest and the newest attempt', function () {
    $screen = [
        'shows' => [],
        'selected' => ['id' => 4, 'scanned' => 18, 'sold' => 45],
        'attempts' => [['time' => '18:54:58', 'result' => 'admitted', 'seat' => 'F6', 'ticket' => 'T-0478-02']],
    ];

    $updated = (new MockTicketScanner())->applyTo($screen, staffMockScan('DEMO-TOKEN'));

    expect($updated['selected']['scanned'])->toBe(19)
        ->and($updated['attempts'][0])->toMatchArray(['time' => '18:58:00', 'seat' => 'G7', 'ticket' => 'T-0482-01'])
        ->and($updated['attempts'])->toHaveCount(2);
});

test('an admission keeps the recent attempts at 10', function () {
    $attempts = array_fill(0, 10, ['time' => '18:50:00', 'result' => 'admitted', 'seat' => 'A1', 'ticket' => 'T-1']);
    $screen = ['shows' => [], 'selected' => ['id' => 4, 'scanned' => 18, 'sold' => 45], 'attempts' => $attempts];

    $updated = (new MockTicketScanner())->applyTo($screen, staffMockScan('DEMO-TOKEN'));

    expect($updated['attempts'])->toHaveCount(10)
        ->and($updated['attempts'][0]['time'])->toBe('18:58:00');
});

test('an admission for another show leaves the screen untouched', function () {
    $screen = ['shows' => [], 'selected' => ['id' => 5, 'scanned' => 0, 'sold' => 41], 'attempts' => []];

    expect((new MockTicketScanner())->applyTo($screen, staffMockScan('DEMO-TOKEN')))->toBe($screen);
});
