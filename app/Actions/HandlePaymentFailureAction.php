<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class HandlePaymentFailureAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Payment $payment, string $reason): Booking
    {
        return DB::transaction(function () use ($payment, $reason): Booking {
            $payment = Payment::query()->with('booking')->lockForUpdate()->findOrFail($payment->id);
            $booking = Booking::query()->lockForUpdate()->findOrFail($payment->booking_id);
            if ($booking->status !== BookingStatus::PendingPayment) {
                return $booking;
            }

            $beforePayment = $this->audit->snapshot($payment);
            $beforeBooking = $this->audit->snapshot($booking);
            $payment->forceFill(['status' => PaymentStatus::Failed, 'failure_reason' => mb_substr($reason, 0, 255)])->save();
            $booking->forceFill(['status' => BookingStatus::Failed])->save();
            foreach ($booking->seatHolds()->where('status', HoldStatus::Active->value)->get() as $hold) {
                $holdBefore = $this->audit->snapshot($hold);
                $hold->forceFill([
                    'status' => HoldStatus::Released,
                    'released_at' => now(),
                ])->save();
                $this->audit->log('seat_hold.released', $hold, $holdBefore, $this->audit->snapshot($hold));
            }
            foreach ($booking->items()->where('status', 'active')->get() as $item) {
                $itemBefore = $this->audit->snapshot($item);
                $item->forceFill(['status' => 'cancelled'])->save();
                $this->audit->log('booking_item.released', $item, $itemBefore, $this->audit->snapshot($item));
            }
            $this->audit->log('payment.failed', $payment, $beforePayment, $this->audit->snapshot($payment));
            $this->audit->log('booking.payment_failed', $booking, $beforeBooking, $this->audit->snapshot($booking));

            return $booking;
        });
    }
}
