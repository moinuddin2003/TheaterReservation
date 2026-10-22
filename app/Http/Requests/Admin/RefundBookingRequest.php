<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RefundBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'refund_type' => ['required', Rule::in(['full', 'partial'])],
            'reason' => ['required', Rule::in(['requested_by_customer', 'duplicate', 'fraudulent'])],
            'confirm_refund' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $booking = $this->route('booking');
            if (! $booking instanceof Booking) {
                return;
            }

            if (! $booking->showtime->starts_at->isFuture()) {
                $validator->errors()->add('amount', 'Refunds cannot be issued after the showtime has started.');

                return;
            }

            $payment = $this->refundablePayment($booking);
            if (! $payment) {
                $validator->errors()->add('amount', 'This booking does not have a refundable Stripe payment.');

                return;
            }

            $remainingCents = $payment->amount_cents - (int) $payment->refunds()
                ->where('status', RefundStatus::Succeeded)
                ->sum('amount_cents');
            $amount = $this->input('amount');
            if (is_numeric($amount) && ((float) $amount * 100) > $remainingCents) {
                $validator->errors()->add('amount', 'The refund amount cannot exceed the remaining refundable balance.');
            }

            if ($this->input('refund_type') === 'full' && is_numeric($amount) && (int) round((float) $amount * 100) !== $remainingCents) {
                $validator->errors()->add('amount', 'A full refund must match the remaining refundable balance.');
            }
        });
    }

    public function refundableAmountCents(): int
    {
        $booking = $this->route('booking');
        if (! $booking instanceof Booking) {
            return 0;
        }

        $payment = $this->refundablePayment($booking);

        return $payment
            ? max(0, $payment->amount_cents - (int) $payment->refunds()
                ->where('status', RefundStatus::Succeeded)
                ->sum('amount_cents'))
            : 0;
    }

    public function amountCents(): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $this->validated('amount'), 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function refundablePayment(Booking $booking): ?Payment
    {
        return $booking->payments()
            ->where('status', PaymentStatus::Succeeded)
            ->where('gateway', 'stripe')
            ->whereNotNull('gateway_payment_intent_id')
            ->with('refunds')
            ->orderByDesc('created_at')
            ->get()
            ->first(fn (Payment $payment): bool => ! $payment->refunds->contains(
                fn ($refund): bool => $refund->status === RefundStatus::Pending,
            ) && $payment->refunds->where('status', RefundStatus::Succeeded)->sum('amount_cents') < $payment->amount_cents);
    }
}
