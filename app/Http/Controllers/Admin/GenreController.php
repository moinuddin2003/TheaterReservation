<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * List all genres with the number of movies attached to each.
     */
    public function index(): View
    {
        $genres = Genre::query()
            ->withCount('movies')
            ->orderBy('name')
            ->get();

        return view('admin.genres.index', [
            'genres' => $genres,
        ]);
    }

    /**
     * Show the form to create a new genre.
     */
    public function create(): View
    {
        return view('admin.genres.create');
    }

    /**
     * Persist a new genre.
     */
    public function store(GenreRequest $request): RedirectResponse
    {
        $genre = Genre::create($request->validated());

        return redirect()
            ->route('admin.genres.index')
            ->with('status', "Genre \"{$genre->name}\" was created.");
    }

    /**
     * Show the form to edit an existing genre.
     */
    public function edit(Genre $genre): View
    {
        $genre->loadCount('movies');

        return view('admin.genres.edit', [
            'genre' => $genre,
        ]);
    }

    /**
     * Update an existing genre.
     */
    public function update(GenreRequest $request, Genre $genre): RedirectResponse
    {
        $genre->update($request->validated());

        return redirect()
            ->route('admin.genres.index')
            ->with('status', "Genre \"{$genre->name}\" was updated.");
    }

    /**
     * Delete a genre.
     *
     * Attached movies are detached via the genre_movie cascade; the UI warns
     * before this is reached when the genre is in use.
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        $name = $genre->name;
        $genre->delete();

        return redirect()
            ->route('admin.genres.index')
            ->with('status', "Genre \"{$name}\" was deleted.");
    }
}
