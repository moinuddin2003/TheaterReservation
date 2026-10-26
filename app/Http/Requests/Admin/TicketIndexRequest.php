<?php

namespace App\Http\Requests\Admin;

use App\Enums\ScanResult;
use App\Enums\TicketStatus;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'tab' => ['nullable', Rule::in(['tickets', 'scans'])],
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'result' => ['nullable', Rule::enum(ScanResult::class)],
            'movie_id' => ['nullable', 'integer', Rule::exists('movies', 'id')],
            'staff_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($this->filled('from'), ['after_or_equal:from']),
            ],
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
     * @return array{tab: string, search: string, status: string|null, result: string|null, movie_id: int|null, staff_id: int|null, from: string|null, to: string|null}
     */
    public function filters(): array
    {
        $filters = $this->validated();

        return [
            'tab' => $filters['tab'] ?? 'tickets',
            'search' => $filters['search'] ?? '',
            'status' => $filters['status'] ?? null,
            'result' => $filters['result'] ?? null,
            'movie_id' => isset($filters['movie_id']) ? (int) $filters['movie_id'] : null,
            'staff_id' => isset($filters['staff_id']) ? (int) $filters['staff_id'] : null,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
        ];
    }
}
