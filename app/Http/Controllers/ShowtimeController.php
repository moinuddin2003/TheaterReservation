<?php

namespace App\Http\Controllers;

use App\Enums\HoldStatus;
use App\Models\BookingItem;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Showtime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    /**
     * The seat selection page for one showtime, with every value the
     * Blade view needs prepared here. Returns the public JSON payload
     * when the client asks for JSON instead.
     */
    public function show(Request $request, Showtime $showtime): View|JsonResponse
    {
        $showtime->loadMissing(['screen', 'movie' => fn ($query) => $query->with('genres')]);

        $seats = Seat::query()
            ->where('screen_id', $showtime->screen_id)
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get();

        $standardPriceCents = (int) $showtime->base_price_cents;
        $premiumPriceCents = $standardPriceCents + (int) config('cinebook.premium_surcharge_cents', 0);

        $held = $this->heldSeatIds($showtime);
        $sold = $this->soldSeatIds($showtime);

        $seatStates = $this->seats($seats, $held, $sold, $standardPriceCents, $premiumPriceCents);

        if ($request->expectsJson()) {
            return response()->json([
                'showtime_id' => $showtime->id,
                'starts_at' => $showtime->starts_at->toDateTimeString(),
                'movie' => $showtime->movie->title,
                'screen' => $showtime->screen->name,
                'currency' => strtoupper((string) config('cinebook.currency', 'usd')),
                'rows' => $seats->pluck('row_label')->unique()->count(),
                'seats_per_row' => (int) ($seats->groupBy('row_label')->map->count()->max() ?? 0),
                'seat_count' => $seats->count(),
                'seats' => $seatStates,
            ]);
        }

        $statuses = $seatStates->mapWithKeys(fn (array $seat) => [$seat['label'] => $seat['status']]);
        $withStatus = fn (string $status) => $statuses->filter(fn (string $value) => $value === $status)->keys()->values();

        $seatMap = [
            'rows' => $seats->groupBy('row_label')
                ->sortKeys()
                ->map(fn (Collection $rowSeats) => $rowSeats->every(fn (Seat $seat) => $seat->isPremium())
                    ? 'premium'
                    : 'standard')
                ->all(),
            'seats' => $seatStates->groupBy('row')->mapWithKeys(
                fn (Collection $rowSeats, string $row): array => [
                    $row => $rowSeats->map(fn (array $seat): array => [
                        'id' => $seat['id'],
                        'number' => $seat['number'],
                        'type' => $seat['type'],
                        'status' => $seat['status'],
                    ])->all(),
                ],
            )->all(),
            'seats_per_row' => (int) ($seats->groupBy('row_label')->map->count()->max() ?? 0),
            'held' => $withStatus('held')->all(),
            'sold' => $withStatus('sold')->all(),
            'disabled' => $withStatus('disabled')->all(),
            'selected' => [],
        ];

        return view('showtimes.show', [
            'movie' => $showtime->movie,
            'showtime' => $showtime,
            'screen' => $showtime->screen,
            'seatMap' => $seatMap,
            'prices' => [
                'standard' => $standardPriceCents / 100,
                'premium' => $premiumPriceCents / 100,
            ],
            'maxSelection' => (int) config('cinebook.max_seats_per_booking', 6),
            'availableSeats' => $withStatus('available')->count(),
            'holdMinutes' => (int) config('cinebook.hold_minutes', 10),
            'salesClosed' => $showtime->hasSalesClosed(),
            'salesCloseAt' => $showtime->salesCloseAt()->format('H:i'),
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
     * @return Collection<int, array{id: int, label: string, row: string, number: int, type: string, status: string, price_cents: int}>
     */
    private function seats(Collection $seats, Collection $held, Collection $sold, int $standardPrice, int $premiumPrice): Collection
    {
        return $seats->map(fn (Seat $seat): array => [
            'id' => $seat->getKey(),
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
            'price_cents' => $seat->isPremium() ? $premiumPrice : $standardPrice,
        ])->values();
    }
}
