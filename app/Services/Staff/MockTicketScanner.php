<?php

namespace App\Services\Staff;

use DateTimeImmutable;

/**
 * Stand-in for the real ticket scanning while the staff screens use mock data.
 * Delete it, together with its use in ScanController, once ScanTicketAction is wired in.
 */
class MockTicketScanner
{
    /**
     * @param  array<string, array<string, mixed>>  $tickets  mock tickets keyed by token
     * @param  list<array<string, mixed>>  $shows  mock shows (id, movie, screen, ...)
     * @return array<string, mixed>|null the admission, or null when the token is not a known mock ticket
     */
    public function scan(string $token, array $tickets, array $shows, DateTimeImmutable $at): ?array
    {
        $ticket = $tickets[trim($token)] ?? null;
        $show = $ticket ? $this->findShow($shows, $ticket['show_id']) : null;

        if (! $ticket || ! $show) {
            return null;
        }

        return [
            'status' => 'admitted',
            'show_id' => $show['id'],
            'movie' => $show['movie'],
            'screen' => $show['screen'],
            'seat' => $ticket['seat'],
            'seat_label' => $ticket['seat_type'].' seat',
            'ticket' => $ticket['code'],
            'admitted_at' => $at->format('H:i'),
            'time' => $at->format('H:i:s'),
        ];
    }

    /**
     * Reflects an admission on the scan screen data: one more guest scanned
     * and the new attempt at the top of the recent list.
     *
     * @param  array{shows: list<array<string, mixed>>, selected: array<string, mixed>|null, attempts: list<array<string, mixed>>}  $screen
     * @param  array<string, mixed>  $result
     * @return array{shows: list<array<string, mixed>>, selected: array<string, mixed>|null, attempts: list<array<string, mixed>>}
     */
    public function applyTo(array $screen, array $result): array
    {
        if (($screen['selected']['id'] ?? null) !== $result['show_id']) {
            return $screen;
        }

        $screen['selected']['scanned']++;

        array_unshift($screen['attempts'], [
            'time' => $result['time'],
            'result' => $result['status'],
            'seat' => $result['seat'],
            'ticket' => $result['ticket'],
        ]);

        $screen['attempts'] = array_slice($screen['attempts'], 0, ScanScreenService::ATTEMPT_LIMIT);

        return $screen;
    }

    /**
     * @param  list<array<string, mixed>>  $shows
     * @return array<string, mixed>|null
     */
    private function findShow(array $shows, int $id): ?array
    {
        foreach ($shows as $show) {
            if ($show['id'] === $id) {
                return $show;
            }
        }

        return null;
    }
}
