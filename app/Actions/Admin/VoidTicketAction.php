<?php

namespace App\Actions\Admin;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidTicketAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Ticket $ticket): Ticket
    {
        return DB::transaction(function () use ($ticket): Ticket {
            $locked = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            if ($locked->status !== TicketStatus::Valid) {
                throw ValidationException::withMessages(['ticket' => 'Only valid tickets can be voided.']);
            }
            $before = $this->audit->snapshot($locked);
            $locked->forceFill(['status' => TicketStatus::Voided, 'voided_at' => now()])->save();
            $this->audit->log('ticket.voided', $locked, $before, $this->audit->snapshot($locked));

            return $locked;
        });
    }
}
