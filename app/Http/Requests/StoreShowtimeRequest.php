<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movie_id' => ['required', 'integer', 'exists:movies,id'],
            'screen_id' => ['required', 'integer', 'exists:screens,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'base_price_cents' => ['required', 'integer', 'between:0,4294967295'],
            'status' => ['sometimes', Rule::enum(\App\Enums\ShowtimeStatus::class)],
        ];
    }
}
