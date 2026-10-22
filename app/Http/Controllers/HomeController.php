<?php

namespace App\Http\Controllers;

use App\Enums\MovieStatus;
use App\Enums\ShowtimeStatus;
use App\Models\Cinema;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $showtimes = fn ($query) => $query
            ->where('status', ShowtimeStatus::Scheduled)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at');

        // Now showing
        $nowShowing = Movie::query()
            ->nowShowing()
            ->with('genres')
            ->with(['showtimes' => $showtimes])
            ->orderByDesc('release_date')
            ->take(8)
            ->get();

        // Coming soon
        $comingSoon = Movie::query()
            ->comingSoon()
            ->with('genres')
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
        $cinema = Cinema::query()->first();

        $dates = collect(range(0, 6))->map(function (int $offset) {
            $day = now()->addDays($offset);

            return [
                'date' => $day->toDateString(),
                'weekday' => $offset === 0 ? 'Today' : $day->format('D'),
                'label' => $day->format('M j'),
            ];
        });

        $showtimesByDate = Showtime::query()
            ->where('status', ShowtimeStatus::Scheduled)
            ->whereBetween('starts_at', [now()->startOfDay(), now()->addDays(6)->endOfDay()])
            ->whereHas('movie', fn ($query) => $query->where('status', MovieStatus::NowShowing))
            ->when(
                $cinema,
                fn ($query) => $query->whereHas(
                    'screen',
                    fn ($screenQuery) => $screenQuery->where('cinema_id', $cinema->id),
                ),
            )
            ->with(['movie', 'screen'])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn ($showtime) => $showtime->starts_at->toDateString());

        return view('home', [
            'featured' => $featured,
            'nowShowing' => $nowShowing,
            'comingSoon' => $comingSoon,
            'genres' => $genres,
            'cinema' => $cinema,
            'dates' => $dates,
            'showtimesByDate' => $showtimesByDate,
        ]);
    }
}
