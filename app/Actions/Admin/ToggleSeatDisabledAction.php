<?php

namespace App\Actions\Admin;

use App\Models\Seat;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ToggleSeatDisabledAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Seat $seat): Seat
    {
        return DB::transaction(function () use ($seat): Seat {
            $seat = Seat::query()->lockForUpdate()->findOrFail($seat->id);
            if (! $seat->is_disabled && ($seat->bookingItems()->where('status', 'active')->exists() || $seat->holds()->where('status', 'active')->exists())) {
                throw ValidationException::withMessages(['seat' => 'A seat with an active booking or hold cannot be disabled.']);
            }
            $before = $this->audit->snapshot($seat);
            $seat->forceFill(['is_disabled' => ! $seat->is_disabled])->save();
            $this->audit->log('seat.disabled_status_updated', $seat, $before, $this->audit->snapshot($seat));

            return $seat->refresh();
        });
    }
}
