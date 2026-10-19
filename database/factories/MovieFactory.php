<?php

namespace Database\Factories;

use App\Enums\MovieStatus;
use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Movie>
 */
class MovieFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'synopsis' => fake()->paragraph(),
            'duration_minutes' => fake()->numberBetween(85, 150),
            'rating' => fake()->randomElement(['G', 'PG', 'PG-13', 'R']),
            'release_date' => fake()->dateTimeBetween('-1 month', '+2 months'),
            'poster_path' => $this->placeholderPoster($title),
            'backdrop_path' => $this->placeholderBackdrop($title),
            'trailer_url' => config('cinebook.sample_trailer_url'),
            'status' => MovieStatus::NowShowing,
        ];
    }

    /**
     * Indicate that the movie is now showing.
     */
    public function nowShowing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MovieStatus::NowShowing,
        ]);
    }

    /**
     * Indicate that the movie is coming soon.
     */
    public function comingSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MovieStatus::ComingSoon,
        ]);
    }

    /**
     * Indicate that the movie is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MovieStatus::Archived,
        ]);
    }

    /**
     * Build a placeholder poster URL for a title.
     */
    private function placeholderPoster(string $title): string
    {
        return 'https://placehold.co/600x900/1e293b/ffffff?text='.urlencode($title);
    }

    /**
     * Build a placeholder backdrop URL for a title.
     */
    private function placeholderBackdrop(string $title): string
    {
        return 'https://placehold.co/1600x900/0b1622/ffffff?text='.urlencode($title);
    }
}
