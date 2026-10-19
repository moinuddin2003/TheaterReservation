<?php

namespace App\Http\Requests;

use App\Enums\MovieStatus;
use App\Models\Movie;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Trim the title and derive a slug when one was not supplied.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('title')) {
            $this->merge(['title' => trim((string) $this->input('title'))]);
        }

        if ($this->filled('title') && ! $this->filled('slug')) {
            $this->merge(['slug' => Str::slug((string) $this->input('title'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $movie = $this->route('movie');
        $movieId = $movie instanceof Movie ? $movie->id : null;

        return [
            'title' => [
                'required',
                'string',
                'max:200',
                Rule::unique('movies', 'title')->ignore($movieId),
            ],
            'slug' => [
                'required',
                'string',
                'max:220',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('movies', 'slug')->ignore($movieId),
            ],
            'synopsis' => ['required', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'rating' => ['nullable', 'string', 'max:10'],
            'release_date' => ['nullable', 'date'],
            'poster_path' => ['nullable', 'string', 'max:255'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'trailer_url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::enum(MovieStatus::class)],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['integer', Rule::exists('genres', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers and single hyphens.',
            'genres.*.exists' => 'One of the selected genres no longer exists.',
        ];
    }
}
