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
            'backdrop_path' => ['nullable', 'string', 'max:255'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'backdrop' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
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
            'title.required' => 'Enter a title for this movie.',
            'title.unique' => 'A movie with this title already exists.',
            'slug.required' => 'Enter a slug for this movie.',
            'slug.unique' => 'This slug is already being used by another movie.',
            'slug.regex' => 'The slug may only contain lowercase letters, numbers and single hyphens.',
            'synopsis.required' => 'Enter a synopsis for this movie.',
            'duration_minutes.required' => 'Enter the movie runtime in minutes.',
            'duration_minutes.integer' => 'The runtime must be a whole number of minutes.',
            'duration_minutes.min' => 'The runtime must be at least 1 minute.',
            'duration_minutes.max' => 'The runtime may not be longer than 600 minutes.',
            'release_date.date' => 'Enter a valid release date.',
            'poster_path.max' => 'The poster path may not be longer than 255 characters.',
            'backdrop_path.max' => 'The backdrop path may not be longer than 255 characters.',
            'trailer_url.url' => 'Enter a valid trailer URL, including https://.',
            'trailer_url.max' => 'The trailer URL may not be longer than 255 characters.',
            'status.required' => 'Choose a status for this movie.',
            'genres.array' => 'Choose valid genres for this movie.',
            'genres.*.exists' => 'One of the selected genres no longer exists.',
            'poster.image' => 'The poster must be an image file.',
            'poster.mimes' => 'The poster must be a JPG, PNG or WebP image.',
            'poster.max' => 'The poster may not be larger than 4 MB.',
            'backdrop.image' => 'The backdrop must be an image file.',
            'backdrop.mimes' => 'The backdrop must be a JPG, PNG or WebP image.',
            'backdrop.max' => 'The backdrop may not be larger than 8 MB.',
        ];
    }
}
