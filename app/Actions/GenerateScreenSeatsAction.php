<?php

namespace App\Actions;

use App\Enums\SeatType;
use App\Models\Screen;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;

class GenerateScreenSeatsAction
{
    /**
     * (Re)generate the full seat grid for a screen.
     *
     * Existing seats are removed first, so callers must ensure the screen is
     * not in use (showtimes / holds / bookings reference seats on restrict).
     *
     * @param  array<int, string>  $premiumRows  Row labels (e.g. ["A", "B"]) that should be premium.
     * @return int The number of seats created.
     */
    public function execute(Screen $screen, array $premiumRows = []): int
    {
        $premiumRows = array_map(
            fn (string $row) => strtoupper(trim($row)),
            $premiumRows,
        );

        return DB::transaction(function () use ($screen, $premiumRows): int {
            $screen->seats()->delete();

            $now = now();
            $records = [];

            for ($index = 1; $index <= $screen->rows_count; $index++) {
                $row = $this->rowLabel($index);
                $type = in_array($row, $premiumRows, true)
                    ? SeatType::Premium->value
                    : SeatType::Standard->value;

                for ($number = 1; $number <= $screen->seats_per_row; $number++) {
                    $records[] = [
                        'screen_id' => $screen->id,
                        'row_label' => $row,
                        'seat_number' => $number,
                        'label' => $row.$number,
                        'type' => $type,
                        'is_disabled' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($records !== []) {
                Seat::insert($records);
            }

            return count($records);
        });
    }

    /**
     * Convert a 1-based row index into a spreadsheet-style label (A, B, ... Z, AA, ...).
     */
    private function rowLabel(int $index): string
    {
        $label = '';

        while ($index > 0) {
            $index--;
            $label = chr(65 + ($index % 26)).$label;
            $index = intdiv($index, 26);
        }

        return $label;
    }
}
