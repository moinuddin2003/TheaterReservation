<?php

namespace App\Http\Requests;

use App\Models\Screen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }

        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $screen = $this->route('screen');
        $screenId = $screen instanceof Screen ? $screen->id : null;

        return [
            'cinema_id' => ['required', 'integer', Rule::exists('cinemas', 'id')],
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('screens', 'name')
                    ->where(fn ($query) => $query->where('cinema_id', $this->input('cinema_id')))
                    ->ignore($screenId),
            ],
            'rows_count' => ['required', 'integer', 'min:1', 'max:26'],
            'seats_per_row' => ['required', 'integer', 'min:1', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A screen with this name already exists in the selected cinema.',
            'rows_count.max' => 'A screen may have at most 26 rows (A–Z).',
        ];
    }
}
