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
    /**
     * The public home page: hero, now showing, coming soon and genre shortcuts.
     */
    public function index(): View
    {
        $upcomingShowtimes = fn ($query) => $query
            ->where('status', ShowtimeStatus::Scheduled)
            ->where('starts_at', '>', now());

        $nowShowing = Movie::query()
            ->nowShowing()
            ->with('genres')
            ->with(['showtimes' => fn ($query) => $upcomingShowtimes($query)
                ->with('screen')
                ->orderBy('starts_at'),
            ])
            ->withCount(['showtimes as upcoming_showtimes_count' => $upcomingShowtimes])
            ->orderByDesc('release_date')
            ->take(8)
            ->get();

        $comingSoon = Movie::query()
            ->comingSoon()
            ->with('genres')
            ->withCount(['showtimes as upcoming_showtimes_count' => $upcomingShowtimes])
            ->orderBy('release_date')
            ->take(8)
            ->get();

        $genres = Genre::query()
            ->withCount(['movies as now_showing_count' => fn ($query) => $query->where('status', MovieStatus::NowShowing)])
            ->orderBy('name')
            ->get();

        return view('home', [
            'featured' => $nowShowing->first(),
            'nowShowing' => $nowShowing,
            'comingSoon' => $comingSoon,
            'genres' => $genres,
            'cinema' => Cinema::query()->first(),
        ]);
    }
}
