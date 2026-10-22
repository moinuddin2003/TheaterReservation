<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CancelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'cancel_reason' => ['required', 'string', 'max:255'],
            'override_reason' => [$this->afterCutoff() ? 'required' : 'nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'also_refund' => ['nullable', 'boolean'],
            'amount' => ['required_if:also_refund,1', 'nullable', 'numeric', 'min:0.01', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'confirm_cancellation' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $booking = $this->route('booking');
            if (! $booking instanceof Booking) {
                return;
            }

            if ($booking->status !== BookingStatus::Confirmed) {
                $validator->errors()->add('cancel_reason', 'Only confirmed bookings can be cancelled.');
            }

            if (! $booking->showtime->starts_at->isFuture()) {
                $validator->errors()->add('cancel_reason', 'A booking cannot be cancelled after its showtime has started.');
            }
        });
    }

    private function afterCutoff(): bool
    {
        $booking = $this->route('booking');
        if (! $booking instanceof Booking) {
            return false;
        }

        $cancelWindowHours = Setting::query()->whereKey('cancel_window_hours')->value('value');
        $cancelWindowHours = $cancelWindowHours === null
            ? (int) config('cinebook.cancel_window_hours', 2)
            : (int) $cancelWindowHours;

        return now()->greaterThan(
            $booking->showtime->starts_at->copy()->subHours(max(0, $cancelWindowHours)),
        );
    }
}
