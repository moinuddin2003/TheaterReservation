<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'synopsis' => ['required', 'string'],
            'duration_minutes' => ['required', 'integer', 'between:1,65535'],
            'rating' => ['nullable', 'string', 'max:10'],
            'release_date' => ['nullable', 'date'],
            'poster_path' => ['nullable', 'string', 'max:255'],
            'trailer_url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::enum(\App\Enums\MovieStatus::class)],
            'genre_ids' => ['sometimes', 'array', 'max:20'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
        ];
    }
}
