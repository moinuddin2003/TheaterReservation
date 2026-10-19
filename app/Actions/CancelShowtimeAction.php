<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\ShowtimeStatus;
use App\Models\Showtime;
use App\Models\User;

class CancelShowtimeAction
{
    /**
     * Mark a showtime as cancelled and record who did it and why.
     *
     * Booking-level refunds are handled in the payments/refunds phase; this
     * only records the cancellation and flags the impact for the operator.
     */
    public function execute(Showtime $showtime, User $actor, ?string $reason = null): void
    {
        $showtime->status = ShowtimeStatus::Cancelled;
        $showtime->cancelled_at = now();
        $showtime->cancelled_by = $actor->id;
        $showtime->cancel_reason = $reason;
        $showtime->save();
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
            'amount_cents' => (int) (clone $bookings)->where('status', BookingStatus::Confirmed)->sum('total_cents'),
        ];
    }
}
