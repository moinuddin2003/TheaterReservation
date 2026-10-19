<?php

namespace Database\Seeders;

use App\Enums\SeatType;
use App\Models\Cinema;
use App\Models\Screen;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class CinemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cinema = Cinema::firstOrCreate(
            ['name' => 'CineBook Downtown'],
            [
                'address' => '123 Main Street, Downtown',
                'phone' => '555-0100',
            ],
        );

        $this->screen($cinema, 'Screen 1', 8, 12, ['G', 'H']);
        $this->screen($cinema, 'IMAX', 10, 14, ['I', 'J']);
    }

    /**
     * Create a screen and generate its seat grid.
     *
     * @param  array<int, string>  $premiumRows
     */
    private function screen(Cinema $cinema, string $name, int $rows, int $seatsPerRow, array $premiumRows): void
    {
        $screen = Screen::updateOrCreate(
            ['cinema_id' => $cinema->id, 'name' => $name],
            [
                'rows_count' => $rows,
                'seats_per_row' => $seatsPerRow,
                'is_active' => true,
            ],
        );

        if ($screen->seats()->exists()) {
            return;
        }

        $now = now();
        $seats = [];

        foreach (range('A', chr(ord('A') + $rows - 1)) as $row) {
            for ($number = 1; $number <= $seatsPerRow; $number++) {
                $seats[] = [
                    'screen_id' => $screen->id,
                    'row_label' => $row,
                    'seat_number' => $number,
                    'label' => $row.$number,
                    'type' => in_array($row, $premiumRows, true)
                        ? SeatType::Premium->value
                        : SeatType::Standard->value,
                    'is_disabled' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Seat::insert($seats);
    }
}
