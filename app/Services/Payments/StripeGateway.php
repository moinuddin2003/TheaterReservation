<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeGateway implements PaymentGateway
{
    private const API = 'https://api.stripe.com/v1';

    public function createCheckoutSession(Booking $booking, string $successUrl, string $cancelUrl): PaymentResult
    {
        $this->assertTestMode();
        $booking->loadMissing(['user', 'showtime.movie', 'items']);
        $payment = $booking->payments()
            ->where('status', PaymentStatus::Pending->value)
            ->latest('id')
            ->firstOrFail();
        $response = Http::withToken($this->secretKey())->asForm()->withHeaders([
            'Idempotency-Key' => $payment->idempotency_key,
        ])->post(self::API.'/checkout/sessions', [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $booking->user->email,
            'client_reference_id' => $booking->reference,
            'metadata[booking_id]' => (string) $booking->id,
            'payment_intent_data[metadata][booking_id]' => (string) $booking->id,
            'line_items[0][price_data][currency]' => 'usd',
            'line_items[0][price_data][unit_amount]' => $booking->total_cents,
            'line_items[0][price_data][product_data][name]' => $booking->showtime->movie->title.' tickets',
            'line_items[0][quantity]' => 1,
        ]);

        return $this->result($response, 'url');
    }

    public function createRefund(Payment $payment, int $amountCents, string $idempotencyKey): PaymentResult
    {
        $this->assertTestMode();
        if (! $payment->gateway_payment_intent_id) {
            throw new RuntimeException('A Stripe payment intent is required before a refund can be created.');
        }
        if ($amountCents < 1 || $amountCents > (int) $payment->amount_cents) {
            throw new RuntimeException('The Stripe refund amount is outside the paid amount.');
        }
        $response = Http::withToken($this->secretKey())->asForm()->withHeaders([
            'Idempotency-Key' => $idempotencyKey,
        ])->post(self::API.'/refunds', [
            'payment_intent' => $payment->gateway_payment_intent_id,
            'amount' => $amountCents,
        ]);

        return $this->result($response);
    }

    public function retrieveEvent(string $eventId): array
    {
        $this->assertTestMode();
        $response = Http::withToken($this->secretKey())->get(self::API.'/events/'.rawurlencode($eventId));
        if (! $response->successful()) {
            throw new RuntimeException('Stripe event retrieval failed: '.$this->errorMessage($response));
        }

        return $response->json();
    }

    private function assertTestMode(): void
    {
        if (! str_starts_with($this->secretKey(), 'sk_test_')) {
            throw new RuntimeException('Stripe is restricted to test mode for CineBook admin operations.');
        }
    }

    private function secretKey(): string
    {
        $key = (string) config('services.stripe.secret');
        if ($key === '') {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        return $key;
    }

    private function result(Response $response, ?string $successField = null): PaymentResult
    {
        if (! $response->successful()) {
            return new PaymentResult(false, error: $this->errorMessage($response));
        }

        $body = $response->json();

        return new PaymentResult(
            true,
            id: $body['id'] ?? null,
            url: $successField ? ($body[$successField] ?? null) : null,
        );
    }

    private function errorMessage(Response $response): string
    {
        return (string) ($response->json('error.message') ?? 'Stripe request failed with HTTP '.$response->status().'.');
    }
}
