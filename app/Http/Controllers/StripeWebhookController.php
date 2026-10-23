<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmBookingAction;
use App\Actions\HandleLatePaymentAction;
use App\Actions\ReleaseHoldAction;
use App\Enums\BookingStatus;
use App\Models\Payment;
use App\Models\StripeEvent;
use App\Services\BookingCancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeObject;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        BookingCancellationService $cancellationService,
        ConfirmBookingAction $confirmBookingAction,
        HandleLatePaymentAction $handleLatePaymentAction,
        ReleaseHoldAction $releaseHoldAction,
    ): JsonResponse {
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (! $signature || ! $secret) {
            return response()->json(['error' => 'Webhook configuration missing'], 400);
        }

        try {
            $event = Webhook::constructEvent($request->getContent(), $signature, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response()->json(['error' => 'Invalid webhook'], 400);
        }

        $savedEvent = StripeEvent::query()->firstOrCreate(
            ['stripe_event_id' => $event->id],
            ['type' => $event->type],
        );

        if ($savedEvent->isProcessed() || $savedEvent->isIgnored()) {
            return response()->json(['received' => true]);
        }

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $checkoutSession = $event->data->object;
            $payment = $this->findPaymentForSession($checkoutSession);

            if (! $payment || ($checkoutSession->payment_status ?? null) !== 'paid') {
                $savedEvent->forceFill([
                    'status' => 'failed',
                    'error_message' => 'The completed checkout session could not be matched to a payment.',
                ])->save();

                return response()->json(['error' => 'Checkout payment not found'], 500);
            }

            $payment->forceFill(['gateway_session_id' => $checkoutSession->id])->save();

            if (
                (int) ($checkoutSession->amount_total ?? -1) !== $payment->amount_cents
                || strtolower((string) ($checkoutSession->currency ?? '')) !== strtolower($payment->currency)
            ) {
                Log::error('Stripe checkout amount does not match the saved payment.', [
                    'payment_id' => $payment->getKey(),
                    'stripe_session_id' => $checkoutSession->id,
                ]);
                $savedEvent->forceFill([
                    'status' => 'failed',
                    'error_message' => 'Stripe checkout amount or currency mismatch.',
                ])->save();

                return response()->json(['error' => 'Checkout amount mismatch'], 500);
            }

            $paymentIntent = $checkoutSession->payment_intent ?? null;
            $paymentIntentId = is_string($paymentIntent) ? $paymentIntent : ($paymentIntent->id ?? null);

            if (! is_string($paymentIntentId)) {
                Log::error('Stripe checkout event is missing its payment intent.', [
                    'payment_id' => $payment->getKey(),
                    'stripe_session_id' => $checkoutSession->id,
                ]);
                $savedEvent->forceFill([
                    'status' => 'failed',
                    'error_message' => 'Stripe checkout event is missing its payment intent.',
                ])->save();

                return response()->json(['error' => 'Payment intent missing'], 500);
            }

            if (! $confirmBookingAction->execute($payment, $paymentIntentId)) {
                $handleLatePaymentAction->execute($payment, $paymentIntentId);
            }

            $this->markEvent($savedEvent, 'processed');

            return response()->json(['received' => true]);
        }

        if (in_array($event->type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
            $checkoutSession = $event->data->object;
            $payment = $this->findPaymentForSession($checkoutSession);

            if ($payment) {
                $payment->forceFill(['gateway_session_id' => $checkoutSession->id])->save();
                $payment->load('booking');
                $releaseHoldAction->execute(
                    $payment->booking,
                    $event->type === 'checkout.session.expired'
                        ? BookingStatus::Expired
                        : BookingStatus::Failed,
                );
            }

            $this->markEvent($savedEvent, $payment ? 'processed' : 'ignored');

            return response()->json(['received' => true]);
        }

        if (! in_array($event->type, ['refund.updated', 'refund.failed'], true)) {
            $this->markEvent($savedEvent, 'ignored');

            return response()->json(['received' => true]);
        }

        $stripeRefund = $event->data->object;
        $refundId = $stripeRefund->id ?? null;
        $refundStatus = $stripeRefund->status ?? null;

        if (! is_string($refundId) || ! is_string($refundStatus)) {
            $this->markEvent($savedEvent, 'ignored');

            return response()->json(['received' => true]);
        }

        $wasRefundUpdated = $cancellationService->updateStripeRefund($refundId, $refundStatus);
        $this->markEvent($savedEvent, $wasRefundUpdated ? 'processed' : 'ignored');

        return response()->json(['received' => true]);
    }

    private function markEvent(StripeEvent $event, string $status): void
    {
        $event->forceFill([
            'status' => $status,
            'processed_at' => now(),
        ])->save();
    }

    private function findPaymentForSession(StripeObject $session): ?Payment
    {
        $sessionId = $session->id ?? null;

        if (! is_string($sessionId)) {
            return null;
        }

        $payment = Payment::query()
            ->where('gateway_session_id', $sessionId)
            ->first();

        if ($payment) {
            return $payment;
        }

        $paymentId = $session->metadata->payment_id ?? null;

        if (! is_string($paymentId) || ! ctype_digit($paymentId)) {
            return null;
        }

        $payment = Payment::query()
            ->whereKey($paymentId)
            ->where('gateway', 'stripe')
            ->first();

        return $payment && ! $payment->gateway_session_id ? $payment : null;
    }
}
