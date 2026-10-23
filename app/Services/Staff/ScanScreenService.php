<?php

namespace App\Services\Staff;

/**
 * Builds the data behind the staff scan screen from the "today's shows" overview.
 * Plain arrays in and out, so it works the same with mock or database data.
 */
class ScanScreenService
{
    public const ATTEMPT_LIMIT = 10;

    /**
     * @param  array{rows: list<array<string, mixed>>, nearest: array<string, mixed>|null}  $overview  from TodayShowsService::build()
     * @param  array<int, list<array<string, mixed>>>  $attempts  scan attempts keyed by show id
     * @return array{shows: list<array<string, mixed>>, selected: array<string, mixed>|null, attempts: list<array<string, mixed>>}
     */
    public function build(array $overview, ?int $showId, array $attempts): array
    {
        $shows = array_values(array_filter(
            $overview['rows'],
            fn (array $row): bool => $row['status'] !== EntryWindow::CLOSED,
        ));

        $selected = $this->select($shows, $overview['nearest'], $showId);

        return [
            'shows' => $shows,
            'selected' => $selected,
            'attempts' => $selected ? $this->recent($attempts[$selected['id']] ?? []) : [],
        ];
    }

    /**
     * The requested show if it can still be scanned, otherwise the nearest one.
     *
     * @param  list<array<string, mixed>>  $shows
     * @param  array<string, mixed>|null  $nearest
     * @return array<string, mixed>|null
     */
    private function select(array $shows, ?array $nearest, ?int $showId): ?array
    {
        foreach ($shows as $show) {
            if ($show['id'] === $showId) {
                return $show;
            }
        }

        return $nearest;
    }

    /**
     * @param  list<array<string, mixed>>  $attempts
     * @return list<array<string, mixed>>
     */
    private function recent(array $attempts): array
    {
        usort($attempts, fn (array $a, array $b): int => strcmp($b['time'], $a['time']));

        return array_slice($attempts, 0, self::ATTEMPT_LIMIT);
    }
}
