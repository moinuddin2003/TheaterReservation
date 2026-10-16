<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cinema_id' => ['required', 'integer', 'exists:cinemas,id'],
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('screens', 'name')
                    ->where('cinema_id', $this->input('cinema_id'))
                    ->ignore($this->route('screen')),
            ],
            'rows_count' => ['required', 'integer', 'between:1,26'],
            'seats_per_row' => ['required', 'integer', 'between:1,99'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
