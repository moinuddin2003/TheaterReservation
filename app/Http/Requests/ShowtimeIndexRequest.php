<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ShowtimeIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Normalize missing and invalid dates to today in the cinema timezone.
     */
    protected function prepareForValidation(): void
    {
        $date = $this->query('date');

        if (! is_string($date) || ! $this->isValidDate($date)) {
            $date = Carbon::now($this->timezone())->toDateString();
        }

        $this->merge(['date' => $date]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:120'],
            'cinema_id' => ['nullable', 'integer', Rule::exists('cinemas', 'id')],
            'screen_id' => ['nullable', 'integer', Rule::exists('screens', 'id')],
        ];
    }

    public function timezone(): string
    {
        $timezone = Setting::query()->whereKey('timezone')->value('value');

        return is_string($timezone) && $timezone !== ''
            ? $timezone
            : (string) config('app.timezone');
    }

    public function selectedDate(): Carbon
    {
        return Carbon::parse((string) $this->validated('date'), $this->timezone())->startOfDay();
    }

    public function weekStart(): Carbon
    {
        return $this->selectedDate()->startOfWeek(Carbon::MONDAY);
    }

    public function weekEnd(): Carbon
    {
        return $this->weekStart()->copy()->endOfWeek(Carbon::SUNDAY);
    }

    public function search(): ?string
    {
        return $this->validated('search');
    }

    public function cinemaId(): ?int
    {
        $cinemaId = $this->validated('cinema_id');

        return $cinemaId === null ? null : (int) $cinemaId;
    }

    public function screenId(): ?int
    {
        $screenId = $this->validated('screen_id');

        return $screenId === null ? null : (int) $screenId;
    }

    private function isValidDate(string $date): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) {
            return false;
        }

        $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, new \DateTimeZone($this->timezone()));
        $errors = \DateTimeImmutable::getLastErrors();

        return $parsedDate !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $parsedDate->format('Y-m-d') === $date;
    }
}
