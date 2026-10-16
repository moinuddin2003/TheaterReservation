<?php

namespace App\Actions\Admin;

use App\Models\Seat;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteSeatAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Seat $seat): void
    {
        DB::transaction(function () use ($seat): void {
            $seat = Seat::query()->lockForUpdate()->findOrFail($seat->id);
            if ($seat->bookingItems()->exists() || $seat->holds()->exists()) {
                throw ValidationException::withMessages(['seat' => 'A seat with bookings or holds cannot be deleted.']);
            }

            $before = $this->audit->snapshot($seat);
            $seat->delete();
            $this->audit->log('seat.deleted', $seat, $before, null);
        });
    }
}
