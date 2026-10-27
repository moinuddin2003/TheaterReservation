<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\BookingCancellationService;
use App\Services\Payments\StripeGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class HandleLatePaymentAction
{
    public function __construct(
        private StripeGateway $stripeGateway,
        private BookingCancellationService $cancellationService,
    ) {}

    public function execute(Payment $payment, string $paymentIntentId): ?Refund
    {
        $refund = DB::transaction(function () use ($payment, $paymentIntentId): Refund {
            $payment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            $booking = Booking::query()->whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();

            if (! $payment->isRefunded()) {
                $payment->forceFill([
                    'gateway_payment_intent_id' => $paymentIntentId,
                    'status' => PaymentStatus::Succeeded,
                    'paid_at' => $payment->paid_at ?? now(),
                ])->save();
            }

            if ($booking->isPendingPayment()) {
                $booking->seatHolds()
                    ->where('status', HoldStatus::Active)
                    ->update([
                        'status' => HoldStatus::Expired->value,
                        'released_at' => now(),
                    ]);
                $booking->forceFill([
                    'status' => BookingStatus::Expired,
                    'cancelled_at' => now(),
                    'cancel_reason' => 'late_payment',
                ])->save();
            }

            $refund = $booking->refunds()
                ->where('payment_id', $payment->getKey())
                ->where('reason', 'late_payment')
                ->latest()
                ->lockForUpdate()
                ->first();

            if ($refund) {
                return $refund;
            }

            $refund = new Refund([
                'payment_id' => $payment->getKey(),
                'currency' => $payment->currency,
                'reason' => 'late_payment',
                'idempotency_key' => 'late-payment-'.$payment->getKey(),
            ]);
            $refund->forceFill(['amount_cents' => $payment->amount_cents]);
            $booking->refunds()->save($refund);

            return $refund;
        });

        if ($refund->gateway_refund_id) {
            return null;
        }

        $payment = Payment::query()->findOrFail($refund->payment_id);
        $stripeRefund = $this->stripeGateway->createRefund(
            $payment->gateway_payment_intent_id,
            $refund->amount_cents,
            $refund->idempotency_key,
        );

        $refund->forceFill(['gateway_refund_id' => $stripeRefund->id])->save();

        if (! $this->cancellationService->updateStripeRefund($stripeRefund->id, $stripeRefund->status)) {
            throw new RuntimeException('The late-payment refund could not be recorded.');
        }

        if (in_array($stripeRefund->status, ['failed', 'canceled'], true)) {
            Log::error('Stripe could not refund a payment received after its seat hold expired.', [
                'booking_id' => $payment->booking_id,
                'payment_id' => $payment->getKey(),
                'stripe_refund_id' => $stripeRefund->id,
            ]);
        }

        return $refund->refresh();
    }
}
