<?php

namespace Database\Factories;

use App\Enums\ShowtimeStatus;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\Showtime;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Showtime>
 */
class ShowtimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = Carbon::now()
            ->addDays(fake()->numberBetween(1, 10))
            ->setTime(fake()->numberBetween(10, 21), 0);

        return [
            'movie_id' => Movie::factory(),
            'screen_id' => Screen::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(fake()->numberBetween(85, 150) + 15),
            'base_price_cents' => 1000,
            'status' => ShowtimeStatus::Scheduled,
        ];
    }

    /**
     * Indicate that the showtime starts the given number of minutes from now.
     */
    public function startingIn(int $minutes): static
    {
        return $this->state(function () use ($minutes) {
            $startsAt = Carbon::now()->addMinutes($minutes);

            return [
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMinutes(120),
            ];
        });
    }

    /**
     * Indicate that the showtime is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShowtimeStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
