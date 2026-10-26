<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($this->filled('from'), ['after_or_equal:from']),
            ],
            'status' => ['nullable', Rule::in(['pending', 'succeeded', 'failed', 'expired', 'refunded'])],
        ];
    }

    /**
     * @return array{search: string, from: string|null, to: string|null, status: string|null}
     */
    public function filters(): array
    {
        $filters = $this->validated();

        return [
            'search' => $filters['search'] ?? '',
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'status' => $filters['status'] ?? null,
        ];
    }
}
