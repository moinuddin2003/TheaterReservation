<?php

namespace App\Actions\Admin;

use App\Enums\BookingStatus;
use App\Enums\ShowtimeStatus;
use App\Events\ShowtimeCancelled;
use App\Models\Showtime;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelShowtimeAction
{
    public function __construct(
        private AuditLogger $audit,
        private CancelBookingAction $cancelBooking,
    ) {}

    public function execute(Showtime $showtime, string $reason, ?User $actor = null): Showtime
    {
        return DB::transaction(function () use ($showtime, $reason, $actor): Showtime {
            $locked = Showtime::query()->lockForUpdate()->findOrFail($showtime->id);
            if ($locked->status !== ShowtimeStatus::Scheduled) {
                throw ValidationException::withMessages(['showtime' => 'Only scheduled showtimes can be cancelled.']);
            }
            if ($locked->starts_at->isPast()) {
                throw ValidationException::withMessages(['showtime' => 'A showtime that has started cannot be cancelled.']);
            }
            $before = $this->audit->snapshot($locked);
            $locked->forceFill([
                'status' => ShowtimeStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor?->id ?? auth()->id(),
                'cancel_reason' => mb_substr($reason, 0, 255),
            ])->save();
            $this->audit->log('showtime.cancelled', $locked, $before, $this->audit->snapshot($locked));

            $bookings = $locked->bookings()
                ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::PendingPayment->value])
                ->lockForUpdate()
                ->get();
            foreach ($bookings as $booking) {
                $this->cancelBooking->execute($booking, 'showtime_cancelled: '.$reason, true, $actor);
            }
            ShowtimeCancelled::dispatch($locked->fresh(['bookings.user', 'movie']));

            return $locked->refresh();
        });
    }
}
