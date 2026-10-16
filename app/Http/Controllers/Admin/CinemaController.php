<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateCinemaAction;
use App\Actions\Admin\DeleteCinemaAction;
use App\Actions\Admin\UpdateCinemaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCinemaRequest;
use App\Http\Requests\UpdateCinemaRequest;
use App\Models\Cinema;
use App\Models\Screen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CinemaController extends Controller
{
    public function store(CreateCinemaRequest $request, CreateCinemaAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return to_route('admin.cinema.index')->with('status', 'Cinema profile created.');
    }

    public function index(Request $request): View
    {
        $screenQuery = Screen::query()->with('cinema')
            ->withCount('seats')
            ->withCount(['showtimes as upcoming_showtimes_count' => fn ($showtimes) => $showtimes
                ->where('status', 'scheduled')
                ->where('starts_at', '>=', now())]);
        if ($request->filled('q')) {
            $search = '%'.trim((string) $request->query('q')).'%';
            $screenQuery->where(fn ($query) => $query->where('name', 'like', $search)
                ->orWhereHas('cinema', fn ($cinemas) => $cinemas->where('name', 'like', $search)));
        }
        if ($request->filled('cinema_id')) {
            $screenQuery->where('cinema_id', $request->integer('cinema_id'));
        }
        if (in_array($request->query('active'), ['active', 'inactive'], true)) {
            $screenQuery->where('is_active', $request->query('active') === 'active');
        }
        $screenSort = in_array($request->query('sort'), ['name', 'seats_count', 'upcoming_showtimes_count', 'created_at'], true)
            ? $request->query('sort')
            : 'name';

        return view('admin.cinema.index', [
            'cinemas' => Cinema::query()
                ->withCount('screens')
                ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.trim((string) $request->query('q')).'%'))
                ->orderBy('name')
                ->get(),
            'screens' => $screenQuery
                ->orderBy($screenSort, $request->query('direction') === 'desc' ? 'desc' : 'asc')
                ->paginate(12)
                ->withQueryString(),
            'screenCount' => Screen::query()->count(),
        ]);
    }

    public function update(UpdateCinemaRequest $request, Cinema $cinema, UpdateCinemaAction $action): RedirectResponse
    {
        $action->execute($cinema, $request->validated());

        return to_route('admin.cinema.index')->with('status', 'Cinema profile updated.');
    }

    public function destroy(Cinema $cinema, DeleteCinemaAction $action): RedirectResponse
    {
        $action->execute($cinema);

        return to_route('admin.cinema.index')->with('status', 'Cinema profile deleted.');
    }
}
