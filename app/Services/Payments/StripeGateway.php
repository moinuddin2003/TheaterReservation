<?php

namespace App\Services\Payments;

use App\Enums\HoldStatus;
use App\Models\Booking;
use App\Models\Payment;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Refund;
use Stripe\StripeClient;

class StripeGateway
{
    public function createCheckoutSession(Booking $booking, Payment $payment): Session
    {
        $stripe = $this->client();
        $booking->loadMissing(['showtime.movie', 'seatHolds.seat']);
        $currency = strtolower($booking->currency);
        $minimumExpiry = now()->addMinutes(30)->timestamp;
        $maximumExpiry = now()->addHours(24)->timestamp;
        $expiresAt = min(max($booking->hold_expires_at->timestamp, $minimumExpiry), $maximumExpiry);
        $activeHolds = $booking->seatHolds->filter(
            fn ($hold): bool => $hold->status === HoldStatus::Active && $hold->expires_at->isFuture(),
        );

        if ($activeHolds->count() !== $booking->seats_count) {
            throw new RuntimeException('The complete seat hold is no longer active.');
        }

        $lineItems = $activeHolds->map(fn ($hold): array => [
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => $hold->price_cents,
                'product_data' => [
                    'name' => $booking->showtime->movie->title.' - Seat '.$hold->seat->label,
                    'description' => ucfirst($hold->seat->type->value).' seat',
                ],
            ],
            'quantity' => 1,
        ])->all();

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'client_reference_id' => (string) $booking->getKey(),
            'metadata' => [
                'booking_id' => (string) $booking->getKey(),
                'payment_id' => (string) $payment->getKey(),
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'booking_id' => (string) $booking->getKey(),
                    'payment_id' => (string) $payment->getKey(),
                ],
            ],
            'success_url' => route('bookings.processing', $booking->reference).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('bookings.checkout', $booking->reference),
            'expires_at' => $expiresAt,
        ], ['idempotency_key' => $payment->idempotency_key]);

        return $session;
    }

    public function retrieveCheckoutSession(string $sessionId): Session
    {
        return $this->client()->checkout->sessions->retrieve($sessionId);
    }

    public function createRefund(
        string $paymentIntentId,
        int $amountCents,
        string $idempotencyKey,
    ): Refund {
        return $this->client()->refunds->create(
            [
                'payment_intent' => $paymentIntentId,
                'amount' => $amountCents,
            ],
            ['idempotency_key' => $idempotencyKey],
        );
    }

    private function client(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (! is_string($secret) || ! str_starts_with($secret, 'sk_test_')) {
            throw new RuntimeException('Stripe test-mode checkout requires a test-mode secret key.');
        }

        return new StripeClient($secret);
    }
}
