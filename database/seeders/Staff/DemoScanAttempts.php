<?php

namespace Database\Seeders\Staff;

use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Models\User;

/**
 * Adds a few rejected attempts to a show's scan history (already used, wrong showtime, invalid),
 * once. They sit after the show's last admission, a minute apart.
 */
class DemoScanAttempts
{
    public function __construct(private readonly User $staff) {}

    public function add(Showtime $show, ?Ticket $usedTicket, ?Ticket $otherShowTicket): void
    {
        if (TicketScan::query()->where('showtime_id', $show->id)->where('result', '!=', 'admitted')->exists()) {
            return;
        }

        $at = $show->starts_at->copy()->subMinutes(10);

        if ($usedTicket) {
            $this->record($show, $usedTicket->id, $usedTicket->token, 'already_used', 'This ticket was already used.', $at->copy());
        }

        if ($otherShowTicket) {
            $this->record($show, $otherShowTicket->id, $otherShowTicket->token, 'wrong_showtime', 'This ticket is for a different show.', $at->copy()->addMinute());
        }

        $this->record($show, null, 'not-a-ticket', 'invalid', 'Ticket not recognised.', $at->copy()->addMinutes(2));
    }

    private function record(Showtime $show, ?int $ticketId, string $token, string $result, string $message, \DateTimeInterface $at): void
    {
        TicketScan::forceCreate([
            'ticket_id' => $ticketId,
            'showtime_id' => $show->id,
            'token_hash' => hash('sha256', $token),
            'scanned_by' => $this->staff->id,
            'result' => $result,
            'message' => $message,
            'ip_address' => '127.0.0.1',
            'scanned_at' => $at,
        ]);
    }
}
