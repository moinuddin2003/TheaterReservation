<?php

namespace App\Services;

use App\Actions\HandleLatePaymentAction;
use App\Actions\HandlePaymentFailureAction;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\StripeEvent;
use Illuminate\Support\Facades\DB;
use Throwable;

class StripeEventProcessor
{
    public function __construct(
        private HandleLatePaymentAction $latePayment,
        private HandlePaymentFailureAction $paymentFailure,
    ) {}

    /** @param array<string, mixed> $payload */
    public function process(array $payload): StripeEvent
    {
        $eventId = $payload['id'] ?? null;
        $type = $payload['type'] ?? null;
        if (! is_string($eventId) || ! is_string($type)) {
            throw new \InvalidArgumentException('Stripe event payload must contain string id and type values.');
        }

        $event = DB::transaction(function () use ($eventId, $type): StripeEvent {
            $event = StripeEvent::query()->firstOrCreate(
                ['stripe_event_id' => $eventId],
                ['type' => $type, 'status' => 'received'],
            );

            return StripeEvent::query()->lockForUpdate()->findOrFail($event->id);
        });
        if ($event->status === 'processed') {
            return $event;
        }

        try {
            $object = $payload['data']['object'] ?? [];
            if (! is_array($object)) {
                throw new \InvalidArgumentException('Stripe event data.object must be an object.');
            }
            match ($type) {
                'checkout.session.completed' => $this->checkoutCompleted($object),
                'payment_intent.succeeded' => $this->paymentSucceeded($object),
                'payment_intent.payment_failed' => $this->paymentFailed($object),
                default => null,
            };

            return DB::transaction(function () use ($event): StripeEvent {
                $locked = StripeEvent::query()->lockForUpdate()->findOrFail($event->id);
                $locked->forceFill(['status' => 'processed', 'processed_at' => now(), 'error_message' => null])->save();

                return $locked;
            });
        } catch (Throwable $exception) {
            DB::transaction(function () use ($event, $exception): void {
                $locked = StripeEvent::query()->lockForUpdate()->findOrFail($event->id);
                $locked->forceFill([
                    'status' => 'failed',
                    'error_message' => mb_substr($exception->getMessage(), 0, 65535),
                ])->save();
            });

            throw $exception;
        }
    }

    /** @param array<string, mixed> $object */
    private function checkoutCompleted(array $object): void
    {
        if (($object['payment_status'] ?? null) !== 'paid') {
            return;
        }
        $bookingId = $object['metadata']['booking_id'] ?? null;
        if (! $bookingId && isset($object['client_reference_id'])) {
            $bookingId = Booking::query()->where('reference', $object['client_reference_id'])->value('id');
        }
        if (! $bookingId) {
            throw new \DomainException('Paid Checkout Session did not identify a booking.');
        }
        $booking = Booking::query()->findOrFail($bookingId);
        $payment = $booking->payments()->latest('id')->firstOrFail();
        $intentId = is_string($object['payment_intent'] ?? null) ? $object['payment_intent'] : null;
        $sessionId = is_string($object['id'] ?? null) ? $object['id'] : null;
        $this->latePayment->execute($payment, $intentId, $sessionId);
    }

    /** @param array<string, mixed> $object */
    private function paymentSucceeded(array $object): void
    {
        $payment = $this->paymentForIntent($object);
        $this->latePayment->execute($payment, $object['id'] ?? null);
    }

    /** @param array<string, mixed> $object */
    private function paymentFailed(array $object): void
    {
        $payment = $this->paymentForIntent($object);
        $this->paymentFailure->execute(
            $payment,
            $object['last_payment_error']['message'] ?? 'Payment intent failed.',
        );
    }

    /** @param array<string, mixed> $object */
    private function paymentForIntent(array $object): Payment
    {
        $intentId = $object['id'] ?? null;
        $bookingId = $object['metadata']['booking_id'] ?? null;
        $payment = Payment::query()
            ->when($intentId, fn ($query) => $query->where('gateway_payment_intent_id', $intentId))
            ->when($bookingId, fn ($query) => $query->orWhere('booking_id', $bookingId))
            ->with('booking')
            ->latest('id')
            ->first();
        if (! $payment) {
            throw new \DomainException('Stripe payment intent is not associated with a booking.');
        }

        return $payment;
    }
}
