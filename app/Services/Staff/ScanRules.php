<?php

namespace App\Services\Staff;

use App\Enums\ScanResult;
use DateTimeImmutable;

/**
 * The scan rules, shared by the mock and the database scanner.
 * Checks run in this order: unknown token, void, already used,
 * wrong showtime, too early, expired.
 *
 * A ticket is described as:
 *   state (valid|used|void), seat, seat_type (display text), number,
 *   used_at (H:i or null), show {id, movie, screen, starts_at}
 */
class ScanRules
{
    public function __construct(
        private readonly int $minutesBefore,
        private readonly int $minutesAfter,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            config('cinebook.scan_window_before_minutes'),
            config('cinebook.scan_window_after_minutes'),
        );
    }

    /**
     * @param  array<string, mixed>|null  $ticket  null for an unknown token
     * @param  array{id: int, movie: string, screen: string, starts_at: string}|null  $selected  defaults to the ticket's show
     * @return array<string, mixed> the result facts (see ScanRejectionView for the rejection ones)
     */
    public function evaluate(?array $ticket, ?array $selected, DateTimeImmutable $at): array
    {
        $selected ??= $ticket['show'] ?? null;
        $window = $ticket ? $this->window($ticket['show']['starts_at']) : null;
        $status = $ticket ? $this->check($ticket, $selected, $window, $at) : ScanResult::Invalid;

        return $status === ScanResult::Admitted
            ? $this->admitted($ticket, $at)
            : $this->rejected($status, $ticket, $selected, $window, $at);
    }

    private function window(string $startsAt): EntryWindow
    {
        return EntryWindow::forShowStart(new DateTimeImmutable($startsAt), $this->minutesBefore, $this->minutesAfter);
    }

    /**
     * @param  array<string, mixed>  $ticket
     * @param  array<string, mixed>|null  $selected
     */
    private function check(array $ticket, ?array $selected, EntryWindow $window, DateTimeImmutable $at): ScanResult
    {
        return match (true) {
            in_array($ticket['state'], ['void', 'voided'], true) => ScanResult::Void,
            $ticket['state'] === 'used' => ScanResult::AlreadyUsed,
            $selected !== null && $selected['id'] !== $ticket['show']['id'] => ScanResult::WrongShowtime,
            $window->statusAt($at) === EntryWindow::NOT_OPEN => ScanResult::TooEarly,
            $window->statusAt($at) === EntryWindow::CLOSED => ScanResult::Expired,
            default => ScanResult::Admitted,
        };
    }

    /**
     * @param  array<string, mixed>  $ticket
     * @return array<string, mixed>
     */
    private function admitted(array $ticket, DateTimeImmutable $at): array
    {
        return [
            'status' => ScanResult::Admitted->value,
            'show_id' => $ticket['show']['id'],
            'movie' => $ticket['show']['movie'],
            'screen' => $ticket['show']['screen'],
            'seat' => $ticket['seat'],
            'seat_label' => $ticket['seat_type'].' seat',
            'ticket' => $ticket['number'],
            'admitted_at' => $at->format('H:i'),
            'time' => $at->format('H:i:s'),
            'at' => $at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Plain values only, so the result can be flashed to the session.
     *
     * @param  array<string, mixed>|null  $ticket
     * @param  array<string, mixed>|null  $selected
     * @return array<string, mixed>
     */
    private function rejected(ScanResult $status, ?array $ticket, ?array $selected, ?EntryWindow $window, DateTimeImmutable $at): array
    {
        return [
            'status' => $status->value,
            'show_id' => $selected['id'] ?? $ticket['show']['id'] ?? null,
            'seat' => $ticket['seat'] ?? null,
            'ticket' => $ticket['number'] ?? null,
            'used_at' => $ticket['used_at'] ?? null,
            'time' => $at->format('H:i:s'),
            'at' => $at->format('Y-m-d H:i:s'),
            'ticket_show' => $ticket ? $this->showFacts($ticket['show']) : null,
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
}
