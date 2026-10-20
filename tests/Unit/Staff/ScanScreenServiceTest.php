<?php

use App\Services\Staff\ScanScreenService;
use App\Services\Staff\TodayShowsService;

function staffScanScreenAt(string $time, ?int $showId = null, array $attempts = []): array
{
    $shows = [
        ['id' => 1, 'movie' => 'Early Show', 'screen' => 'Screen 1', 'starts_at' => '2026-10-15 14:30', 'sold' => 38, 'scanned' => 38],
        ['id' => 2, 'movie' => 'Current Show', 'screen' => 'Screen 1', 'starts_at' => '2026-10-15 19:30', 'sold' => 45, 'scanned' => 18],
        ['id' => 3, 'movie' => 'Late Show', 'screen' => 'Screen 2', 'starts_at' => '2026-10-15 22:00', 'sold' => 28, 'scanned' => 0],
    ];

    $overview = (new TodayShowsService(60, 30))->build($shows, new DateTimeImmutable("2026-10-15 {$time}"));

    return (new ScanScreenService())->build($overview, $showId, $attempts);
}

test('only shows whose entry window has not closed can be scanned', function () {
    expect(array_column(staffScanScreenAt('18:55')['shows'], 'id'))->toBe([2, 3]);
});

test('the nearest show is selected by default', function () {
    expect(staffScanScreenAt('18:55')['selected']['id'])->toBe(2);
});

test('a requested show is selected when it can still be scanned', function () {
    expect(staffScanScreenAt('18:55', 3)['selected']['id'])->toBe(3);
});

test('a closed or unknown show falls back to the nearest one', function () {
    expect(staffScanScreenAt('18:55', 1)['selected']['id'])->toBe(2)
        ->and(staffScanScreenAt('18:55', 99)['selected']['id'])->toBe(2);
});

test('attempts are newest first and limited to 10', function () {
    $attempts = [];
    foreach (range(0, 11) as $second) {
        $attempts[] = ['time' => sprintf('18:50:%02d', $second), 'result' => 'admitted'];
    }

    $recent = staffScanScreenAt('18:55', null, [2 => $attempts])['attempts'];

    expect($recent)->toHaveCount(10)
        ->and($recent[0]['time'])->toBe('18:50:11')
        ->and($recent[9]['time'])->toBe('18:50:02');
});

test('there is nothing to select once every show has closed', function () {
    $screen = staffScanScreenAt('23:00');

    expect($screen['shows'])->toBe([])
        ->and($screen['selected'])->toBeNull()
        ->and($screen['attempts'])->toBe([]);
});
