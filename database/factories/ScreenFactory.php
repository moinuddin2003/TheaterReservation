<?php

namespace Database\Factories;

use App\Models\Cinema;
use App\Models\Screen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Screen>
 */
class ScreenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cinema_id' => Cinema::factory(),
            'name' => 'Screen '.fake()->unique()->numberBetween(1, 20),
            'rows_count' => 8,
            'seats_per_row' => 12,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the screen is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
