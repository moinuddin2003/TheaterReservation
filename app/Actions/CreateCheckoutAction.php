<?php

namespace App\Actions;

use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payments\StripeGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class CreateCheckoutAction
{
    public function __construct(private StripeGateway $stripeGateway) {}

    public function execute(Booking $booking): Session
    {
        $booking->loadMissing(['showtime.movie', 'showtime.screen', 'seatHolds.seat']);
        $activeHolds = $booking->seatHolds->filter(
            fn ($hold): bool => $hold->status === HoldStatus::Active && $hold->expires_at->isFuture(),
        );

        if ($activeHolds->count() !== $booking->seats_count) {
            throw ValidationException::withMessages([
                'checkout' => 'The full seat hold is no longer active. Please choose your seats again.',
            ]);
        }

        $payment = $booking->payments()
            ->where('status', PaymentStatus::Pending)
            ->latest()
            ->first();

        if ($payment?->gateway_session_id) {
            try {
                $session = $this->stripeGateway->retrieveCheckoutSession($payment->gateway_session_id);
            } catch (ApiErrorException|RuntimeException $exception) {
                Log::warning('Stripe checkout session retrieval failed.', [
                    'booking_id' => $booking->getKey(),
                    'payment_id' => $payment->getKey(),
                    'exception' => $exception,
                ]);

                throw ValidationException::withMessages([
                    'checkout' => 'Secure checkout is temporarily unavailable. Please try again.',
                ]);
            }

            if ($session->status === 'open' && is_string($session->url)) {
                return $session;
            }

            if ($session->status === 'complete') {
                return $session;
            }

            $payment->forceFill(['status' => PaymentStatus::Expired])->save();
            $payment = null;
        }

        if (! $payment) {
            $payment = new Payment([
                'gateway' => 'stripe',
                'idempotency_key' => Str::random(64),
                'currency' => $booking->currency,
            ]);
            $payment->forceFill([
                'amount_cents' => $booking->total_cents,
                'status' => PaymentStatus::Pending,
            ]);
            $booking->payments()->save($payment);
        }

        try {
            $session = $this->stripeGateway->createCheckoutSession($booking, $payment);
        } catch (ApiErrorException|RuntimeException $exception) {
            $payment->forceFill([
                'failure_reason' => 'Stripe checkout could not be started.',
            ])->save();

            Log::warning('Stripe checkout session creation failed.', [
                'booking_id' => $booking->getKey(),
                'payment_id' => $payment->getKey(),
                'exception' => $exception,
            ]);

            throw ValidationException::withMessages([
                'checkout' => 'Secure checkout could not be started. Please try again.',
            ]);
        }

        $paymentIntentId = is_string($session->payment_intent)
            ? $session->payment_intent
            : ($session->payment_intent->id ?? null);

        $payment->forceFill([
            'gateway_session_id' => $session->id,
            'gateway_payment_intent_id' => $paymentIntentId,
            'failure_reason' => null,
        ])->save();

        return $session;
    }
}
