<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookingStatus;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'movie_id' => ['nullable', 'integer', Rule::exists('movies', 'id')],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($this->filled('from'), ['after_or_equal:from']),
            ],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
        ];
    }

    public function timezone(): string
    {
        $timezone = Setting::query()->whereKey('timezone')->value('value');

        return is_string($timezone) && $timezone !== ''
            ? $timezone
            : (string) config('app.timezone');
    }

    /**
     * @return array{search: string, movie_id: int|null, from: string|null, to: string|null, status: string|null}
     */
    public function filters(): array
    {
        $filters = $this->validated();

        return [
            'search' => $filters['search'] ?? '',
            'movie_id' => isset($filters['movie_id']) ? (int) $filters['movie_id'] : null,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'status' => $filters['status'] ?? null,
        ];
    }
}
