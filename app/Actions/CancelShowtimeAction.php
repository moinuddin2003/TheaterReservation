<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShowtimeStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\SeatHold;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelShowtimeAction
{
    /**
     * Cancel the showtime and close its active reservations atomically.
     */
    /**
     * @return array{
     *   bookings_total: int,
     *   confirmed: int,
     *   pending_payment: int,
     *   seats: int,
     *   tickets: int,
     *   active_holds: int,
     *   amount_cents: int
     * }|null
     */
    public function execute(Showtime $showtime, User $actor, string $reason): ?array
    {
        return DB::transaction(function () use ($showtime, $actor, $reason): ?array {
            $showtime = Showtime::query()->lockForUpdate()->findOrFail($showtime->id);

            if (! $showtime->isScheduled() || $showtime->hasEnded()) {
                return null;
            }

            $impact = $this->impact($showtime);
            $now = now();
            $showtime->status = ShowtimeStatus::Cancelled;
            $showtime->cancelled_at = $now;
            $showtime->cancelled_by = $actor->id;
            $showtime->cancel_reason = $reason;
            $showtime->save();

            $bookingIds = Booking::query()
                ->where('showtime_id', $showtime->id)
                ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::PendingPayment])
                ->pluck('id');

            Booking::query()
                ->whereIn('id', $bookingIds)
                ->update([
                    'status' => BookingStatus::Cancelled->value,
                    'cancelled_at' => $now,
                    'cancelled_by' => $actor->id,
                    'cancel_reason' => $reason,
                    'updated_at' => $now,
                ]);

            BookingItem::query()
                ->where('showtime_id', $showtime->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled', 'updated_at' => $now]);

            $showtime->tickets()
                ->where('status', TicketStatus::Valid)
                ->update([
                    'status' => TicketStatus::Void->value,
                    'voided_at' => $now,
                    'updated_at' => $now,
                ]);

            SeatHold::query()
                ->where('showtime_id', $showtime->id)
                ->where('status', HoldStatus::Active)
                ->update([
                    'status' => HoldStatus::Released->value,
                    'released_at' => $now,
                    'updated_at' => $now,
                ]);

            Payment::query()
                ->whereIn('booking_id', $bookingIds)
                ->where('status', PaymentStatus::Pending)
                ->update([
                    'status' => PaymentStatus::Expired->value,
                    'failure_reason' => 'Showtime cancelled before payment completed.',
                    'updated_at' => $now,
                ]);

            return $impact;
        });
    }

    /**
     * Summarise what cancelling this showtime would affect.
     *
     * @return array<string, int>
     */
    public function impact(Showtime $showtime): array
    {
        $bookings = $showtime->bookings();

        return [
            'bookings_total' => (clone $bookings)->count(),
            'confirmed' => (clone $bookings)->where('status', BookingStatus::Confirmed)->count(),
            'pending_payment' => (clone $bookings)->where('status', BookingStatus::PendingPayment)->count(),
            'seats' => $showtime->bookingItems()->where('status', 'active')->count(),
            'tickets' => $showtime->tickets()->where('status', TicketStatus::Valid)->count(),
            'active_holds' => $showtime->seatHolds()->where('status', HoldStatus::Active)->count(),
            'amount_cents' => (int) (clone $bookings)->where('status', BookingStatus::Confirmed)->sum('total_cents'),
        ];
    }
}
