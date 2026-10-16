<?php

namespace App\Actions\Admin;

use App\Enums\SeatType;
use App\Models\Screen;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveScreenAction
{
    public function __construct(private AuditLogger $audit) {}

    /** @param array{cinema_id: int, name: string, rows_count: int, seats_per_row: int} $data */
    public function execute(array $data, ?Screen $screen = null): Screen
    {
        return DB::transaction(function () use ($data, $screen): Screen {
            $screen = $screen ? Screen::query()->lockForUpdate()->findOrFail($screen->id) : new Screen;
            $before = $screen->exists ? $this->audit->snapshot($screen) : null;
            $resizing = $screen->exists && (
                (int) $screen->rows_count !== (int) $data['rows_count']
                || (int) $screen->seats_per_row !== (int) $data['seats_per_row']
            );

            if ($screen->exists && (int) $screen->cinema_id !== (int) $data['cinema_id'] && $screen->showtimes()->exists()) {
                throw ValidationException::withMessages(['cinema_id' => 'A screen with showtimes cannot be moved to another cinema.']);
            }
            if ($resizing && $screen->showtimes()->where('starts_at', '>', now())->exists()) {
                throw ValidationException::withMessages(['screen' => 'A screen with future showtimes cannot be resized.']);
            }

            $screen->fill($data)->save();
            $existingSeats = $screen->seats()->get()->keyBy(fn ($seat) => $seat->row_label.':'.$seat->seat_number);
            for ($row = 0; $row < $data['rows_count']; $row++) {
                for ($number = 1; $number <= $data['seats_per_row']; $number++) {
                    $rowLabel = $this->rowLabel($row);
                    $key = $rowLabel.':'.$number;
                    if ($existingSeats->has($key)) {
                        $existingSeats->forget($key);

                        continue;
                    }
                    $screen->seats()->create([
                        'row_label' => $rowLabel,
                        'seat_number' => $number,
                        'label' => $rowLabel.$number,
                        'type' => $number === 1 ? SeatType::Accessible : ($row >= max(0, $data['rows_count'] - 2) ? SeatType::Premium : SeatType::Standard),
                        'is_disabled' => false,
                    ]);
                }
            }
            foreach ($existingSeats as $seat) {
                if ($seat->bookingItems()->exists() || $seat->holds()->exists()) {
                    throw ValidationException::withMessages(['screen' => 'Seats with bookings or holds cannot be removed while resizing.']);
                }
                app(DeleteSeatAction::class)->execute($seat);
            }

            $this->audit->log($before === null ? 'screen.created' : 'screen.updated', $screen, $before, $this->audit->snapshot($screen));

            return $screen->refresh();
        });
    }

    private function rowLabel(int $index): string
    {
        $label = '';
        do {
            $label = chr(65 + ($index % 26)).$label;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $label;
    }
}
