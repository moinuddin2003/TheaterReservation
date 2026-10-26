<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Showtime;
use App\Models\StripeEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $tomorrow = $today->copy()->addDay();

        $confirmedBookings = Booking::query()
            ->confirmed()
            ->where('confirmed_at', '>=', $today)
            ->where('confirmed_at', '<', $tomorrow)
            ->get(['confirmed_at', 'seats_count']);

        $ticketsSoldToday = (int) $confirmedBookings->sum('seats_count');
        $hourlySales = array_fill(0, 24, 0);

        foreach ($confirmedBookings as $booking) {
            $hourlySales[(int) $booking->confirmed_at->format('G')] += $booking->seats_count;
        }

        $chartPoints = collect($hourlySales)
            ->values()
            ->map(fn (int $sales, int $hour): string => sprintf(
                '%.1f,%.1f',
                18 + ($hour * 684 / 23),
                138 - ($sales / max(1, max($hourlySales)) * 108),
            ))
            ->implode(' ');

        $showtimes = Showtime::query()
            ->upcoming()
            ->where('starts_at', '<=', $now->copy()->addDay())
            ->with([
                'movie:id,title',
                'screen' => fn (BelongsTo $query) => $query->withCount([
                    'seats as capacity' => fn (Builder $query) => $query->where('is_disabled', false),
                ]),
            ])
            ->withCount([
                'bookingItems as sold_count' => fn (Builder $query) => $query
                    ->where('status', 'active')
                    ->whereHas('booking', fn (Builder $query) => $query->where('status', BookingStatus::Confirmed)),
                'seatHolds as held_count' => fn (Builder $query) => $query
                    ->where('status', 'active')
                    ->where('expires_at', '>', $now),
            ])
            ->orderBy('starts_at')
            ->get();

        $capacity = (int) $showtimes->sum(fn (Showtime $showtime): int => $showtime->screen->capacity);
        $ticketsSold = (int) $showtimes->sum('sold_count');
        $ticketsHeld = (int) $showtimes->sum('held_count');
        $occupancy = $capacity > 0 ? (($ticketsSold + $ticketsHeld) / $capacity) * 100 : 0;

        $grossRevenueCents = Payment::query()
            ->where('status', PaymentStatus::Succeeded)
            ->where('paid_at', '>=', $today)
            ->where('paid_at', '<', $tomorrow)
            ->sum('amount_cents');
        $refundsTodayCents = Refund::query()
            ->where('status', RefundStatus::Succeeded)
            ->where('processed_at', '>=', $today)
            ->where('processed_at', '<', $tomorrow)
            ->sum('amount_cents');

        return view('admin.dashboard', [
            'confirmedBookingsToday' => $confirmedBookings->count(),
            'ticketsSoldToday' => $ticketsSoldToday,
            'netRevenueCents' => $grossRevenueCents - $refundsTodayCents,
            'occupancy' => $occupancy,
            'ticketsSold' => $ticketsSold,
            'ticketsHeld' => $ticketsHeld,
            'capacity' => $capacity,
            'showtimes' => $showtimes->take(5),
            'chartPoints' => $chartPoints,
            'chartArea' => "18,138 {$chartPoints} 702,138",
            'chartLabels' => collect([0, 6, 12, 18])
                ->map(fn (int $hour): array => [
                    'x' => 18 + ($hour * 684 / 23),
                    'label' => $today->copy()->addHours($hour)->format('g A'),
                ]),
            'attention' => [
                [
                    'label' => 'Failed refunds',
                    'count' => Refund::query()->where('status', RefundStatus::Failed)->count(),
                    'route' => 'admin.refunds.index',
                ],
                [
                    'label' => 'Stuck payments · 10+ min',
                    'count' => Payment::query()
                        ->where('status', PaymentStatus::Pending)
                        ->where('created_at', '<=', $now->copy()->subMinutes(10))
                        ->count(),
                    'route' => 'admin.payments.index',
                ],
                [
                    'label' => 'Failed webhook events',
                    'count' => StripeEvent::query()->where('status', 'failed')->count(),
                    'route' => 'admin.stripe-events.index',
                ],
                [
                    'label' => 'Failed jobs',
                    'count' => DB::table('failed_jobs')->count(),
                    'route' => null,
                ],
            ],
            'updatedAt' => $now,
        ]);
    }
}
