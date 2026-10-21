<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cancel_reason' => ['required', 'string', 'max:255'],
            'confirm_cancellation' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cancel_reason.required' => 'Enter a reason for cancelling this showtime.',
            'cancel_reason.max' => 'The cancellation reason may not be longer than 255 characters.',
            'confirm_cancellation.accepted' => 'Confirm that you understand the bookings and tickets will be cancelled.',
        ];
    }
}
