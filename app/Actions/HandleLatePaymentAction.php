<?php

namespace App\Actions;

use App\Actions\Admin\IssueRefundAction;
use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShowtimeStatus;
use App\Jobs\ProcessRefundJob;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\SeatHold;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class HandleLatePaymentAction
{
    public function __construct(
        private ConfirmBookingAction $confirmBooking,
        private IssueRefundAction $issueRefund,
        private AuditLogger $audit,
    ) {}

    public function execute(
        Payment $payment,
        ?string $paymentIntentId = null,
        ?string $checkoutSessionId = null,
    ): Booking {
        return DB::transaction(function () use ($payment, $paymentIntentId, $checkoutSessionId): Booking {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $booking = Booking::query()->with('showtime')->lockForUpdate()->findOrFail($payment->booking_id);

            if ($booking->status === BookingStatus::Confirmed && $payment->status === PaymentStatus::Succeeded) {
                return $booking;
            }

            $activeHolds = SeatHold::query()
                ->where('booking_id', $booking->id)
                ->where('status', HoldStatus::Active->value)
                ->where('expires_at', '>', now())
                ->count();
            $canConfirm = $booking->status === BookingStatus::PendingPayment
                && $booking->hold_expires_at->isFuture()
                && $activeHolds === (int) $booking->seats_count
                && $booking->showtime->status === ShowtimeStatus::Scheduled
                && $booking->showtime->starts_at->greaterThan(now()->addMinutes(15))
                && (int) $payment->amount_cents === (int) $booking->total_cents;

            if ($canConfirm) {
                return $this->confirmBooking->execute($booking, $paymentIntentId, $checkoutSessionId);
            }

            $paymentBefore = $this->audit->snapshot($payment);
            $payment->forceFill([
                'status' => PaymentStatus::Succeeded,
                'gateway_payment_intent_id' => $paymentIntentId ?? $payment->gateway_payment_intent_id,
                'gateway_session_id' => $checkoutSessionId ?? $payment->gateway_session_id,
                'paid_at' => $payment->paid_at ?? now(),
            ])->save();
            $this->audit->log('payment.succeeded_late', $payment, $paymentBefore, $this->audit->snapshot($payment));

            if ($booking->status === BookingStatus::PendingPayment) {
                $bookingBefore = $this->audit->snapshot($booking);
                $booking->forceFill(['status' => BookingStatus::Expired])->save();
                foreach ($booking->items()->where('status', 'active')->get() as $item) {
                    $itemBefore = $this->audit->snapshot($item);
                    $item->forceFill(['status' => 'cancelled'])->save();
                    $this->audit->log('booking_item.released', $item, $itemBefore, $this->audit->snapshot($item));
                }
                foreach ($booking->seatHolds()->where('status', HoldStatus::Active->value)->get() as $hold) {
                    $holdBefore = $this->audit->snapshot($hold);
                    $hold->forceFill([
                        'status' => HoldStatus::Released,
                        'released_at' => now(),
                    ])->save();
                    $this->audit->log('seat_hold.released', $hold, $holdBefore, $this->audit->snapshot($hold));
                }
                $this->audit->log('booking.expired_after_payment', $booking, $bookingBefore, $this->audit->snapshot($booking));
            }

            $reservedRefundCents = (int) Refund::query()
                ->where('payment_id', $payment->id)
                ->whereIn('status', ['pending', 'processing', 'succeeded'])
                ->sum('amount_cents');
            $remainingCents = max(0, (int) $payment->amount_cents - $reservedRefundCents);
            if ($remainingCents > 0) {
                $refund = $this->issueRefund->execute($booking, $remainingCents, 'late_payment');
                DB::afterCommit(fn () => ProcessRefundJob::dispatch($refund->id));
            }

            return $booking->refresh();
        });
    }
}
