<?php

namespace App\Services\Staff;

use App\Contracts\Staff\TicketScanner;
use App\Enums\ScanResult;
use DateTimeImmutable;

/**
 * Stand-in for the real ticket scanning while the staff screens use mock data.
 * Delete it, together with the staff-mock tickets, once the mock-based tests are moved to the database.
 */
class MockTicketScanner implements TicketScanner
{
    /**
     * @param  array<string, array<string, mixed>>  $tickets  mock tickets keyed by token
     * @param  list<array<string, mixed>>  $shows  mock shows (id, movie, screen, starts_at, ...)
     */
    public function __construct(
        private readonly ScanRules $rules,
        private readonly array $tickets,
        private readonly array $shows,
        private readonly DateTimeImmutable $scanAt,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            ScanRules::fromConfig(),
            config('staff-mock.tickets'),
            config('staff-mock.shows'),
            new DateTimeImmutable(config('staff-mock.scan_at')),
        );
    }

    public function scan(string $token, ?int $selectedShowId, int $staffUserId, ?string $ip): array
    {
        $mock = $this->tickets[trim($token)] ?? null;
        $show = $mock ? $this->findShow($mock['show_id']) : null;
        $ticket = $mock && $show ? $this->ticket($mock, $show) : null;

        // A mock ticket can fix the time it is scanned at, to demo early and late entries.
        $at = isset($mock['scan_at']) ? new DateTimeImmutable($mock['scan_at']) : $this->scanAt;

        return $this->rules->evaluate($ticket, $this->findShow($selectedShowId ?? 0), $at);
    }

    /**
     * Records the scan on the screen data: the new attempt at the top of the
     * recent list, and one more guest scanned when it was an admission.
     */
    public function applyTo(array $screen, array $result): array
    {
        if (($screen['selected']['id'] ?? null) !== $result['show_id']) {
            return $screen;
        }

        if ($result['status'] === ScanResult::Admitted->value) {
            $screen['selected']['scanned']++;
        }

        array_unshift($screen['attempts'], [
            'time' => $result['time'],
            'result' => $result['status'],
            'seat' => $result['seat'],
            'ticket' => $result['ticket'],
            'used_at' => $result['used_at'] ?? null,
        ]);

        $screen['attempts'] = array_slice($screen['attempts'], 0, ScanScreenService::ATTEMPT_LIMIT);

        return $screen;
    }

    /**
     * @param  array<string, mixed>  $mock
     * @param  array<string, mixed>  $show
     * @return array<string, mixed>
     */
    private function ticket(array $mock, array $show): array
    {
        return [
            'state' => $mock['state'] ?? 'valid',
            'seat' => $mock['seat'],
            'seat_type' => $mock['seat_type'],
            'number' => $mock['code'],
            'used_at' => $mock['used_at'] ?? null,
            'show' => ['id' => $show['id'], 'movie' => $show['movie'], 'screen' => $show['screen'], 'starts_at' => $show['starts_at']],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findShow(int $id): ?array
    {
        foreach ($this->shows as $show) {
            if ($show['id'] === $id) {
                return $show;
            }
        }

        return null;
    }
}
