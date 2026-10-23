<?php

namespace App\Services\Staff;

use App\Contracts\Staff\TicketScanner;
use App\Enums\BookingStatus;
use App\Enums\ScanResult;
use App\Enums\TicketStatus;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\TicketScan;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Scans tickets against the database: decides with ScanRules, marks a ticket
 * used (one atomic UPDATE, so two simultaneous scans cannot both succeed) and
 * saves every attempt in ticket_scans.
 *
 * A stand-in until ScanTicketAction is ready; when it is, wrap it in a class
 * implementing TicketScanner and bind that in StaffServiceProvider instead.
 */
class EloquentTicketScanner implements TicketScanner
{
    public function __construct(
        private readonly ScanRules $rules,
        private readonly ScanRejectionView $rejections,
    ) {
    }

    public function scan(string $token, ?int $selectedShowId, int $staffUserId, ?string $ip): array
    {
        $token = trim($token);
        $at = now()->toDateTimeImmutable();
        $ticket = $token === '' ? null : Ticket::with(['booking', 'bookingItem', 'showtime.movie', 'showtime.screen'])->where('token', $token)->first();
        $selected = $selectedShowId ? $this->showFacts(Showtime::with(['movie', 'screen'])->find($selectedShowId)) : null;

        return DB::transaction(function () use ($ticket, $selected, $at, $token, $staffUserId, $ip): array {
            $result = $this->rules->evaluate($ticket ? $this->describe($ticket) : null, $selected, $at);

            if ($result['status'] === ScanResult::Admitted->value && ! $this->claim($ticket, $staffUserId, $at)) {
                // Another scan won the race a moment ago.
                $result = $this->rules->evaluate($this->describe($ticket->refresh()), $selected, $at);
            }

            $this->record($result, $ticket, $token, $staffUserId, $ip);

            return $result;
        });
    }

    /** The screen data is already read from ticket_scans, so there is nothing to add. */
    public function applyTo(array $screen, array $result): array
    {
        return $screen;
    }

    /**
     * Marks the ticket used only if it is still valid. False when someone else got there first.
     */
    private function claim(Ticket $ticket, int $staffUserId, DateTimeImmutable $at): bool
    {
        return Ticket::query()
            ->whereKey($ticket->id)
            ->where('status', TicketStatus::Valid->value)
            ->update(['status' => TicketStatus::Used->value, 'used_at' => $at, 'used_by' => $staffUserId]) === 1;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function record(array $result, ?Ticket $ticket, string $token, int $staffUserId, ?string $ip): void
    {
        $admitted = $result['status'] === ScanResult::Admitted->value;

        TicketScan::query()->create([
            'ticket_id' => $ticket?->id,
            'showtime_id' => $result['show_id'],
            // Only a hash of the token is kept, never the token itself.
            'token_hash' => hash('sha256', $token),
            'scanned_by' => $staffUserId,
            'result' => $result['status'],
            'message' => $admitted ? null : $this->rejections->make($result)['headline'],
            'ip_address' => $ip,
            'scanned_at' => $result['at'],
        ]);
    }

    /**
     * The ticket as ScanRules expects it. A ticket whose booking is no longer confirmed counts as void.
     *
     * @return array<string, mixed>
     */
    private function describe(Ticket $ticket): array
    {
        $void = $ticket->status === TicketStatus::Void || $ticket->booking->status !== BookingStatus::Confirmed;

        return [
            'state' => $void ? 'void' : $ticket->status->value,
            'seat' => $ticket->bookingItem->seat_label,
            'seat_type' => ucfirst($ticket->bookingItem->seat_type->value),
            'number' => $ticket->ticket_number,
            'used_at' => $ticket->used_at?->format('H:i'),
            'show' => $this->showFacts($ticket->showtime),
        ];
    }

    /**
     * @return array{id: int, movie: string, screen: string, starts_at: string}|null
     */
    private function showFacts(?Showtime $show): ?array
    {
        return $show ? [
            'id' => $show->id,
            'movie' => $show->movie->title,
            'screen' => $show->screen->name,
            'starts_at' => $show->starts_at->format('Y-m-d H:i'),
        ] : null;
    }
}
