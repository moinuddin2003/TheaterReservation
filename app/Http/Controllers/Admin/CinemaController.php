<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CinemaRequest;
use App\Models\Cinema;
use App\Models\Screen;
use App\Models\Seat;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function store(CinemaRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $cinema = DB::transaction(function () use ($request, $auditLogger): Cinema {
            $cinema = Cinema::create($request->validated());
            $auditLogger->log($request->user(), 'cinema.created', $cinema, [], $cinema->only(['name', 'address', 'phone']), $request->ip());

            return $cinema;
        });

        return redirect()
            ->route('admin.cinema.index')
            ->with('status', "Cinema \"{$cinema->name}\" was created.");
    }

    public function update(CinemaRequest $request, Cinema $cinema, AuditLogger $auditLogger): RedirectResponse
    {
        DB::transaction(function () use ($request, $cinema, $auditLogger): void {
            $before = $cinema->only(['name', 'address', 'phone']);
            $cinema->update($request->validated());
            $auditLogger->log($request->user(), 'cinema.updated', $cinema, $before, $cinema->only(['name', 'address', 'phone']), $request->ip());
        });

        return redirect()
            ->route('admin.cinema.index')
            ->with('status', "Cinema \"{$cinema->name}\" was updated.");
    }

    public function destroy(Request $request, Cinema $cinema, AuditLogger $auditLogger): RedirectResponse
    {
        if ($cinema->screens()->exists()) {
            return redirect()
                ->route('admin.cinema.index', ['delete' => $cinema->getKey()])
                ->with('error', "Cinema \"{$cinema->name}\" cannot be deleted while it has screens.");
        }

        $name = $cinema->name;
        DB::transaction(function () use ($request, $cinema, $auditLogger): void {
            $before = $cinema->only(['name', 'address', 'phone']);
            $cinema->delete();
            $auditLogger->log($request->user(), 'cinema.deleted', $cinema, $before, [], $request->ip());
        });

        return redirect()
            ->route('admin.cinema.index')
            ->with('status', "Cinema \"{$name}\" was deleted.");
    }
}
