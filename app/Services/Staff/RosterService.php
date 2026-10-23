<?php

namespace App\Services\Staff;

/**
 * Builds the data behind the staff "Show roster" screen: the selected show,
 * its bookings (searchable, paginated) and the admitted totals.
 * Plain arrays in and out, so it works the same with mock or database data.
 */
class RosterService
{
    public const PAGE_SIZE = 8;

    /**
     * @param  array{rows: list<array<string, mixed>>, nearest: array<string, mixed>|null}  $overview  from TodayShowsService::build()
     * @param  list<array{ref: string, name: string, show_id: int, seats: list<string>, scanned: int}>  $bookings
     * @return array<string, mixed>
     */
    public function build(array $overview, array $bookings, ?int $showId, string $query, int $page): array
    {
        $selected = $this->select($overview, $showId);
        $showBookings = $selected
            ? array_values(array_filter($bookings, fn (array $b): bool => $b['show_id'] === $selected['id']))
            : [];

        $matches = $this->search($showBookings, $query);
        $total = count($matches);
        $pages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $rows = array_slice($matches, $offset, self::PAGE_SIZE);

        return [
            'shows' => $overview['rows'],
            'selected' => $selected,
            'summary' => [
                'bookings' => count($showBookings),
                'admitted' => array_sum(array_column($showBookings, 'scanned')),
                'seats' => array_sum(array_map(fn (array $b): int => count($b['seats']), $showBookings)),
            ],
            'rows' => array_map($this->toRow(...), $rows),
            'query' => $query,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => $offset + count($rows),
        ];
    }

    /**
     * The requested show, otherwise the nearest one, otherwise the first of the day.
     *
     * @param  array{rows: list<array<string, mixed>>, nearest: array<string, mixed>|null}  $overview
     * @return array<string, mixed>|null
     */
    private function select(array $overview, ?int $showId): ?array
    {
        foreach ($overview['rows'] as $row) {
            if ($row['id'] === $showId) {
                return $row;
            }
        }

        return $overview['nearest'] ?? ($overview['rows'][0] ?? null);
    }

    /**
     * Matches the booking reference or the customer name (case-insensitive).
     *
     * @param  list<array<string, mixed>>  $bookings
     * @return list<array<string, mixed>>
     */
    private function search(array $bookings, string $query): array
    {
        $needle = mb_strtolower(trim($query));

        if ($needle === '') {
            return $bookings;
        }

        return array_values(array_filter(
            $bookings,
            fn (array $b): bool => str_contains(mb_strtolower($b['ref'].' '.$b['name']), $needle),
        ));
    }

    /**
     * @param  array{ref: string, name: string, seats: list<string>, scanned: int}  $booking
     * @return array{ref: string, name: string, seats: string, seat_count: int, scanned: string}
     */
    private function toRow(array $booking): array
    {
        $count = count($booking['seats']);

        return [
            'ref' => $booking['ref'],
            'name' => $booking['name'],
            'seats' => implode(', ', $booking['seats']),
            'seat_count' => $count,
            'scanned' => $booking['scanned'].' / '.$count,
        ];
    }
}
