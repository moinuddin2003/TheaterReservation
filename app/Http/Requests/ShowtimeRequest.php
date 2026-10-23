<?php

namespace App\Http\Requests;

use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $showtime = $this->route('showtime');
        $currentScreenId = $showtime instanceof Showtime ? $showtime->screen_id : null;

        return [
            'movie_id' => ['required', 'integer', Rule::exists('movies', 'id')],
            'screen_id' => [
                'required',
                'integer',
                Rule::exists('screens', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->when($currentScreenId, fn ($query) => $query->orWhere('id', $currentScreenId))),
            ],
            'starts_at' => ['required', 'date', 'after_or_equal:now'],
            'base_price' => ['required', 'numeric', 'min:0.5', 'max:1000'],
        ];
    }

    /**
     * Reject a showtime that overlaps another scheduled showtime on the same screen.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $showtime = $this->route('showtime');
                $ignoreId = $showtime instanceof Showtime ? $showtime->id : null;

                $conflict = Showtime::query()
                    ->scheduled()
                    ->where('screen_id', $this->validated('screen_id'))
                    ->overlapping($this->startsAt(), $this->endsAt())
                    ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                    ->exists();

                if ($conflict) {
                    $validator->errors()->add('starts_at', 'This screen already has an overlapping showtime.');
                }
            },
        ];
    }

    /**
     * The requested start as a Carbon instance.
     */
    public function startsAt(): Carbon
    {
        return Carbon::parse((string) $this->validated('starts_at'));
    }

    /**
     * The derived end (start + runtime + turnaround).
     */
    public function endsAt(): Carbon
    {
        return Showtime::endsAtFor($this->startsAt(), Movie::findOrFail($this->validated('movie_id')));
    }

    /**
     * The base price in integer cents.
     */
    public function basePriceCents(): int
    {
        return (int) round(((float) $this->validated('base_price')) * 100);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'starts_at.after_or_equal' => 'A showtime cannot be scheduled in the past.',
            'screen_id.exists' => 'Choose an active screen for this showtime.',
        ];
    }
}
