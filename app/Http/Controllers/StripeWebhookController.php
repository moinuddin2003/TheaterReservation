<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmBookingAction;
use App\Actions\HandleLatePaymentAction;
use App\Actions\ReleaseHoldAction;
use App\Enums\BookingConfirmationResult;
use App\Enums\BookingStatus;
use App\Mail\BookingConfirmed;
use App\Mail\LatePaymentRefund;
use App\Mail\PaymentNotCompleted;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\StripeEvent;
use App\Services\BookingCancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        if ($savedEvent->isProcessed()) {
            return response()->json(['received' => true]);
        }

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $checkoutSession = $event->data->object;
            $payment = $this->findPaymentForSession($checkoutSession);

            if (! $payment || ($checkoutSession->payment_status ?? null) !== 'paid') {
                $this->markEvent($savedEvent, 'ignored');

                return response()->json(['received' => true]);
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

            $confirmationResult = $confirmBookingAction->execute($payment, $paymentIntentId);

            if ($confirmationResult === BookingConfirmationResult::CannotConfirm) {
                $latePaymentRefund = $handleLatePaymentAction->execute($payment, $paymentIntentId);
                $this->queueLatePaymentRefundEmail($latePaymentRefund);
            } elseif ($confirmationResult === BookingConfirmationResult::Confirmed) {
                $this->queueConfirmationEmail($payment);
            }

            $this->markEvent($savedEvent, 'processed');

            return response()->json(['received' => true]);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            $payment = $this->findPaymentForIntent($paymentIntent);
            $paymentIntentId = $paymentIntent->id ?? null;

            if (! $payment) {
                $this->markEvent($savedEvent, 'ignored');

                return response()->json(['received' => true]);
            }

            if (! is_string($paymentIntentId) || ($paymentIntent->status ?? null) !== 'succeeded') {
                $savedEvent->forceFill([
                    'status' => 'failed',
                    'error_message' => 'The matched payment intent does not have a valid succeeded status.',
                ])->save();

                return response()->json(['error' => 'Invalid payment intent status'], 500);
            }

            if (
                (int) ($paymentIntent->amount_received ?? -1) !== $payment->amount_cents
                || strtolower((string) ($paymentIntent->currency ?? '')) !== strtolower($payment->currency)
            ) {
                Log::error('Stripe payment intent amount does not match the saved payment.', [
                    'payment_id' => $payment->getKey(),
                    'stripe_payment_intent_id' => $paymentIntentId,
                ]);
                $savedEvent->forceFill([
                    'status' => 'failed',
                    'error_message' => 'Stripe payment intent amount or currency mismatch.',
                ])->save();

                return response()->json(['error' => 'Payment amount mismatch'], 500);
            }

            $confirmationResult = $confirmBookingAction->execute($payment, $paymentIntentId);

            if ($confirmationResult === BookingConfirmationResult::CannotConfirm) {
                $latePaymentRefund = $handleLatePaymentAction->execute($payment, $paymentIntentId);
                $this->queueLatePaymentRefundEmail($latePaymentRefund);
            } elseif ($confirmationResult === BookingConfirmationResult::Confirmed) {
                $this->queueConfirmationEmail($payment);
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
                $wasReleased = $releaseHoldAction->execute(
                    $payment->booking,
                    $event->type === 'checkout.session.expired'
                        ? BookingStatus::Expired
                        : BookingStatus::Failed,
                );

                if ($wasReleased) {
                    $this->queuePaymentNotCompletedEmail($payment);
                }
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

    private function queueConfirmationEmail(Payment $payment): void
    {
        $booking = Booking::query()
            ->with(['user', 'showtime.movie', 'items.ticket'])
            ->findOrFail($payment->booking_id);

        Mail::to($booking->user->email)
            ->queue((new BookingConfirmed($booking))->afterCommit());
    }

    private function queuePaymentNotCompletedEmail(Payment $payment): void
    {
        $booking = Booking::query()
            ->with(['user', 'showtime.movie', 'items.ticket'])
            ->findOrFail($payment->booking_id);

        Mail::to($booking->user->email)
            ->queue((new PaymentNotCompleted($booking))->afterCommit());
    }

    private function queueLatePaymentRefundEmail(?Refund $refund): void
    {
        if (! $refund) {
            return;
        }

        $refund->loadMissing(['booking.user', 'booking.showtime.movie']);

        Mail::to($refund->booking->user->email)
            ->queue((new LatePaymentRefund($refund))->afterCommit());
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

    private function findPaymentForIntent(StripeObject $paymentIntent): ?Payment
    {
        $paymentId = $paymentIntent->metadata->payment_id ?? null;
        $bookingId = $paymentIntent->metadata->booking_id ?? null;

        if (
            ! is_string($paymentId)
            || ! ctype_digit($paymentId)
            || ! is_string($bookingId)
            || ! ctype_digit($bookingId)
        ) {
            return null;
        }

        return Payment::query()
            ->whereKey($paymentId)
            ->where('booking_id', $bookingId)
            ->where('gateway', 'stripe')
            ->first();
    }
}
