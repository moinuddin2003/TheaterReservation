<?php

namespace App\Http\Requests;

use App\Enums\ShowtimeStatus;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('base_price_cents') && $this->filled('price')) {
            $this->merge([
                'base_price_cents' => (int) round((float) $this->input('price') * 100),
            ]);
        }

        $movie = Movie::query()->find($this->input('movie_id'));
        $startsAt = $this->input('starts_at');
        if ($movie && is_string($startsAt) && strtotime($startsAt) !== false) {
            $this->merge([
                'ends_at' => Carbon::parse($startsAt)
                    ->addMinutes((int) $movie->duration_minutes + 20)
                    ->toDateTimeString(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'movie_id' => ['required', 'integer', 'exists:movies,id'],
            'screen_id' => ['required', 'integer', 'exists:screens,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'base_price_cents' => ['required', 'integer', 'between:0,4294967295'],
            'status' => ['sometimes', Rule::enum(ShowtimeStatus::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('ends_at') || ! $this->filled('starts_at') || ! $this->filled('screen_id')) {
                return;
            }

            $showtime = $this->route('showtime');
            $overlaps = Showtime::query()
                ->where('screen_id', $this->integer('screen_id'))
                ->where('starts_at', '<', $this->input('ends_at'))
                ->where('ends_at', '>', $this->input('starts_at'))
                ->when($showtime, fn ($query) => $query->whereKeyNot($showtime->id))
                ->exists();
            if ($overlaps) {
                $validator->errors()->add('starts_at', 'This screening overlaps another showtime on the selected screen.');
            }
        });
    }
}
