<?php

namespace App\Http\Controllers;

use App\Enums\MovieStatus;
use App\Enums\ShowtimeStatus;
use App\Models\Cinema;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $upcomingShowtimes = fn ($query) => $query
            ->where('status', ShowtimeStatus::Scheduled)
            ->where('starts_at', '>', now());

        // Now showing
        $nowShowing = Movie::query()
            ->nowShowing()
            ->with('genres')
            ->withCount(['showtimes as upcoming_showtimes_count' => $upcomingShowtimes])
            ->orderByDesc('release_date')
            ->take(8)
            ->get();

        // Coming soon
        $comingSoon = Movie::query()
            ->comingSoon()
            ->with('genres')
            ->withCount(['showtimes as upcoming_showtimes_count' => $upcomingShowtimes])
            ->orderBy('release_date')
            ->take(8)
            ->get();

        // Genres
        $genres = Genre::query()
            ->withCount(['movies as now_showing_count' => fn ($query) => $query->where('status', MovieStatus::NowShowing)])
            ->orderBy('name')
            ->get();

        // Hero movie
        $featured = $nowShowing->first();

        // NEW: date tabs (today + next 6 days)
        $dates = collect(range(0, 6))->map(function (int $offset) {
            $day = now()->addDays($offset);

            return [
                'date' => $day->toDateString(),
                'weekday' => $offset === 0 ? 'Today' : $day->format('D'),
                'label' => $day->format('M j'),
            ];
        });

        // NEW: featured movie's showtimes for those 7 days, grouped by date
        $showtimesByDate = $featured
            ? $featured->showtimes()
                ->where('status', ShowtimeStatus::Scheduled)
                ->whereBetween('starts_at', [now()->startOfDay(), now()->addDays(6)->endOfDay()])
                ->with('screen')
                ->orderBy('starts_at')
                ->get()
                ->groupBy(fn ($showtime) => $showtime->starts_at->toDateString())
            : collect();

        // NEW: first showtime a customer can still book (hero line + "Find seats")
        $firstShowtime = $showtimesByDate
            ->flatten()
            ->first(fn ($showtime) => $showtime->isBookable());

        return view('home', [
            'featured' => $featured,
            'nowShowing' => $nowShowing,
            'comingSoon' => $comingSoon,
            'genres' => $genres,
            'cinema' => Cinema::query()->first(),
            'dates' => $dates,
            'showtimesByDate' => $showtimesByDate,
            'firstShowtime' => $firstShowtime,
        ]);
    }
}
