<?php

namespace App\Http\Controllers;

use App\Enums\MovieStatus;
use App\Enums\ShowtimeStatus;
use App\Models\Cinema;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

        $scheduleQuery = Showtime::query()
            ->where('status', ShowtimeStatus::Scheduled)
            ->where('starts_at', '>', now()->addMinutes((int) config('cinebook.sales_cutoff_minutes', 15)))
            ->whereHas('movie', fn ($query) => $query->where('status', MovieStatus::NowShowing))
            ->when(
                $cinema,
                fn ($query) => $query->whereHas(
                    'screen',
                    fn ($screenQuery) => $screenQuery->where('cinema_id', $cinema->id),
                ),
            );

        $dateValues = (clone $scheduleQuery)
            ->selectRaw('DATE(starts_at) as show_date')
            ->distinct()
            ->orderBy('show_date')
            ->limit(7)
            ->pluck('show_date');

        $dates = $dateValues->map(function (string $date) {
            $day = Carbon::parse($date, config('cinema.timezone'));

            return [
                'date' => $day->toDateString(),
                'weekday' => $day->isToday() ? 'Today' : $day->format('D'),
                'label' => $day->format('M j'),
            ];
        });

        $showtimesByDate = (clone $scheduleQuery)
            ->whereIn(DB::raw('DATE(starts_at)'), $dates->pluck('date'))
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
