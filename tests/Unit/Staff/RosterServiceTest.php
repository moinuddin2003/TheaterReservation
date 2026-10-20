<?php

use App\Services\Staff\RosterService;

function rosterBookings(int $count, int $showId = 1): array
{
    return array_map(fn (int $i): array => [
        'ref' => 'CB-'.$i,
        'name' => $i === 3 ? 'Maya Chen' : 'Guest '.$i,
        'show_id' => $showId,
        'seats' => ['A'.$i, 'B'.$i],
        'scanned' => $i % 3,
    ], range(1, $count));
}

function rosterOverview(): array
{
    $rows = [['id' => 1], ['id' => 2]];

    return ['rows' => $rows, 'nearest' => $rows[1]];
}

test('it defaults to the nearest show and totals admitted and seats', function () {
    $data = (new RosterService)->build(rosterOverview(), rosterBookings(3, 2), null, '', 1);

    expect($data['selected']['id'])->toBe(2)
        ->and($data['summary'])->toBe(['bookings' => 3, 'admitted' => 3, 'seats' => 6]);
});

test('it only counts bookings of the requested show', function () {
    $bookings = array_merge(rosterBookings(2, 1), rosterBookings(5, 2));

    $data = (new RosterService)->build(rosterOverview(), $bookings, 1, '', 1);

    expect($data['selected']['id'])->toBe(1)->and($data['total'])->toBe(2);
});

test('it paginates eight per page and clamps the page number', function () {
    $service = new RosterService;
    $bookings = rosterBookings(21);

    $first = $service->build(rosterOverview(), $bookings, 1, '', 1);
    $last = $service->build(rosterOverview(), $bookings, 1, '', 99);

    expect($first['rows'])->toHaveCount(8)
        ->and([$first['from'], $first['to'], $first['pages']])->toBe([1, 8, 3])
        ->and([$last['page'], $last['from'], $last['to']])->toBe([3, 17, 21]);
});

test('it formats rows with seats and scanned counts', function () {
    $row = (new RosterService)->build(rosterOverview(), rosterBookings(3, 2), null, '', 1)['rows'][2];

    expect($row)->toBe([
        'ref' => 'CB-3',
        'name' => 'Maya Chen',
        'seats' => 'A3, B3',
        'seat_count' => 2,
        'scanned' => '0 / 2',
    ]);
});

test('search matches name or reference ignoring case and keeps the whole-show summary', function () {
    $service = new RosterService;
    $bookings = rosterBookings(5, 2);

    $byName = $service->build(rosterOverview(), $bookings, 2, ' MAYA ', 1);
    $byRef = $service->build(rosterOverview(), $bookings, 2, 'cb-5', 1);
    $none = $service->build(rosterOverview(), $bookings, 2, 'nobody', 1);

    expect($byName['total'])->toBe(1)
        ->and($byName['summary']['bookings'])->toBe(5)
        ->and($byRef['rows'][0]['ref'])->toBe('CB-5')
        ->and([$none['total'], $none['from'], $none['to'], $none['pages']])->toBe([0, 0, 0, 1]);
});

test('with no shows there is nothing selected', function () {
    $data = (new RosterService)->build(['rows' => [], 'nearest' => null], [], null, '', 1);

    expect($data['selected'])->toBeNull()->and($data['rows'])->toBe([]);
});
