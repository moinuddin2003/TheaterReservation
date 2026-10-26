<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CinemaRequest extends FormRequest
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

        foreach (['address', 'phone'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a name for this cinema.',
            'name.max' => 'The cinema name may not be longer than 120 characters.',
            'address.max' => 'The cinema address may not be longer than 255 characters.',
            'phone.max' => 'The phone number may not be longer than 30 characters.',
        ];
    }
}
