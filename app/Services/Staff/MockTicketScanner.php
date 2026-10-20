<?php

namespace App\Services\Staff;

use App\Enums\ScanResult;
use DateTimeImmutable;

/**
 * Stand-in for the real ticket scanning while the staff screens use mock data.
 * Delete it, together with its use in ScanController, once ScanTicketAction is wired in.
 */
class MockTicketScanner
{
    public function __construct(
        private readonly int $minutesBefore,
        private readonly int $minutesAfter,
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
     * Decides what happens when a token is scanned: an admission, or one of the
     * rejections. Checks run in this order: unknown token, void, already used,
     * wrong showtime, too early, expired.
     *
     * @param  array<string, array<string, mixed>>  $tickets  mock tickets keyed by token
     * @param  list<array<string, mixed>>  $shows  mock shows (id, movie, screen, starts_at, ...)
     * @param  int|null  $selectedShowId  the show the staff member is scanning for
     * @return array<string, mixed> the result; its "status" is a ScanResult value
     */
    public function scan(string $token, array $tickets, array $shows, DateTimeImmutable $at, ?int $selectedShowId = null): array
    {
        $ticket = $tickets[trim($token)] ?? null;
        $ticketShow = $ticket ? $this->findShow($shows, $ticket['show_id']) : null;
        $selected = $this->findShow($shows, $selectedShowId ?? $ticket['show_id'] ?? 0);

        // A mock ticket can fix the time it is scanned at, to demo early and late entries.
        $at = isset($ticket['scan_at']) ? new DateTimeImmutable($ticket['scan_at']) : $at;

        if (! $ticket || ! $ticketShow) {
            return $this->rejected(ScanResult::Invalid, null, null, $selected, $at);
        }

        $window = EntryWindow::forShowStart(
            new DateTimeImmutable($ticketShow['starts_at']),
            $this->minutesBefore,
            $this->minutesAfter,
        );

        $rejection = match (true) {
            ($ticket['state'] ?? null) === 'void' => ScanResult::Void,
            ($ticket['state'] ?? null) === 'used' => ScanResult::AlreadyUsed,
            $selected !== null && $selected['id'] !== $ticketShow['id'] => ScanResult::WrongShowtime,
            $window->statusAt($at) === EntryWindow::NOT_OPEN => ScanResult::TooEarly,
            $window->statusAt($at) === EntryWindow::CLOSED => ScanResult::Expired,
            default => null,
        };

        return $rejection
            ? $this->rejected($rejection, $ticket, $ticketShow, $selected, $at, $window)
            : $this->admitted($ticket, $ticketShow, $at);
    }

    /**
     * @param  array<string, mixed>  $ticket
     * @param  array<string, mixed>  $show
     * @return array<string, mixed>
     */
    private function admitted(array $ticket, array $show, DateTimeImmutable $at): array
    {
        return [
            'status' => ScanResult::Admitted->value,
            'show_id' => $show['id'],
            'movie' => $show['movie'],
            'screen' => $show['screen'],
            'seat' => $ticket['seat'],
            'seat_label' => $ticket['seat_type'].' seat',
            'ticket' => $ticket['code'],
            'admitted_at' => $at->format('H:i'),
            'time' => $at->format('H:i:s'),
            'at' => $at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * The facts ScanRejectionView needs to explain a rejection. Dates are
     * plain strings so the result can be flashed to the session.
     *
     * @param  array<string, mixed>|null  $ticket
     * @param  array<string, mixed>|null  $ticketShow
     * @param  array<string, mixed>|null  $selected
     * @return array<string, mixed>
     */
    private function rejected(ScanResult $status, ?array $ticket, ?array $ticketShow, ?array $selected, DateTimeImmutable $at, ?EntryWindow $window = null): array
    {
        return [
            'status' => $status->value,
            'show_id' => $selected['id'] ?? $ticketShow['id'] ?? null,
            'seat' => $ticket['seat'] ?? null,
            'ticket' => $ticket['code'] ?? null,
            'used_at' => $ticket['used_at'] ?? null,
            'time' => $at->format('H:i:s'),
            'at' => $at->format('Y-m-d H:i:s'),
            'ticket_show' => $ticketShow ? $this->showFacts($ticketShow) : null,
            'selected_show' => $selected ? $this->showFacts($selected) : null,
            'opens_at' => $window?->opensAt->format('Y-m-d H:i:s'),
            'closes_at' => $window?->closesAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param  array<string, mixed>  $show
     * @return array{movie: string, screen: string, starts_at: string}
     */
    private function showFacts(array $show): array
    {
        return ['movie' => $show['movie'], 'screen' => $show['screen'], 'starts_at' => $show['starts_at']];
    }

    /**
     * Reflects a scan on the scan screen data: the new attempt at the top of
     * the recent list, and one more guest scanned when it was an admission.
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

        // Only an admission counts as a scanned guest; every attempt is listed.
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
