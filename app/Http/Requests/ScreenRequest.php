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
            'cinema_id.required' => 'Select the cinema this screen belongs to.',
            'cinema_id.exists' => 'The selected cinema could not be found. Choose an existing cinema.',
            'name.required' => 'Enter a name for this screen.',
            'name.unique' => 'A screen with this name already exists in the selected cinema.',
            'name.max' => 'The screen name may not be longer than 60 characters.',
            'rows_count.required' => 'Enter how many rows this screen has.',
            'rows_count.integer' => 'The number of rows must be a whole number.',
            'rows_count.min' => 'A screen must have at least one row.',
            'rows_count.max' => 'A screen may have at most 26 rows (A–Z).',
            'seats_per_row.required' => 'Enter how many seats each row has.',
            'seats_per_row.integer' => 'Seats per row must be a whole number.',
            'seats_per_row.min' => 'Each row must have at least one seat.',
            'seats_per_row.max' => 'A row may have at most 50 seats.',
        ];
    }
}
