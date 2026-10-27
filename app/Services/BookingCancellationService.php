<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\Payments\StripeGateway;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class BookingCancellationService
{
    public function __construct(
        private StripeGateway $stripeGateway,
        private SettingsService $settings,
    ) {}

    public function cancel(Booking $booking, User $user): Refund
    {
        $refund = DB::transaction(function () use ($booking, $user): Refund {
            $booking = Booking::query()
                ->whereKey($booking->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->user_id !== $user->getKey()) {
                throw new AuthorizationException;
            }

            $latestRefund = $booking->refunds()->latest()->lockForUpdate()->first();

            if ($booking->isRefunded()) {
                return $latestRefund ?? throw ValidationException::withMessages([
                    'booking' => 'This booking has already been refunded.',
                ]);
            }

            if ($booking->isCancelled() && $latestRefund?->isSucceeded()) {
                return $latestRefund;
            }

            if ($booking->isCancelled() && $latestRefund?->isPending()) {
                return $latestRefund;
            }

            if (! $booking->isConfirmed() && ! ($booking->isCancelled() && $latestRefund?->isFailed())) {
                throw ValidationException::withMessages([
                    'booking' => 'Only a confirmed booking can be cancelled.',
                ]);
            }

            if ($booking->isConfirmed()) {
                $booking->load('showtime');
                $cancelWindowHours = (int) $this->settings->get('cancel_window_hours');

                if ($booking->showtime->starts_at->lessThanOrEqualTo(now()->addHours($cancelWindowHours))) {
                    throw ValidationException::withMessages([
                        'booking' => 'Bookings can only be cancelled at least '.$cancelWindowHours.' hours before the show.',
                    ]);
                }

                $hasUsedTicket = $booking->tickets()
                    ->where(function ($query): void {
                        $query->where('status', TicketStatus::Used)
                            ->orWhereHas('scans');
                    })
                    ->exists();

                if ($hasUsedTicket) {
                    throw ValidationException::withMessages([
                        'booking' => 'A booking with a scanned ticket cannot be cancelled.',
                    ]);
                }
            }

            $payment = $this->findRefundablePayment($booking);

            if (! $payment) {
                throw ValidationException::withMessages([
                    'booking' => 'No successful Stripe payment is available to refund for this booking.',
                ]);
            }

            $pendingRefund = $booking->refunds()
                ->where('payment_id', $payment->getKey())
                ->where('status', RefundStatus::Pending)
                ->latest()
                ->lockForUpdate()
                ->first();

            if ($pendingRefund) {
                return $pendingRefund;
            }

            $alreadyRefundedCents = $payment->refunds()
                ->where('status', RefundStatus::Succeeded)
                ->sum('amount_cents');
            $amountCents = $payment->amount_cents - $alreadyRefundedCents;

            if ($amountCents <= 0) {
                throw ValidationException::withMessages([
                    'booking' => 'This payment has no refundable amount remaining.',
                ]);
            }

            $refund = new Refund([
                'payment_id' => $payment->getKey(),
                'currency' => $payment->currency,
                'reason' => 'customer_request',
                'idempotency_key' => bin2hex(random_bytes(32)),
                'initiated_by' => $user->getKey(),
            ]);
            $refund->forceFill(['amount_cents' => $amountCents]);
            $booking->refunds()->save($refund);

            return $refund;
        });

        if ($refund->isSucceeded() || ($refund->isPending() && $refund->gateway_refund_id)) {
            return $refund;
        }

        $payment = Payment::query()->findOrFail($refund->payment_id);

        try {
            $stripeRefund = $this->stripeGateway->createRefund(
                $payment->gateway_payment_intent_id,
                $refund->amount_cents,
                $refund->idempotency_key,
            );
        } catch (ApiErrorException|\RuntimeException $exception) {
            $this->markRefundFailed($refund, 'Stripe could not process the refund request.');

            Log::warning('Stripe refund request failed.', [
                'booking_id' => $booking->getKey(),
                'refund_id' => $refund->getKey(),
                'exception' => $exception,
            ]);

            $message = $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'We could not request the refund. Your booking has not been cancelled. Please try again.';

            throw ValidationException::withMessages(['booking' => $message]);
        }

        $this->saveStripeRefund($refund, $stripeRefund->id, $stripeRefund->status);

        if (in_array($stripeRefund->status, ['failed', 'canceled'], true)) {
            throw ValidationException::withMessages([
                'booking' => 'Stripe could not complete the refund. Your booking has not been cancelled.',
            ]);
        }

        return $refund->refresh();
    }

    public function updateStripeRefund(string $refundId, string $stripeStatus): bool
    {
        $refund = Refund::query()
            ->where('gateway_refund_id', $refundId)
            ->first();

        if (! $refund) {
            return false;
        }

        $this->saveStripeRefund($refund, $refundId, $stripeStatus);

        return true;
    }

    private function findRefundablePayment(Booking $booking): ?Payment
    {
        return $booking->payments()
            ->where('gateway', 'stripe')
            ->where('status', PaymentStatus::Succeeded)
            ->whereNotNull('gateway_payment_intent_id')
            ->latest('paid_at')
            ->lockForUpdate()
            ->first();
    }

    private function saveStripeRefund(Refund $refund, string $refundId, string $stripeStatus): void
    {
        DB::transaction(function () use ($refund, $refundId, $stripeStatus): void {
            $refund = Refund::query()->whereKey($refund->getKey())->lockForUpdate()->firstOrFail();
            $status = match ($stripeStatus) {
                'succeeded' => RefundStatus::Succeeded,
                'pending', 'requires_action' => RefundStatus::Pending,
                default => RefundStatus::Failed,
            };

            $refund->forceFill([
                'gateway_refund_id' => $refundId,
                'status' => $status,
                'failure_message' => $status === RefundStatus::Failed
                    ? 'Stripe did not complete the refund.'
                    : null,
                'processed_at' => $status === RefundStatus::Pending ? null : now(),
            ])->save();

            if ($status !== RefundStatus::Failed) {
                $this->finishCancellation($refund, $status);
            }
        });
    }

    private function markRefundFailed(Refund $refund, string $message): void
    {
        $refund->forceFill([
            'status' => RefundStatus::Failed,
            'failure_message' => $message,
            'processed_at' => now(),
        ])->save();
    }

    private function finishCancellation(Refund $refund, RefundStatus $refundStatus): void
    {
        $booking = Booking::query()->whereKey($refund->booking_id)->lockForUpdate()->firstOrFail();
        $booking->forceFill([
            'status' => $refundStatus === RefundStatus::Succeeded
                ? BookingStatus::Refunded
                : BookingStatus::Cancelled,
            'cancelled_at' => $booking->cancelled_at ?? now(),
            'cancelled_by' => $booking->cancelled_by ?? $refund->initiated_by,
            'cancel_reason' => $booking->cancel_reason ?? 'customer_request',
        ])->save();

        $booking->tickets()
            ->where('status', TicketStatus::Valid)
            ->update([
                'status' => TicketStatus::Void->value,
                'voided_at' => now(),
            ]);

        $booking->seatHolds()
            ->where('status', HoldStatus::Active)
            ->update([
                'status' => HoldStatus::Released->value,
                'released_at' => now(),
            ]);

        if ($refundStatus === RefundStatus::Succeeded) {
            Payment::query()->whereKey($refund->payment_id)->update([
                'status' => PaymentStatus::Refunded->value,
            ]);
        }
    }
}
