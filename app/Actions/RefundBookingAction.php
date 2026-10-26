<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RefundBookingAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(
        Booking $booking,
        User $actor,
        int $amountCents,
        string $reason,
        ?string $ipAddress = null,
    ): Refund {
        $refund = $this->prepare($booking, $actor, $amountCents, $reason, $ipAddress);

        return $this->processPrepared($refund, $actor, $ipAddress);
    }

    public function prepare(
        Booking $booking,
        User $actor,
        int $amountCents,
        string $reason,
        ?string $ipAddress = null,
    ): Refund {
        if (! in_array($reason, ['requested_by_customer', 'duplicate', 'fraudulent'], true)) {
            throw ValidationException::withMessages([
                'reason' => 'Select a valid refund reason.',
            ]);
        }

        $secret = config('services.stripe.secret');

        if (! is_string($secret) || $secret === '') {
            throw ValidationException::withMessages([
                'amount' => 'Stripe refunds are unavailable until STRIPE_SECRET is configured.',
            ]);
        }

        return DB::transaction(function () use ($booking, $actor, $amountCents, $reason, $ipAddress): Refund {
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $lockedBooking->loadMissing('showtime');
            if (! $lockedBooking->showtime->starts_at->isFuture()) {
                throw ValidationException::withMessages([
                    'amount' => 'Refunds cannot be issued after the showtime has started.',
                ]);
            }

            $payments = Payment::query()
                ->where('booking_id', $lockedBooking->id)
                ->where('status', PaymentStatus::Succeeded)
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->get();
            $payment = $payments->first(function (Payment $candidate): bool {
                $hasPendingRefund = $candidate->refunds()->where('status', RefundStatus::Pending)->exists();
                $alreadyRefunded = (int) $candidate->refunds()
                    ->where('status', RefundStatus::Succeeded)
                    ->sum('amount_cents');

                return ! $hasPendingRefund
                    && $candidate->gateway === 'stripe'
                    && filled($candidate->gateway_payment_intent_id)
                    && $alreadyRefunded < $candidate->amount_cents;
            });

            if (! $payment) {
                throw ValidationException::withMessages([
                    'amount' => 'This booking does not have a refundable Stripe payment.',
                ]);
            }

            $alreadyRefunded = (int) $payment->refunds()
                ->where('status', RefundStatus::Succeeded)
                ->sum('amount_cents');
            $remainingCents = $payment->amount_cents - $alreadyRefunded;

            if ($amountCents < 1 || $amountCents > $remainingCents) {
                throw ValidationException::withMessages([
                    'amount' => 'The refund amount must be greater than zero and no more than the remaining refundable balance.',
                ]);
            }

            $refund = Refund::query()->create([
                'payment_id' => $payment->id,
                'booking_id' => $lockedBooking->id,
                'amount_cents' => $amountCents,
                'currency' => $payment->currency,
                'status' => RefundStatus::Pending,
                'reason' => $reason,
                'idempotency_key' => (string) Str::uuid(),
                'initiated_by' => $actor->id,
            ]);

            $this->auditLogger->log(
                $actor,
                'booking.refund_requested',
                $lockedBooking,
                ['refundable_cents' => $remainingCents],
                [
                    'refund_id' => $refund->id,
                    'amount_cents' => $amountCents,
                    'reason' => $reason,
                    'status' => RefundStatus::Pending->value,
                ],
                $ipAddress,
            );

            return $refund->fresh(['payment', 'booking']);
        });
    }

    public function processPrepared(Refund $refund, User $actor, ?string $ipAddress = null): Refund
    {
        $refund->loadMissing(['payment', 'booking']);
        $payment = $refund->payment;
        $amountCents = $refund->amount_cents;
        $secret = config('services.stripe.secret');

        if ($refund->status !== RefundStatus::Pending) {
            return $refund;
        }

        try {
            $response = Http::withToken($secret)
                ->asForm()
                ->withHeaders(['Idempotency-Key' => $refund->idempotency_key])
                ->timeout(20)
                ->post('https://api.stripe.com/v1/refunds', [
                    'payment_intent' => $payment->gateway_payment_intent_id,
                    'amount' => $amountCents,
                    'reason' => $refund->reason,
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'amount' => 'Stripe could not confirm the refund request. It remains pending; check the refund record before retrying.',
            ]);
        }

        if ($response->serverError()) {
            throw ValidationException::withMessages([
                'amount' => 'Stripe returned a server error. The refund remains pending; verify its status before retrying.',
            ]);
        }

        if ($response->failed()) {
            $message = $response->json('error.message');
            $refund->forceFill([
                'status' => RefundStatus::Failed,
                'failure_message' => is_string($message) ? Str::limit($message, 255, '') : 'Stripe rejected the refund request.',
                'processed_at' => now(),
            ])->save();

            $this->auditLogger->log(
                $actor,
                'booking.refund_processed',
                $refund->booking,
                ['status' => RefundStatus::Pending->value],
                [
                    'refund_id' => $refund->id,
                    'amount_cents' => $amountCents,
                    'status' => RefundStatus::Failed->value,
                    'failure_message' => $refund->failure_message,
                ],
                $ipAddress,
            );

            return $refund->fresh(['payment', 'booking']);
        }

        $stripeRefundId = $response->json('id');
        $stripeStatus = $response->json('status');
        if (! is_string($stripeRefundId) || ! in_array($stripeStatus, ['succeeded', 'pending', 'failed'], true)) {
            throw ValidationException::withMessages([
                'amount' => 'Stripe returned an incomplete refund response. This refund remains pending and needs review.',
            ]);
        }

        return DB::transaction(function () use ($actor, $amountCents, $refund, $payment, $stripeRefundId, $stripeStatus, $response, $ipAddress): Refund {
            $refund = Refund::query()->lockForUpdate()->findOrFail($refund->id);
            $status = RefundStatus::from($stripeStatus);
            $refund->forceFill([
                'gateway_refund_id' => $stripeRefundId,
                'status' => $status,
                'failure_message' => $response->json('failure_reason'),
                'processed_at' => $status === RefundStatus::Pending ? null : now(),
            ])->save();

            if ($status === RefundStatus::Succeeded) {
                $refundedCents = (int) $payment->refunds()
                    ->where('status', RefundStatus::Succeeded)
                    ->sum('amount_cents');

                if ($refundedCents >= $payment->amount_cents) {
                    $payment->forceFill(['status' => PaymentStatus::Refunded])->save();

                    $successfulPayments = Payment::query()
                        ->where('booking_id', $refund->booking_id)
                        ->whereIn('status', [PaymentStatus::Succeeded, PaymentStatus::Refunded])
                        ->withSum(['refunds as refunded_cents' => fn ($query) => $query
                            ->where('status', RefundStatus::Succeeded)], 'amount_cents')
                        ->get();

                    if ($successfulPayments->isNotEmpty() && $successfulPayments->every(
                        fn (Payment $successfulPayment): bool => (int) $successfulPayment->refunded_cents >= $successfulPayment->amount_cents,
                    )) {
                        Booking::query()
                            ->whereKey($refund->booking_id)
                            ->where('status', '!=', BookingStatus::Cancelled)
                            ->update(['status' => BookingStatus::Refunded->value, 'updated_at' => now()]);
                    }
                }
            }

            $this->auditLogger->log(
                $actor,
                'booking.refund_processed',
                $refund->booking,
                ['status' => RefundStatus::Pending->value],
                [
                    'refund_id' => $refund->id,
                    'amount_cents' => $amountCents,
                    'status' => $status->value,
                    'gateway_refund_id' => $stripeRefundId,
                ],
                $ipAddress,
            );

            return $refund->fresh(['payment', 'booking']);
        });
    }
}
