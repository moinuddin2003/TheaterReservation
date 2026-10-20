<?php

namespace App\Http\Controllers\Admin;

use App\Actions\StoreMovieImageAction;
use App\Enums\MovieStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MovieRequest;
use App\Models\Genre;
use App\Models\Movie;
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

        $movies = Movie::query()
            ->with('genres')
            ->withCount('showtimes')
            ->search($search)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('release_date')
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('admin.movies.index', [
            'movies' => $movies,
            'statuses' => MovieStatus::cases(),
            'filters' => [
                'status' => $status?->value,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form to create a new movie.
     */
    public function create(): View
    {
        return view('admin.movies.create', [
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
        $poster = $this->storeImage->store($request->file('poster'));
        $backdrop = $this->storeImage->store($request->file('backdrop'));

        try {
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

        return view('admin.movies.edit', [
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

        $poster = $this->storeImage->store($request->file('poster'));
        $backdrop = $this->storeImage->store($request->file('backdrop'));

        try {
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

        if ($movie->poster_path !== $originalPoster) {
            $this->storeImage->delete($originalPoster);
        }

        if ($movie->backdrop_path !== $originalBackdrop) {
            $this->storeImage->delete($originalBackdrop);
        }

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
