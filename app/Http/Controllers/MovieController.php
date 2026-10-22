<?php

namespace App\Http\Controllers;

use App\Enums\MovieStatus;
use App\Enums\ShowtimeStatus;
use App\Models\BookingItem;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MovieController extends Controller
{
    /**
     * Public movie listing (now showing + coming soon) with genre/status/search filters.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $search = is_string($search) ? $search : null;

        $genre = $request->query('genre');
        $genre = is_string($genre) ? $genre : null;

        $status = MovieStatus::tryFrom((string) $request->query('status', ''));
        $status = in_array($status, [MovieStatus::NowShowing, MovieStatus::ComingSoon], true) ? $status : null;

        $movies = Movie::query()
            ->whereIn('status', [MovieStatus::NowShowing, MovieStatus::ComingSoon])
            ->with('genres')
            ->with([
                'showtimes' => fn($query) => $query
                    ->where('status', ShowtimeStatus::Scheduled)
                    ->where('starts_at', '>=', now())
                    ->orderByDesc('starts_at'),
            ])
            ->withCount([
                'showtimes as upcoming_showtimes_count' => fn($query) => $query
                    ->where('status', ShowtimeStatus::Scheduled)
                    ->where('starts_at', '>', now()),
            ])
            ->search($search)
            ->when($status, fn($query, $value) => $query->where('status', $value))
            ->when($genre, fn($query, $slug) => $query->whereHas('genres', fn($genres) => $genres->where('slug', $slug)))
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        // $movie = Movie::all();
        // dd($movie);

        // dd($movies->items());

        // $movies = Movie::where('status', 'now_showing')->get();
        // dd($movies);

        return view('movies.catalog', [
            'movies' => $movies,
            'genres' => Genre::query()->orderBy('name')->get(),
            'filters' => [
                'search' => $search,
                'genre' => $genre,
                'status' => $status?->value,
            ],
        ]);
    }

    /**
     * Public movie detail with upcoming showtimes grouped by date.
     */
    public function show(Movie $movie): View
    {
        $movie->load('genres');

        $showtimes = $movie->showtimes()
            ->where('status', ShowtimeStatus::Scheduled)
            ->where('starts_at', '>', now())
            ->with('screen')
            ->orderBy('starts_at')
            ->get();

        return view('movies.index', [
            'movie' => $movie,
            'showtimesByDate' => $showtimes->groupBy(fn ($showtime) => $showtime->starts_at->toDateString()),
            'availability' => $this->availabilityFor($showtimes),
        ]);
    }

    /**
     * Sold/held/available seat counts per showtime.
     *
     * @param  Collection<int, Showtime>  $showtimes
     * @return array<int, array<string, int|bool>>
     */
    private function availabilityFor(Collection $showtimes): array
    {
        if ($showtimes->isEmpty()) {
            return [];
        }

        $capacities = Seat::query()
            ->whereIn('screen_id', $showtimes->pluck('screen_id')->unique())
            ->where('is_disabled', false)
            ->selectRaw('screen_id, count(*) as total')
            ->groupBy('screen_id')
            ->pluck('total', 'screen_id');

        $ids = $showtimes->pluck('id');

        $held = SeatHold::query()
            ->whereIn('showtime_id', $ids)
            ->where('status', 'active')
            ->selectRaw('showtime_id, count(*) as total')
            ->groupBy('showtime_id')
            ->pluck('total', 'showtime_id');

        $sold = BookingItem::query()
            ->whereIn('showtime_id', $ids)
            ->where('status', 'active')
            ->selectRaw('showtime_id, count(*) as total')
            ->groupBy('showtime_id')
            ->pluck('total', 'showtime_id');

        $availability = [];

        foreach ($showtimes as $showtime) {
            $capacity = (int) ($capacities[$showtime->screen_id] ?? 0);
            $taken = (int) ($held[$showtime->id] ?? 0) + (int) ($sold[$showtime->id] ?? 0);
            $available = max(0, $capacity - $taken);

            $availability[$showtime->id] = [
                'capacity' => $capacity,
                'available' => $available,
                'sold_out' => $available <= 0,
            ];
        }

        return $availability;
    }
}
