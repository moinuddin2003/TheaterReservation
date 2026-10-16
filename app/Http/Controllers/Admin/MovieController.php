<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteMovieAction;
use App\Actions\Admin\SaveMovieAction;
use App\Actions\Admin\SetMovieArchivedAction;
use App\Enums\MovieStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMovieRequest;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MovieController extends Controller
{
    public function index(Request $request): View
    {
        $query = Movie::query()
            ->with('genres')
            ->withCount([
                'showtimes',
                'showtimes as upcoming_showtimes_count' => fn (Builder $showtimes) => $showtimes
                    ->where('status', 'scheduled')
                    ->where('starts_at', '>=', now()),
            ])
            ->addSelect([
                'tickets_sold_count' => DB::table('booking_items')
                    ->join('showtimes', 'showtimes.id', '=', 'booking_items.showtime_id')
                    ->whereColumn('showtimes.movie_id', 'movies.id')
                    ->where('booking_items.status', 'active')
                    ->whereExists(fn ($bookings) => $bookings
                        ->selectRaw('1')
                        ->from('bookings')
                        ->whereColumn('bookings.id', 'booking_items.booking_id')
                        ->where('bookings.status', 'confirmed'))
                    ->selectRaw('COUNT(*)'),
            ]);
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where('title', 'like', '%'.$search.'%');
        }
        if ($request->filled('genre')) {
            $query->whereHas('genres', fn (Builder $genres) => $genres->whereKey($request->integer('genre')));
        }
        if ($request->filled('status') && MovieStatus::tryFrom((string) $request->query('status'))) {
            $query->where('status', $request->query('status'));
        }
        $sortable = ['title', 'status', 'release_date', 'created_at'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'created_at';
        $query->orderBy($sort, $request->query('direction') === 'asc' ? 'asc' : 'desc');

        return view('admin.movies.index', [
            'movies' => $query->paginate(12)->withQueryString(),
            'genres' => Genre::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => MovieStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.movies.form', [
            'movie' => new Movie,
            'genres' => Genre::query()->orderBy('name')->get(),
            'statuses' => MovieStatus::cases(),
        ]);
    }

    public function store(StoreMovieRequest $request, SaveMovieAction $action): RedirectResponse
    {
        $movie = $action->execute($request->validated(), actor: $request->user());

        return to_route('admin.movies.show', $movie)->with('status', 'Movie created.');
    }

    public function show(Movie $movie): View
    {
        $movie->load('genres');
        $movie->loadCount([
            'showtimes',
            'showtimes as upcoming_showtimes_count' => fn (Builder $query) => $query
                ->where('status', 'scheduled')
                ->where('starts_at', '>=', now()),
        ]);
        $upcomingShowtimes = Showtime::query()
            ->with('screen')
            ->withCount(['bookingItems as tickets_sold_count' => fn (Builder $query) => $query
                ->where('status', 'active')
                ->whereHas('booking', fn (Builder $bookings) => $bookings->where('status', 'confirmed'))])
            ->where('movie_id', $movie->id)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(10)
            ->get();
        $ticketsSold = (int) $movie->showtimes()
            ->withCount(['bookingItems as tickets_sold_count' => fn (Builder $query) => $query
                ->where('status', 'active')
                ->whereHas('booking', fn (Builder $bookings) => $bookings->where('status', 'confirmed'))])
            ->get()
            ->sum('tickets_sold_count');
        $salesCents = (int) DB::table('payments')
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
            ->where('showtimes.movie_id', $movie->id)
            ->where('payments.status', 'succeeded')
            ->sum('payments.amount_cents');

        return view('admin.movies.show', compact('movie', 'upcomingShowtimes', 'ticketsSold', 'salesCents'));
    }

    public function edit(Movie $movie): View
    {
        $movie->load('genres');

        return view('admin.movies.form', [
            'movie' => $movie,
            'genres' => Genre::query()->orderBy('name')->get(),
            'statuses' => MovieStatus::cases(),
        ]);
    }

    public function update(StoreMovieRequest $request, Movie $movie, SaveMovieAction $action): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['remove_poster']) && ! $request->hasFile('poster')) {
            $data['poster_path'] = null;
        }
        unset($data['remove_poster']);
        $movie = $action->execute($data, $movie, $request->user());

        return to_route('admin.movies.show', $movie)->with('status', 'Movie updated.');
    }

    public function destroy(Movie $movie, DeleteMovieAction $action): RedirectResponse
    {
        $action->execute($movie);

        return to_route('admin.movies.index')->with('status', 'Movie deleted.');
    }

    public function archive(Movie $movie, SetMovieArchivedAction $action): RedirectResponse
    {
        $action->execute($movie, true);

        return back()->with('status', 'Movie archived.');
    }

    public function restore(Movie $movie, SetMovieArchivedAction $action): RedirectResponse
    {
        $action->execute($movie, false);

        return back()->with('status', 'Movie restored.');
    }
}
