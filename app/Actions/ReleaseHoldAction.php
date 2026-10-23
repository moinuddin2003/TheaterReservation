<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class ReleaseHoldAction
{
    public function execute(Booking $booking, BookingStatus $bookingStatus): void
    {
        DB::transaction(function () use ($booking, $bookingStatus): void {
            $booking = Booking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();

            if (! $booking->isPendingPayment()) {
                return;
            }

            $booking->seatHolds()
                ->where('status', HoldStatus::Active)
                ->update([
                    'status' => $bookingStatus === BookingStatus::Expired
                        ? HoldStatus::Expired->value
                        : HoldStatus::Released->value,
                    'released_at' => now(),
                ]);

            $paymentStatus = $bookingStatus === BookingStatus::Expired
                ? PaymentStatus::Expired
                : PaymentStatus::Failed;

            $booking->payments()
                ->where('status', PaymentStatus::Pending)
                ->update(['status' => $paymentStatus->value]);

            $booking->forceFill(['status' => $bookingStatus])->save();
        });
    }
}
