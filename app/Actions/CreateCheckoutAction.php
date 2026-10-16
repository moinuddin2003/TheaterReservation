<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\AuditLogger;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateCheckoutAction
{
    public function __construct(
        private PaymentGateway $gateway,
        private AuditLogger $audit,
    ) {}

    /** @return array{booking: Booking, checkout_url: string} */
    public function execute(Booking $booking, string $successUrl, string $cancelUrl): array
    {
        $payment = DB::transaction(function () use ($booking): Payment {
            $locked = Booking::query()->with('seatHolds')->lockForUpdate()->findOrFail($booking->id);
            if ($locked->status !== BookingStatus::PendingPayment || $locked->hold_expires_at->isPast()) {
                throw ValidationException::withMessages(['booking' => 'This seat hold has expired or is no longer payable.']);
            }
            $hasActiveHolds = $locked->seatHolds()
                ->where('status', HoldStatus::Active->value)
                ->where('expires_at', '>', now())
                ->count() === (int) $locked->seats_count;
            if (! $hasActiveHolds) {
                throw ValidationException::withMessages(['booking' => 'All seats must remain actively held during checkout.']);
            }
            $existing = $locked->payments()->where('status', PaymentStatus::Pending->value)->latest('id')->first();
            if ($existing) {
                return $existing;
            }
            $payment = $locked->payments()->create([
                'gateway' => 'stripe',
                'idempotency_key' => 'checkout-'.Str::uuid(),
                'amount_cents' => $locked->total_cents,
                'currency' => 'USD',
                'status' => PaymentStatus::Pending,
            ]);
            $this->audit->log('payment.created', $payment, null, $this->audit->snapshot($payment));

            return $payment;
        });

        $booking->refresh()->load(['user', 'showtime.movie', 'items']);
        $result = $this->gateway->createCheckoutSession($booking, $successUrl, $cancelUrl);
        if (! $result->successful || ! $result->id || ! $result->url) {
            DB::transaction(function () use ($payment, $result): void {
                $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                $before = $this->audit->snapshot($payment);
                $payment->forceFill([
                    'status' => PaymentStatus::Failed,
                    'failure_reason' => mb_substr($result->error ?? 'Stripe checkout session was incomplete.', 0, 255),
                ])->save();
                $this->audit->log('payment.failed', $payment, $before, $this->audit->snapshot($payment));
            });
            throw new \RuntimeException($result->error ?? 'Stripe did not return a checkout session URL.');
        }

        DB::transaction(function () use ($payment, $result): void {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $before = $this->audit->snapshot($locked);
            $locked->forceFill(['gateway_session_id' => $result->id])->save();
            $this->audit->log('payment.checkout_session_created', $locked, $before, $this->audit->snapshot($locked));
        });

        return ['booking' => $booking->refresh(), 'checkout_url' => $result->url];
    }
}
