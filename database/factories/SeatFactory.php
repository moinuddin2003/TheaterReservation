<?php

namespace Database\Factories;

use App\Enums\SeatType;
use App\Models\Screen;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seat>
 */
class SeatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $row = fake()->randomElement(['A', 'B', 'C', 'D']);
        $number = fake()->numberBetween(1, 12);

        return [
            'screen_id' => Screen::factory(),
            'row_label' => $row,
            'seat_number' => $number,
            'label' => $row.$number,
            'type' => SeatType::Standard,
            'is_disabled' => false,
        ];
    }

    /**
     * Indicate that the seat is a premium seat.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SeatType::Premium,
        ]);
    }

    /**
     * Indicate that the seat is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_disabled' => true,
        ]);
    }
}
