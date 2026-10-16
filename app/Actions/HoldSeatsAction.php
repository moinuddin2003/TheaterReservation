<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\ShowtimeStatus;
use App\Models\Booking;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Showtime;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HoldSeatsAction
{
    public function __construct(
        private AuditLogger $audit,
        private ExpireHoldsAction $expireHolds,
    ) {}

    /** @param list<int> $seatIds */
    public function execute(User $user, Showtime $showtime, array $seatIds): Booking
    {
        $this->expireHolds->execute();

        return DB::transaction(function () use ($user, $showtime, $seatIds): Booking {
            $showtime = Showtime::query()->lockForUpdate()->findOrFail($showtime->id);
            $uniqueSeatIds = array_values(array_unique(array_map('intval', $seatIds)));
            if ($uniqueSeatIds === [] || count($uniqueSeatIds) > 6) {
                throw ValidationException::withMessages(['seat_ids' => 'Select between 1 and 6 seats.']);
            }
            if ($showtime->status !== ShowtimeStatus::Scheduled
                || now()->utc()->greaterThanOrEqualTo($showtime->starts_at->subMinutes(15))) {
                throw ValidationException::withMessages(['showtime' => 'Sales are closed for this showtime.']);
            }

            $seats = Seat::query()->where('screen_id', $showtime->screen_id)
                ->whereIn('id', $uniqueSeatIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            if ($seats->count() !== count($uniqueSeatIds) || $seats->contains(fn (Seat $seat) => $seat->is_disabled)) {
                throw ValidationException::withMessages(['seat_ids' => 'One or more selected seats are unavailable.']);
            }

            $alreadyBooked = DB::table('booking_items')
                ->where('showtime_id', $showtime->id)
                ->where('status', 'active')
                ->whereIn('seat_id', $uniqueSeatIds)
                ->exists();
            $alreadyHeld = SeatHold::query()
                ->where('showtime_id', $showtime->id)
                ->where('status', HoldStatus::Active->value)
                ->where('expires_at', '>', now())
                ->whereIn('seat_id', $uniqueSeatIds)
                ->exists();
            if ($alreadyBooked || $alreadyHeld) {
                throw ValidationException::withMessages(['seat_ids' => 'One or more selected seats are already reserved.']);
            }

            $now = now('UTC');
            $holdToken = (string) Str::uuid();
            $booking = Booking::query()->create([
                'reference' => 'CB-'.Str::upper(Str::random(12)),
                'user_id' => $user->id,
                'showtime_id' => $showtime->id,
                'status' => BookingStatus::PendingPayment,
                'seats_count' => count($uniqueSeatIds),
                'total_cents' => 0,
                'currency' => 'USD',
                'hold_expires_at' => $now->addMinutes(10),
            ]);
            $total = 0;
            foreach ($seats as $seat) {
                $price = (int) $showtime->base_price_cents;
                $total += $price;
                $booking->items()->create([
                    'showtime_id' => $showtime->id,
                    'seat_id' => $seat->id,
                    'seat_label' => $seat->label,
                    'seat_type' => $seat->type,
                    'price_cents' => $price,
                    'status' => 'active',
                ]);
                $booking->seatHolds()->create([
                    'hold_token' => $holdToken,
                    'showtime_id' => $showtime->id,
                    'seat_id' => $seat->id,
                    'user_id' => $user->id,
                    'price_cents' => $price,
                    'status' => HoldStatus::Active,
                    'expires_at' => $booking->hold_expires_at,
                ]);
            }
            $booking->forceFill(['total_cents' => $total])->save();
            $this->audit->log('booking.created', $booking, null, $this->audit->snapshot($booking));

            return $booking->load(['items.seat', 'showtime.movie', 'seatHolds']);
        });
    }
}
