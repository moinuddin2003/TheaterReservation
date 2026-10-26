<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefundIndexRequest extends FormRequest
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
        ];
    }

    /**
     * @return array{search: string, from: string, to: string}
     */
    public function filters(): array
    {
        $filters = $this->validated();
        $today = today();

        return [
            'search' => $filters['search'] ?? '',
            'from' => $filters['from'] ?? $today->copy()->startOfMonth()->toDateString(),
            'to' => $filters['to'] ?? $today->copy()->endOfMonth()->toDateString(),
        ];
    }
}
