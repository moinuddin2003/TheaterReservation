<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenreRequest;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * List all genres with the number of movies attached to each.
     */
    public function index(Request $request): View
    {
        $genres = Genre::query()
            ->withCount('movies')
            ->orderBy('name')
            ->get();

        $editingGenre = $request->query('edit')
            ? Genre::query()->find($request->query('edit'))
            : null;
        $deletingGenre = $request->query('delete')
            ? Genre::query()->with('movies:id,title')->find($request->query('delete'))
            : null;

        return $this->catalogView($genres, $editingGenre, $deletingGenre);
    }

    /**
     * @param  Collection<int, Genre>  $genres
     */
    private function catalogView(Collection $genres, ?Genre $editingGenre = null, ?Genre $deletingGenre = null): View
    {
        return view('admin.genres.index', [
            'genres' => $genres,
            'editingGenre' => $editingGenre,
            'deletingGenre' => $deletingGenre,
        ]);
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
    public function edit(Genre $genre): RedirectResponse
    {
        return redirect()->route('admin.genres.index', ['edit' => $genre->getKey()]);
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
        if ($genre->movies()->exists()) {
            return redirect()
                ->route('admin.genres.index', ['delete' => $genre->getKey()])
                ->with('error', "Genre \"{$genre->name}\" is assigned to movies. Remove it from those movies before deleting it.");
        }

        $name = $genre->name;
        $genre->delete();

        return redirect()
            ->route('admin.genres.index')
            ->with('status', "Genre \"{$name}\" was deleted.");
    }
}
