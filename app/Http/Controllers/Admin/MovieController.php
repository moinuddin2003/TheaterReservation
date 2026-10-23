<?php

namespace App\Http\Controllers\Admin;

use App\Actions\StoreMovieImageAction;
use App\Enums\MovieStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MovieRequest;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class MovieController extends Controller
{
    public function __construct(private readonly StoreMovieImageAction $storeImage) {}

    /**
     * List movies with optional status filter, search and genre/showtime counts.
     */
    public function index(Request $request): View
    {
        $status = MovieStatus::tryFrom((string) $request->query('status', ''));
        $search = $request->query('search');
        $search = is_string($search) ? $search : null;
        $genreId = filter_var($request->query('genre'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;
        $view = $request->query('view') === 'grid' ? 'grid' : 'list';

        $statusCounts = Movie::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as added_this_week', [now()->startOfWeek()])
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $totalMovies = (int) $statusCounts->sum('total');
        $addedThisWeek = (int) $statusCounts->sum('added_this_week');

        $movies = Movie::query()
            ->with('genres')
            ->withCount('showtimes')
            ->search($search)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($genreId, fn (Builder $query) => $query->whereHas(
                'genres',
                fn (Builder $genres) => $genres->whereKey($genreId),
            ))
            ->orderByDesc('release_date')
            ->orderBy('title')
            ->paginate(10)
            ->withQueryString();

        return view('admin.movies.index', [
            'movies' => $movies,
            'statuses' => MovieStatus::cases(),
            'genres' => Genre::query()->orderBy('name')->get(),
            'view' => $view,
            'stats' => [
                'total' => $totalMovies,
                'added_this_week' => $addedThisWeek,
                'now_showing' => (int) ($statusCounts[MovieStatus::NowShowing->value]->total ?? 0),
                'now_showing_added' => (int) ($statusCounts[MovieStatus::NowShowing->value]->added_this_week ?? 0),
                'coming_soon' => (int) ($statusCounts[MovieStatus::ComingSoon->value]->total ?? 0),
                'coming_soon_added' => (int) ($statusCounts[MovieStatus::ComingSoon->value]->added_this_week ?? 0),
                'archived' => (int) ($statusCounts[MovieStatus::Archived->value]->total ?? 0),
                'archived_added' => (int) ($statusCounts[MovieStatus::Archived->value]->added_this_week ?? 0),
            ],
            'filters' => [
                'status' => $status?->value,
                'search' => $search,
                'genre' => $genreId,
            ],
        ]);
    }

    /**
     * Show the form to create a new movie.
     */
    public function create(): View
    {
        return view('admin.movies.form', [
            'movie' => null,
            'genres' => Genre::query()->orderBy('name')->get(),
            'statuses' => MovieStatus::cases(),
        ]);
    }

    /**
     * Persist a new movie. Status is assigned explicitly (never mass assigned)
     * so the write can be routed through audit logging once it exists.
     */
    public function store(MovieRequest $request): RedirectResponse
    {
        $poster = null;
        $backdrop = null;

        try {
            $poster = $this->storeImage->store($request->file('poster'));
            $backdrop = $this->storeImage->store($request->file('backdrop'));

            $movie = DB::transaction(function () use ($request, $poster, $backdrop): Movie {
                $movie = new Movie($this->payload($request));

                if ($poster !== null) {
                    $movie->poster_path = $poster;
                }

                if ($backdrop !== null) {
                    $movie->backdrop_path = $backdrop;
                }

                $movie->status = MovieStatus::from((string) $request->validated('status'));
                $movie->save();

                $movie->genres()->sync($request->validated('genres', []));

                return $movie;
            });
        } catch (Throwable $exception) {
            $this->storeImage->delete($poster);
            $this->storeImage->delete($backdrop);

            throw $exception;
        }

        return redirect()
            ->route('admin.movies.index')
            ->with('status', "Movie \"{$movie->title}\" was created.");
    }

    /**
     * Show the form to edit an existing movie.
     */
    public function edit(Movie $movie): View
    {
        $movie->load('genres')->loadCount('showtimes');

        return view('admin.movies.form', [
            'movie' => $movie,
            'genres' => Genre::query()->orderBy('name')->get(),
            'statuses' => MovieStatus::cases(),
        ]);
    }

    /**
     * Update an existing movie.
     */
    public function update(MovieRequest $request, Movie $movie): RedirectResponse
    {
        $originalPoster = $movie->getOriginal('poster_path');
        $originalBackdrop = $movie->getOriginal('backdrop_path');
        $poster = null;
        $backdrop = null;

        try {
            $poster = $this->storeImage->store($request->file('poster'));
            $backdrop = $this->storeImage->store($request->file('backdrop'));

            DB::transaction(function () use ($request, $movie, $poster, $backdrop): void {
                $movie->fill($this->payload($request));

                if ($poster !== null) {
                    $movie->poster_path = $poster;
                }

                if ($backdrop !== null) {
                    $movie->backdrop_path = $backdrop;
                }

                $movie->status = MovieStatus::from((string) $request->validated('status'));
                $movie->save();

                $movie->genres()->sync($request->validated('genres', []));
            });
        } catch (Throwable $exception) {
            $this->storeImage->delete($poster);
            $this->storeImage->delete($backdrop);

            throw $exception;
        }

        $this->storeImage->delete($movie->poster_path !== $originalPoster ? $originalPoster : null);

        $this->storeImage->delete($movie->backdrop_path !== $originalBackdrop ? $originalBackdrop : null);

        return redirect()
            ->route('admin.movies.index')
            ->with('status', "Movie \"{$movie->title}\" was updated.");
    }

    /**
     * Validated movie attributes, with blank image paths normalised to null.
     *
     * @return array<string, mixed>
     */
    private function payload(MovieRequest $request): array
    {
        $data = $request->safe()->except(['status', 'genres', 'poster', 'backdrop']);

        foreach (['poster_path', 'backdrop_path'] as $key) {
            if (($data[$key] ?? null) === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }

    /**
     * Delete a movie.
     *
     * Showtimes restrict deletion at the database level, so a movie that is
     * still scheduled cannot be removed. Attached genres detach via cascade.
     */
    public function destroy(Movie $movie): RedirectResponse
    {
        if ($movie->showtimes()->exists()) {
            return redirect()
                ->route('admin.movies.index')
                ->with('error', "Movie \"{$movie->title}\" cannot be deleted while it has showtimes.");
        }

        $title = $movie->title;
        $poster = $movie->poster_path;
        $backdrop = $movie->backdrop_path;
        $movie->delete();

        $this->storeImage->delete($poster);
        $this->storeImage->delete($backdrop);

        return redirect()
            ->route('admin.movies.index')
            ->with('status', "Movie \"{$title}\" was deleted.");
    }
}
