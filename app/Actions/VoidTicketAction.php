<?php

namespace App\Actions;

use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidTicketAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Booking $booking, Ticket $ticket, User $actor, string $reason, ?string $ipAddress = null): Ticket
    {
        return DB::transaction(function () use ($booking, $ticket, $actor, $reason, $ipAddress): Ticket {
            $ticket = Ticket::query()
                ->with('showtime')
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->findOrFail($ticket->id);

            if ($ticket->status !== TicketStatus::Valid) {
                throw ValidationException::withMessages([
                    'reason' => 'Only valid tickets can be voided.',
                ]);
            }

            if (blank($reason)) {
                throw ValidationException::withMessages([
                    'reason' => 'A reason is required to void a ticket.',
                ]);
            }

            if (! $ticket->showtime->starts_at->isFuture()) {
                throw ValidationException::withMessages([
                    'reason' => 'A ticket cannot be voided after its showtime has started.',
                ]);
            }

            $before = ['status' => $ticket->status->value];
            $ticket->forceFill([
                'status' => TicketStatus::Void,
                'voided_at' => now(),
            ])->save();

            $this->auditLogger->log(
                $actor,
                'ticket.voided',
                $ticket,
                $before,
                [
                    'status' => TicketStatus::Void->value,
                    'reason' => trim($reason),
                    'refund_issued' => false,
                ],
                $ipAddress,
            );

            return $ticket->fresh(['booking', 'showtime']);
        });
    }
}
