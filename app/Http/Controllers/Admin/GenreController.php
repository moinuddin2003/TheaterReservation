<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteGenreAction;
use App\Actions\Admin\SaveGenreAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenreController extends Controller
{
    public function index(Request $request): View
    {
        $query = Genre::query()->withCount('movies');
        if ($request->filled('q')) {
            $term = '%'.trim((string) $request->query('q')).'%';
            $query->where(fn ($builder) => $builder->where('name', 'like', $term)->orWhere('slug', 'like', $term));
        }
        $sort = in_array($request->query('sort'), ['name', 'slug', 'movies_count', 'created_at'], true)
            ? $request->query('sort')
            : 'name';

        return view('admin.genres.index', [
            'genres' => $query->orderBy($sort, $request->query('direction') === 'desc' ? 'desc' : 'asc')->paginate(15)->withQueryString(),
        ]);
    }

    public function store(GenreRequest $request, SaveGenreAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return to_route('admin.genres.index')->with('status', 'Genre created.');
    }

    public function update(GenreRequest $request, Genre $genre, SaveGenreAction $action): RedirectResponse
    {
        $action->execute($request->validated(), $genre);

        return to_route('admin.genres.index')->with('status', 'Genre updated.');
    }

    public function destroy(Genre $genre, DeleteGenreAction $action): RedirectResponse
    {
        $action->execute($genre);

        return to_route('admin.genres.index')->with('status', 'Genre deleted.');
    }
}
