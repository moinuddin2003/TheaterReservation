<?php

namespace App\Services\Staff;

use App\Enums\ScanResult;
use DateTimeImmutable;

/**
 * Turns the facts of a rejected scan into the texts shown on the staff scan screen.
 * Pure formatting: it works the same with mock or database data.
 *
 * Expected keys in $result: status, seat, used_at, at, ticket_show, selected_show,
 * opens_at, closes_at (see MockTicketScanner::rejected()).
 */
class ScanRejectionView
{
    /**
     * @param  array<string, mixed>  $result
     * @return array{status: string, tone: string, label: string, headline: string, detail: string, context: string}
     */
    public function make(array $result): array
    {
        $status = ScanResult::from($result['status']);
        $at = new DateTimeImmutable($result['at']);
        $show = $result['ticket_show'];
        $selected = $result['selected_show'];
        $now = 'Current time '.$at->format('H:i');

        [$label, $headline, $detail, $context] = match ($status) {
            ScanResult::AlreadyUsed => [
                'Already used',
                $this->ticketLine($result),
                "This ticket was admitted today at {$result['used_at']}. A ticket can only be used once. There is no reset or undo.",
                "{$now} · Entry window {$this->window($result)}",
            ],
            ScanResult::WrongShowtime => [
                'Wrong showtime',
                "This ticket is for {$this->showLine($show)}.",
                "You’re scanning {$this->showLine($selected)}. Choose the matching showtime before scanning again.",
                "{$now} · Selected show {$this->time($selected['starts_at'])}",
            ],
            ScanResult::TooEarly => [
                'Too early',
                "Entry opens today at {$this->time($result['opens_at'])}.",
                "{$this->showLine($show, true)}. Come back when the entry window opens, {$this->minutes($result['opens_at'], $show['starts_at'])} before the show.",
                "{$now} · Opens in {$this->minutes($at, $result['opens_at'])}",
            ],
            ScanResult::Expired => [
                'Expired',
                "Entry closed today at {$this->time($result['closes_at'])}.",
                "{$this->showLine($show, true)}. This ticket is more than {$this->minutes($show['starts_at'], $result['closes_at'])} past the show start and can no longer be admitted.",
                "{$now} · Closed {$this->minutes($result['closes_at'], $at)} ago",
            ],
            ScanResult::Void => [
                'Void · Do not admit',
                'This ticket was cancelled or refunded.',
                "{$this->ticketLine($result)}. The booking is no longer valid. A refund state never makes a void ticket valid again.",
                "{$now} · Entry window {$this->window($result)}",
            ],
            default => [
                'Invalid',
                'We couldn’t recognize this ticket.',
                'No valid ticket matches this token. Try scanning once more, type the token carefully or use the booking reference lookup.',
                $selected ? "{$now} · Selected show {$this->time($selected['starts_at'])}" : $now,
            ],
        };

        return [
            'status' => $status->value,
            'tone' => $status === ScanResult::TooEarly ? 'warning' : 'danger',
            'label' => $label,
            'headline' => $headline,
            'detail' => $detail,
            'context' => $context,
        ];
    }

    /**
     * "G7 · The Last Horizon · Screen 1"
     *
     * @param  array<string, mixed>  $result
     */
    private function ticketLine(array $result): string
    {
        return $result['seat'].' · '.$result['ticket_show']['movie'].' · '.$result['ticket_show']['screen'];
    }

    /**
     * "Night Circuit · 18:00 · Screen 2 IMAX", or with $screenFirst "Night Circuit · Screen 2 IMAX · 18:00".
     *
     * @param  array{movie: string, screen: string, starts_at: string}  $show
     */
    private function showLine(array $show, bool $screenFirst = false): string
    {
        $time = $this->time($show['starts_at']);

        return $screenFirst
            ? "{$show['movie']} · {$show['screen']} · {$time}"
            : "{$show['movie']} · {$time} · {$show['screen']}";
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function window(array $result): string
    {
        return $this->time($result['opens_at']).'–'.$this->time($result['closes_at']);
    }

    private function time(DateTimeImmutable|string $moment): string
    {
        return $this->moment($moment)->format('H:i');
    }

    /**
     * Whole minutes from one moment to the other, as "20 minutes".
     */
    private function minutes(DateTimeImmutable|string $from, DateTimeImmutable|string $to): string
    {
        $seconds = $this->moment($to)->getTimestamp() - $this->moment($from)->getTimestamp();
        $count = (int) abs(round($seconds / 60));

        return $count === 1 ? '1 minute' : "{$count} minutes";
    }

    private function moment(DateTimeImmutable|string $moment): DateTimeImmutable
    {
        return $moment instanceof DateTimeImmutable ? $moment : new DateTimeImmutable($moment);
    }
}
