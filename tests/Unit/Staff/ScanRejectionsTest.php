<?php

use App\Services\Staff\MockTicketScanner;
use App\Services\Staff\ScanRules;

function rejectionShows(): array
{
    return [
        ['id' => 3, 'movie' => 'Night Circuit', 'screen' => 'Screen 2 IMAX', 'starts_at' => '2026-10-15 18:00'],
        ['id' => 4, 'movie' => 'The Last Horizon', 'screen' => 'Screen 1', 'starts_at' => '2026-10-15 19:30'],
    ];
}

function rejectionTicket(array $extra = []): array
{
    return ['T' => array_merge(['show_id' => 4, 'seat' => 'G7', 'seat_type' => 'Premium', 'code' => 'T-0482-01'], $extra)];
}

function rejectionScanner(array $tickets, string $at = '18:55'): MockTicketScanner
{
    return new MockTicketScanner(new ScanRules(60, 30), $tickets, rejectionShows(), new DateTimeImmutable("2026-10-15 {$at}"));
}

function rejectionScan(array $ticket, string $at = '18:55', ?int $show = 4, string $token = 'T'): array
{
    return rejectionScanner($ticket, $at)->scan($token, $show, 1, null);
}

test('a ticket inside its entry window for the selected show is admitted', function () {
    expect(rejectionScan(rejectionTicket())['status'])->toBe('admitted');
});

test('an unknown token is invalid and has no ticket details', function () {
    $result = rejectionScan(rejectionTicket(), token: 'nope');

    expect($result)->toMatchArray(['status' => 'invalid', 'show_id' => 4, 'seat' => null, 'ticket_show' => null])
        ->and($result['selected_show']['movie'])->toBe('The Last Horizon');
});

test('a used ticket is rejected as already used and keeps when it was used', function () {
    $result = rejectionScan(rejectionTicket(['state' => 'used', 'used_at' => '18:58']), '19:02');

    expect($result)->toMatchArray(['status' => 'already_used', 'seat' => 'G7', 'used_at' => '18:58']);
});

test('a void ticket is rejected as void', function () {
    expect(rejectionScan(rejectionTicket(['state' => 'void']))['status'])->toBe('void');
});

test('a legacy voided ticket is rejected as void', function () {
    expect(rejectionScan(rejectionTicket(['state' => 'voided']))['status'])->toBe('void');
});

test('a ticket for another show is rejected as the wrong showtime', function () {
    $result = rejectionScan(rejectionTicket(['show_id' => 3]), '18:55', 4);

    expect($result['status'])->toBe('wrong_showtime')
        ->and($result['show_id'])->toBe(4)
        ->and($result['ticket_show']['movie'])->toBe('Night Circuit');
});

test('a ticket scanned before the entry window opens is too early', function () {
    $result = rejectionScan(rejectionTicket(), '18:10');

    expect($result['status'])->toBe('too_early')
        ->and($result['opens_at'])->toBe('2026-10-15 18:30:00');
});

test('a ticket scanned after the entry window closes is expired', function () {
    $result = rejectionScan(rejectionTicket(), '20:05');

    expect($result['status'])->toBe('expired')
        ->and($result['closes_at'])->toBe('2026-10-15 20:00:00');
});

test('the entry window edges still admit', function (string $at) {
    expect(rejectionScan(rejectionTicket(), $at)['status'])->toBe('admitted');
})->with(['18:30', '20:00']);

test('a void ticket stays void even for another show or outside the window', function () {
    expect(rejectionScan(rejectionTicket(['state' => 'void', 'show_id' => 3]), '23:00')['status'])->toBe('void');
});

test('a used ticket for another show says already used', function () {
    expect(rejectionScan(rejectionTicket(['state' => 'used', 'show_id' => 3]))['status'])->toBe('already_used');
});

test('without a selected show the ticket is checked against its own show', function () {
    expect(rejectionScan(rejectionTicket(['show_id' => 3]), '18:10', null)['status'])->toBe('admitted');
});

test('a scan can fix its own time', function () {
    $result = rejectionScan(rejectionTicket(['scan_at' => '2026-10-15 18:10']), '18:55');

    expect($result['status'])->toBe('too_early')->and($result['at'])->toBe('2026-10-15 18:10:00');
});

test('a rejection is listed as an attempt but does not count a scanned guest', function () {
    $screen = ['shows' => [], 'selected' => ['id' => 4, 'scanned' => 18, 'sold' => 45], 'attempts' => []];
    $result = rejectionScan(rejectionTicket(['state' => 'used', 'used_at' => '18:48']), '19:02');

    $updated = rejectionScanner([])->applyTo($screen, $result);

    expect($updated['selected']['scanned'])->toBe(18)
        ->and($updated['attempts'][0])->toMatchArray(['result' => 'already_used', 'seat' => 'G7', 'ticket' => 'T-0482-01', 'used_at' => '18:48']);
});
