<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ShowtimeStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Setting;
use App\Models\Showtime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $timezone = (string) Setting::query()->whereKey('timezone')->value('value');
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = config('app.timezone', 'UTC');
        }

        $localNow = CarbonImmutable::now($timezone);
        $todayStartUtc = $localNow->startOfDay()->utc();
        $tomorrowStartUtc = $localNow->addDay()->startOfDay()->utc();
        $chartStart = $localNow->subDays(29)->startOfDay();
        $chartStartUtc = $chartStart->utc();
        $chartPayments = Payment::query()
            ->where('status', PaymentStatus::Succeeded->value)
            ->whereBetween('paid_at', [$chartStartUtc, $tomorrowStartUtc])
            ->get(['amount_cents', 'paid_at']);
        $revenueByDay = array_fill_keys(
            collect(range(0, 29))->map(fn (int $offset) => $chartStart->addDays($offset)->format('Y-m-d'))->all(),
            0,
        );
        foreach ($chartPayments as $payment) {
            $day = $payment->paid_at->timezone($timezone)->format('Y-m-d');
            if (array_key_exists($day, $revenueByDay)) {
                $revenueByDay[$day] += (int) $payment->amount_cents;
            }
        }
        $maxRevenue = max(1, ...array_values($revenueByDay));
        $revenueChart = collect($revenueByDay)->map(
            fn (int $amount, string $day): array => [
                'day' => CarbonImmutable::parse($day, $timezone)->format('M j'),
                'amount_cents' => $amount,
                'height_percent' => (int) round(($amount / $maxRevenue) * 100),
            ],
        );

        $statusCounts = Booking::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $topMovies = DB::table('booking_items')
            ->join('showtimes', 'showtimes.id', '=', 'booking_items.showtime_id')
            ->join('movies', 'movies.id', '=', 'showtimes.movie_id')
            ->where('booking_items.status', 'active')
            ->select('movies.id', 'movies.title')
            ->selectRaw('COUNT(*) as tickets_sold')
            ->groupBy('movies.id', 'movies.title')
            ->orderByDesc('tickets_sold')
            ->limit(5)
            ->get();
        $todayShowtimes = Showtime::query()
            ->with(['movie', 'screen.seats'])
            ->withCount(['bookingItems as sold_seats_count' => fn ($query) => $query->where('status', 'active')])
            ->where('status', ShowtimeStatus::Scheduled->value)
            ->where('starts_at', '>=', $todayStartUtc)
            ->where('starts_at', '<', $tomorrowStartUtc)
            ->orderBy('starts_at')
            ->limit(8)
            ->get();
        $todayShowtimes->each(function (Showtime $showtime): void {
            $capacity = $showtime->screen->seats->count();
            $showtime->setAttribute(
                'occupancy_percent',
                $capacity === 0 ? 0 : min(100, (int) round(($showtime->sold_seats_count / $capacity) * 100)),
            );
        });

        return view('admin.dashboard', [
            'timezone' => $timezone,
            'stats' => [
                [
                    'label' => "Today's bookings",
                    'value' => number_format(Booking::query()->whereBetween('created_at', [$todayStartUtc, $tomorrowStartUtc])->count()),
                    'note' => 'Bookings created today',
                    'icon' => 'ticket',
                ],
                [
                    'label' => 'Revenue today',
                    'value' => '$'.number_format((int) Payment::query()
                        ->where('status', PaymentStatus::Succeeded->value)
                        ->whereBetween('paid_at', [$todayStartUtc, $tomorrowStartUtc])
                        ->sum('amount_cents') / 100, 2),
                    'note' => 'Collected today (USD)',
                    'icon' => 'dollar-sign',
                ],
                [
                    'label' => 'Upcoming showtimes',
                    'value' => number_format(Showtime::query()
                        ->where('status', ShowtimeStatus::Scheduled->value)
                        ->where('starts_at', '>=', now('UTC'))
                        ->count()),
                    'note' => 'Scheduled screenings',
                    'icon' => 'calendar-clock',
                ],
                [
                    'label' => 'Pending refunds',
                    'value' => number_format(Refund::query()->where('status', RefundStatus::Pending->value)->count()),
                    'note' => 'Awaiting processing',
                    'icon' => 'undo-2',
                ],
            ],
            'revenueChart' => $revenueChart,
            'bookingStatusCounts' => $statusCounts,
            'topMovies' => $topMovies,
            'todayShowtimes' => $todayShowtimes,
            'recentBookings' => Booking::query()
                ->with(['user', 'showtime.movie'])
                ->latest('created_at')
                ->limit(8)
                ->get(),
            'alerts' => [
                'failed_payments' => Payment::query()
                    ->where('status', PaymentStatus::Failed->value)
                    ->where('created_at', '>=', now('UTC')->subDay())
                    ->count(),
                'failed_refunds' => Refund::query()->where('status', RefundStatus::Failed->value)->count(),
            ],
        ]);
    }
}
