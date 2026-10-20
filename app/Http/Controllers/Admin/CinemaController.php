<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CinemaRequest;
use App\Models\Cinema;
use App\Models\Screen;
use App\Models\Seat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CinemaController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.cinema.index', [
            'cinemas' => Cinema::query()
                ->withCount('screens')
                ->with(['screens' => fn ($query) => $query
                    ->withCount([
                        'seats',
                        'showtimes',
                        'seats as disabled_seats_count' => fn ($seats) => $seats->where('is_disabled', true),
                        'seats as premium_seats_count' => fn ($seats) => $seats->where('type', 'premium'),
                    ])
                    ->orderBy('name')])
                ->orderBy('name')
                ->get(),
            'screens' => Screen::query()
                ->with('cinema')
                ->withCount([
                    'seats',
                    'showtimes',
                    'seats as disabled_seats_count' => fn ($query) => $query->where('is_disabled', true),
                    'seats as premium_seats_count' => fn ($query) => $query->where('type', 'premium'),
                ])
                ->orderBy('name')
                ->get(),
            'stats' => [
                'cinemas' => Cinema::query()->count(),
                'screens' => Screen::query()->count(),
                'seats' => Seat::query()->count(),
                'disabled_seats' => Seat::query()->where('is_disabled', true)->count(),
                'premium_seats' => Seat::query()->where('type', 'premium')->count(),
            ],
            'editingCinema' => $request->query('edit')
                ? Cinema::query()->find($request->query('edit'))
                : null,
            'deletingCinema' => $request->query('delete')
                ? Cinema::query()->withCount('screens')->find($request->query('delete'))
                : null,
        ]);
    }

    public function store(CinemaRequest $request): RedirectResponse
    {
        $cinema = Cinema::create($request->validated());

        return redirect()
            ->route('admin.cinema.index')
            ->with('status', "Cinema \"{$cinema->name}\" was created.");
    }

    public function update(CinemaRequest $request, Cinema $cinema): RedirectResponse
    {
        $cinema->update($request->validated());

        return redirect()
            ->route('admin.cinema.index')
            ->with('status', "Cinema \"{$cinema->name}\" was updated.");
    }

    public function destroy(Cinema $cinema): RedirectResponse
    {
        if ($cinema->screens()->exists()) {
            return redirect()
                ->route('admin.cinema.index', ['delete' => $cinema->getKey()])
                ->with('error', "Cinema \"{$cinema->name}\" cannot be deleted while it has screens.");
        }

        $name = $cinema->name;
        $cinema->delete();

        return redirect()
            ->route('admin.cinema.index')
            ->with('status', "Cinema \"{$name}\" was deleted.");
    }
}
