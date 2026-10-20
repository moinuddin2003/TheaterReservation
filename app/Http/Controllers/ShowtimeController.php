<?php

namespace App\Http\Controllers;

use App\Enums\HoldStatus;
use App\Models\BookingItem;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Showtime;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class ShowtimeController extends Controller
{
    /**
     * Bricks 1 to 3: the real showtime row, its screen's seats and each
     * seat's state for this showtime.
     *
     * Prices and the Blade view come in later bricks.
     */
    public function show(Showtime $showtime): JsonResponse
    {
        $showtime->loadMissing(['movie', 'screen']);

        $seats = Seat::query()
            ->where('screen_id', $showtime->screen_id)
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get();

        $held = $this->heldSeatIds($showtime);
        $sold = $this->soldSeatIds($showtime);

        return response()->json([
            'showtime_id' => $showtime->id,
            'starts_at' => $showtime->starts_at->toDateTimeString(),
            'movie' => $showtime->movie->title,
            'screen' => $showtime->screen->name,
            'rows' => $seats->pluck('row_label')->unique()->count(),
            'seats_per_row' => (int) ($seats->groupBy('row_label')->map->count()->max() ?? 0),
            'seat_count' => $seats->count(),
            'seats' => $this->seats($seats, $held, $sold),
        ]);
    }

    /**
     * Seats an active, unexpired hold is sitting on for this showtime.
     *
     * @return Collection<int, int>
     */
    private function heldSeatIds(Showtime $showtime): Collection
    {
        return SeatHold::query()
            ->where('showtime_id', $showtime->id)
            ->where('status', HoldStatus::Active)
            ->where('expires_at', '>', now())
            ->pluck('seat_id')
            ->flip();
    }

    /**
     * Seats sold for this showtime.
     *
     * @return Collection<int, int>
     */
    private function soldSeatIds(Showtime $showtime): Collection
    {
        return BookingItem::query()
            ->where('showtime_id', $showtime->id)
            ->where('status', 'active')
            ->pluck('seat_id')
            ->flip();
    }

    /**
     * @param  Collection<int, Seat>  $seats
     * @param  Collection<int, int>  $held
     * @param  Collection<int, int>  $sold
     * @return Collection<int, array{label: string, row: string, number: int, type: string, status: string}>
     */
    private function seats(Collection $seats, Collection $held, Collection $sold): Collection
    {
        return $seats->map(fn (Seat $seat): array => [
            'label' => $seat->label,
            'row' => $seat->row_label,
            'number' => $seat->seat_number,
            'type' => $seat->type->value,
            'status' => match (true) {
                $seat->is_disabled => 'disabled',
                $sold->has($seat->id) => 'sold',
                $held->has($seat->id) => 'held',
                default => 'available',
            },
        ])->values();
    }
}
