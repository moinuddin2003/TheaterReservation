<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateSeatsRequest extends FormRequest
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
            'premium_rows' => ['nullable', 'array'],
            'premium_rows.*' => ['string', 'max:3'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'premium_rows.array' => 'Choose premium rows from the list.',
            'premium_rows.*.string' => 'Each premium row must be a valid row label.',
            'premium_rows.*.max' => 'A premium row label may not be longer than 3 characters.',
        ];
    }
}
