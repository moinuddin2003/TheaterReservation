<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Models\Booking;
use App\Models\SeatHold;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class ExpireHoldsAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(): int
    {
        $bookingIds = SeatHold::query()
            ->where('status', HoldStatus::Active->value)
            ->where('expires_at', '<=', now())
            ->whereNotNull('booking_id')
            ->distinct()
            ->pluck('booking_id');
        $expiredCount = 0;

        foreach ($bookingIds as $bookingId) {
            $expiredCount += DB::transaction(function () use ($bookingId): int {
                $booking = Booking::query()->lockForUpdate()->find($bookingId);
                $holds = SeatHold::query()
                    ->where('booking_id', $bookingId)
                    ->where('status', HoldStatus::Active->value)
                    ->where('expires_at', '<=', now())
                    ->lockForUpdate()
                    ->get();

                foreach ($holds as $hold) {
                    $before = $this->audit->snapshot($hold);
                    $hold->forceFill([
                        'status' => HoldStatus::Expired,
                        'released_at' => now(),
                    ])->save();
                    $this->audit->log('seat_hold.expired', $hold, $before, $this->audit->snapshot($hold));
                }

                if ($booking && $booking->status === BookingStatus::PendingPayment) {
                    $before = $this->audit->snapshot($booking);
                    $booking->forceFill(['status' => BookingStatus::Expired])->save();
                    foreach ($booking->items()->where('status', 'active')->get() as $item) {
                        $itemBefore = $this->audit->snapshot($item);
                        $item->forceFill(['status' => 'cancelled'])->save();
                        $this->audit->log('booking_item.released', $item, $itemBefore, $this->audit->snapshot($item));
                    }
                    $this->audit->log('booking.expired', $booking, $before, $this->audit->snapshot($booking));
                }

                return $holds->count();
            });
        }

        return $expiredCount;
    }
}
