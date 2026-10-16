<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'values' => ['required', 'array', 'min:1'],
            'values.cinema_name' => ['sometimes', 'required', 'string', 'max:120'],
            'values.support_email' => ['sometimes', 'required', 'email', 'max:255'],
            'values.timezone' => ['sometimes', 'required', 'timezone'],
            'values.seat_hold_minutes' => ['sometimes', 'required', 'integer', 'in:10'],
            'values.max_seats_per_booking' => ['sometimes', 'required', 'integer', 'in:6'],
            'values.sales_close_minutes' => ['sometimes', 'required', 'integer', 'in:15'],
            'values.customer_cancel_hours' => ['sometimes', 'required', 'integer', 'in:2'],
        ];
    }
}
