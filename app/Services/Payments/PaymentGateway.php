<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;

interface PaymentGateway
{
    public function createCheckoutSession(Booking $booking, string $successUrl, string $cancelUrl): PaymentResult;

    public function createRefund(Payment $payment, int $amountCents, string $idempotencyKey): PaymentResult;

    /** @return array<string, mixed> */
    public function retrieveEvent(string $eventId): array;
}
