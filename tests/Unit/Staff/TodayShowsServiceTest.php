<?php

use App\Services\Staff\TodayShowsService;

function staffSampleShows(): array
{
    return [
        ['id' => 1, 'movie' => 'Early Show', 'screen' => 'Screen 1', 'starts_at' => '2026-10-15 14:30', 'sold' => 38, 'scanned' => 38],
        ['id' => 2, 'movie' => 'Current Show', 'screen' => 'Screen 1', 'starts_at' => '2026-10-15 19:30', 'sold' => 45, 'scanned' => 18],
        ['id' => 3, 'movie' => 'Late Show', 'screen' => 'Screen 2', 'starts_at' => '2026-10-15 22:00', 'sold' => 28, 'scanned' => 0],
    ];
}

function staffOverviewAt(string $time, ?array $shows = null): array
{
    return (new TodayShowsService(60, 30))->build($shows ?? staffSampleShows(), new DateTimeImmutable("2026-10-15 {$time}"));
}

test('summary totals the shows, tickets expected and guests admitted', function () {
    expect(staffOverviewAt('18:55')['summary'])->toBe(['scheduled' => 3, 'expected' => 111, 'admitted' => 56]);
});

test('each show gets its entry window, status and action', function () {
    $rows = staffOverviewAt('18:55')['rows'];

    expect(array_column($rows, 'status'))->toBe(['closed', 'open', 'not_open'])
        ->and(array_column($rows, 'action'))->toBe(['roster', 'scan', 'scan'])
        ->and($rows[1]['window'])->toBe('18:30–20:00')
        ->and($rows[1]['time'])->toBe('19:30');
});

test('the nearest show is the one currently open', function () {
    expect(staffOverviewAt('18:55')['nearest']['movie'])->toBe('Current Show');
});

test('the nearest show is the next one to open when none is open yet', function () {
    $nearest = staffOverviewAt('12:00')['nearest'];

    expect($nearest['movie'])->toBe('Early Show')
        ->and($nearest['status'])->toBe('not_open');
});

test('there is no nearest show once every entry window has closed', function () {
    expect(staffOverviewAt('23:00')['nearest'])->toBeNull();
});

test('rows are ordered by start time whatever order they arrive in', function () {
    $rows = staffOverviewAt('18:55', array_reverse(staffSampleShows()))['rows'];

    expect(array_column($rows, 'time'))->toBe(['14:30', '19:30', '22:00']);
});
