<?php

namespace App\Services\Staff;

use DateTimeImmutable;

/**
 * Builds the data behind the staff "Today's shows" screen.
 *
 * It only works on plain arrays, so the data source (mock config now,
 * database later) can change without touching this class.
 */
class TodayShowsService
{
    public function __construct(
        private readonly int $entryMinutesBefore,
        private readonly int $entryMinutesAfter,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            config('cinebook.scan_window_before_minutes'),
            config('cinebook.scan_window_after_minutes'),
        );
    }

    /**
     * @param  list<array{id: int, movie: string, screen: string, starts_at: string, sold: int, scanned: int}>  $shows
     * @return array{
     *     summary: array{scheduled: int, expected: int, admitted: int},
     *     nearest: array<string, mixed>|null,
     *     rows: list<array<string, mixed>>
     * }
     */
    public function build(array $shows, DateTimeImmutable $now): array
    {
        $rows = array_map(fn (array $show): array => $this->toRow($show, $now), $shows);

        usort($rows, fn (array $a, array $b): int => $a['starts_at'] <=> $b['starts_at']);

        return [
            'summary' => [
                'scheduled' => count($rows),
                'expected' => array_sum(array_column($rows, 'sold')),
                'admitted' => array_sum(array_column($rows, 'scanned')),
            ],
            'nearest' => $this->nearest($rows),
            'rows' => $rows,
        ];
    }

    /**
     * @param  array{id: int, movie: string, screen: string, starts_at: string, sold: int, scanned: int}  $show
     * @return array<string, mixed>
     */
    private function toRow(array $show, DateTimeImmutable $now): array
    {
        $startsAt = new DateTimeImmutable($show['starts_at']);
        $window = EntryWindow::forShowStart($startsAt, $this->entryMinutesBefore, $this->entryMinutesAfter);
        $status = $window->statusAt($now);

        return [
            'id' => $show['id'],
            'movie' => $show['movie'],
            'screen' => $show['screen'],
            'starts_at' => $startsAt,
            'time' => $startsAt->format('H:i'),
            'sold' => $show['sold'],
            'scanned' => $show['scanned'],
            'window' => $window->label(),
            'opens_at' => $window->opensAt->format('H:i'),
            'closes_at' => $window->closesAt->format('H:i'),
            'status' => $status,
            'action' => $status === EntryWindow::CLOSED ? 'roster' : 'scan',
        ];
    }

    /**
     * The show being admitted now, otherwise the next one to open.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>|null
     */
    private function nearest(array $rows): ?array
    {
        foreach ([EntryWindow::OPEN, EntryWindow::NOT_OPEN] as $status) {
            foreach ($rows as $row) {
                if ($row['status'] === $status) {
                    return $row;
                }
            }
        }

        return null;
    }
}
