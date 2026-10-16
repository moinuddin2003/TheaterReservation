<?php

namespace App\Actions\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Booking;
use App\Models\Refund;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IssueRefundAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Booking $booking, int $amountCents, string $reason, ?User $actor = null): Refund
    {
        return DB::transaction(function () use ($booking, $amountCents, $reason, $actor): Refund {
            $booking = Booking::query()->with('payments')->lockForUpdate()->findOrFail($booking->id);
            $payment = $booking->payments()->where('status', PaymentStatus::Succeeded->value)->lockForUpdate()->latest('id')->first();
            if (! $payment) {
                throw ValidationException::withMessages(['refund' => 'There is no successful payment to refund.']);
            }
            $reservedCents = (int) Refund::query()
                ->where('payment_id', $payment->id)
                ->whereIn('status', [
                    RefundStatus::Pending->value,
                    RefundStatus::Processing->value,
                    RefundStatus::Succeeded->value,
                ])->sum('amount_cents');
            $remainingCents = max(0, (int) $payment->amount_cents - $reservedCents);
            if ($amountCents < 1 || $amountCents > $remainingCents) {
                throw ValidationException::withMessages(['amount_cents' => 'The refund must be at least 1 cent and cannot exceed the remaining paid balance.']);
            }

            $refund = Refund::query()->create([
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'amount_cents' => $amountCents,
                'currency' => $payment->currency,
                'status' => RefundStatus::Pending,
                'reason' => mb_substr($reason, 0, 30),
                'idempotency_key' => (string) Str::uuid(),
                'initiated_by' => $actor?->id ?? auth()->id(),
            ]);
            $this->audit->log('refund.created', $refund, null, $this->audit->snapshot($refund));

            return $refund;
        });
    }
}
