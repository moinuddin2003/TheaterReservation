<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Mail\PaymentNotCompleted;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Showtime;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HoldSeatsAction
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @param  array<int, int>  $seatIds
     */
    public function execute(Showtime $showtime, User $user, array $seatIds): Booking
    {
        return DB::transaction(function () use ($showtime, $user, $seatIds): Booking {
            $showtime = Showtime::query()
                ->whereKey($showtime->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $showtime->isBookable()) {
                throw ValidationException::withMessages([
                    'seat_ids' => 'Seat sales for this showtime have closed.',
                ]);
            }

            $seatIds = array_map('intval', $seatIds);
            if (count(array_unique($seatIds)) !== count($seatIds)) {
                throw ValidationException::withMessages([
                    'seat_ids' => 'Choose each seat only once.',
                ]);
            }
            $seatIds = array_values($seatIds);
            $maxSeats = (int) $this->settings->get('max_seats_per_booking');

            if (count($seatIds) === 0 || count($seatIds) > $maxSeats) {
                throw ValidationException::withMessages([
                    'seat_ids' => 'Select between 1 and '.$maxSeats.' seats.',
                ]);
            }

            SeatHold::query()
                ->where('showtime_id', $showtime->getKey())
                ->where('status', HoldStatus::Active)
                ->where('expires_at', '<=', now())
                ->update([
                    'status' => HoldStatus::Expired->value,
                    'released_at' => now(),
                ]);

            $expiredBookings = Booking::query()
                ->where('showtime_id', $showtime->getKey())
                ->where('status', BookingStatus::PendingPayment)
                ->where('hold_expires_at', '<=', now())
                ->whereHas('payments', fn ($query) => $query
                    ->where('status', PaymentStatus::Pending)
                    ->whereNotNull('gateway_session_id'))
                ->with(['user', 'showtime.movie', 'items.ticket'])
                ->get();

            if ($expiredBookings->isNotEmpty()) {
                $expiredBookingIds = $expiredBookings->modelKeys();

                Booking::query()->whereIn('id', $expiredBookingIds)
                    ->update(['status' => BookingStatus::Expired->value]);
                Payment::query()
                    ->whereIn('booking_id', $expiredBookingIds)
                    ->where('status', PaymentStatus::Pending)
                    ->update(['status' => PaymentStatus::Expired->value]);

                foreach ($expiredBookings as $expiredBooking) {
                    Mail::to($expiredBooking->user->email)
                        ->queue((new PaymentNotCompleted($expiredBooking))->afterCommit());
                }
            }

            $seats = Seat::query()
                ->where('screen_id', $showtime->screen_id)
                ->whereIn('id', $seatIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== count($seatIds) || $seats->contains(fn (Seat $seat): bool => $seat->is_disabled)) {
                throw ValidationException::withMessages([
                    'seat_ids' => 'One or more selected seats are no longer available.',
                ]);
            }

            $unavailableSeatIds = SeatHold::query()
                ->where('showtime_id', $showtime->getKey())
                ->whereIn('seat_id', $seatIds)
                ->where('status', HoldStatus::Active)
                ->where('expires_at', '>', now())
                ->pluck('seat_id')
                ->merge(
                    $showtime->bookingItems()
                        ->whereIn('seat_id', $seatIds)
                        ->where('status', 'active')
                        ->pluck('seat_id'),
                )
                ->unique();

            if ($unavailableSeatIds->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'seat_ids' => 'One or more selected seats have just been taken. Please choose different seats.',
                ]);
            }

            $premiumSurcharge = (int) $this->settings->get('premium_surcharge_cents');
            $holds = $seats->map(fn (Seat $seat): array => [
                'seat' => $seat,
                'price_cents' => $showtime->base_price_cents + ($seat->isPremium() ? $premiumSurcharge : 0),
            ]);
            $totalCents = (int) $holds->sum('price_cents');
            $expiresAt = now()->addMinutes((int) $this->settings->get('hold_minutes'));

            $booking = new Booking([
                'reference' => 'CB-'.Str::upper(Str::random(10)),
                'user_id' => $user->getKey(),
                'showtime_id' => $showtime->getKey(),
                'seats_count' => $seats->count(),
                'currency' => strtoupper((string) config('cinebook.currency', 'usd')),
                'hold_expires_at' => $expiresAt,
            ]);
            $booking->forceFill(['total_cents' => $totalCents])->save();

            $holdToken = (string) Str::uuid();

            foreach ($holds as $holdData) {
                $hold = new SeatHold([
                    'hold_token' => $holdToken,
                    'showtime_id' => $showtime->getKey(),
                    'seat_id' => $holdData['seat']->getKey(),
                    'user_id' => $user->getKey(),
                    'booking_id' => $booking->getKey(),
                    'expires_at' => $expiresAt,
                ]);
                $hold->forceFill(['price_cents' => $holdData['price_cents']])->save();
            }

            return $booking;
        });
    }
}
