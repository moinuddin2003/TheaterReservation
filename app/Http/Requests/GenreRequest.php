<?php

namespace App\Http\Requests;

use App\Models\Genre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GenreRequest extends FormRequest
{
    protected $errorBag = 'createGenre';

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Derive a slug from the name when one was not supplied.
     */
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('PUT')) {
            $this->errorBag = 'editGenre';
        }

        if ($this->filled('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }

        if ($this->filled('name') && ! $this->filled('slug')) {
            $this->merge(['slug' => Str::slug((string) $this->input('name'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $genre = $this->route('genre');
        $genreId = $genre instanceof Genre ? $genre->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('genres', 'name')->ignore($genreId),
            ],
            'slug' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('genres', 'slug')->ignore($genreId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a name for this genre.',
            'name.unique' => 'A genre with this name already exists.',
            'name.max' => 'The genre name may not be longer than 60 characters.',
            'slug.required' => 'Enter a slug or leave it blank to generate one from the name.',
            'slug.unique' => 'This slug is already being used by another genre.',
            'slug.max' => 'The genre slug may not be longer than 80 characters.',
            'slug.regex' => 'The slug may only contain lowercase letters, numbers and single hyphens.',
        ];
    }
}
