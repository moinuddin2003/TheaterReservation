<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SeatType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScreenRequest;
use App\Models\Cinema;
use App\Models\Screen;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScreenController extends Controller
{
    /**
     * List screens with their cinema, seat and showtime counts.
     */
    public function index(): View
    {
        $screens = Screen::query()
            ->with('cinema')
            ->withCount([
                'seats',
                'showtimes',
                'seats as disabled_seats_count' => fn ($query) => $query->where('is_disabled', true),
                'seats as premium_seats_count' => fn ($query) => $query->where('type', 'premium'),
            ])
            ->orderBy('name')
            ->get();

        return view('admin.screens.index', [
            'screens' => $screens,
        ]);
    }

    /**
     * Show the form to create a new screen.
     */
    public function create(): View
    {
        return view('admin.screens.create', [
            'cinemas' => Cinema::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Persist a new screen. Seats are not created until the grid is generated.
     */
    public function store(ScreenRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $screen = DB::transaction(function () use ($request, $auditLogger): Screen {
            $screen = Screen::create($request->validated());
            $auditLogger->log($request->user(), 'screen.created', $screen, [], $screen->only(['cinema_id', 'name', 'rows_count', 'seats_per_row', 'is_active']), $request->ip());

            return $screen;
        });

        return redirect()
            ->route('admin.screens.show', $screen)
            ->with('status', "Screen \"{$screen->name}\" was created. Generate its seats next.");
    }

    /**
     * Show a screen with its seat layout and showtime count.
     */
    public function show(Screen $screen): View
    {
        $screen->load('cinema')->loadCount([
            'showtimes',
            'seats',
            'seats as disabled_seats_count' => fn ($query) => $query->where('is_disabled', true),
            'seats as premium_seats_count' => fn ($query) => $query->where('type', 'premium'),
        ]);

        $seats = $screen->seats()
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get()
            ->groupBy('row_label');

        return view('admin.screens.show', [
            'screen' => $screen,
            'seatRows' => $seats,
            'seatTypes' => SeatType::cases(),
        ]);
    }

    /**
     * Show the form to edit an existing screen.
     */
    public function edit(Screen $screen): View
    {
        $screen->loadCount('showtimes');

        return view('admin.screens.edit', [
            'screen' => $screen,
            'cinemas' => Cinema::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Update an existing screen.
     */
    public function update(ScreenRequest $request, Screen $screen, AuditLogger $auditLogger): RedirectResponse
    {
        DB::transaction(function () use ($request, $screen, $auditLogger): void {
            $before = $screen->only(['cinema_id', 'name', 'rows_count', 'seats_per_row', 'is_active']);
            $screen->update($request->validated());
            $auditLogger->log($request->user(), 'screen.updated', $screen, $before, $screen->only(['cinema_id', 'name', 'rows_count', 'seats_per_row', 'is_active']), $request->ip());
        });

        return redirect()
            ->route('admin.screens.index')
            ->with('status', "Screen \"{$screen->name}\" was updated.");
    }

    /**
     * Delete a screen.
     *
     * Showtimes restrict deletion at the database level, so a screen with a
     * schedule cannot be removed. Seats detach via cascade.
     */
    public function destroy(Request $request, Screen $screen, AuditLogger $auditLogger): RedirectResponse
    {
        if ($screen->showtimes()->exists()) {
            return redirect()
                ->route('admin.screens.index')
                ->with('error', "Screen \"{$screen->name}\" cannot be deleted while it has showtimes.");
        }

        $name = $screen->name;
        DB::transaction(function () use ($request, $screen, $auditLogger): void {
            $before = [
                'attributes' => $screen->only(['cinema_id', 'name', 'rows_count', 'seats_per_row', 'is_active']),
                'seats' => $screen->seats()
                    ->orderBy('row_label')
                    ->orderBy('seat_number')
                    ->get()
                    ->map(fn ($seat): array => [
                        'id' => $seat->id,
                        'label' => $seat->label,
                        'type' => $seat->type->value,
                        'is_disabled' => $seat->is_disabled,
                    ])
                    ->all(),
            ];
            $screen->delete();
            $auditLogger->log($request->user(), 'screen.deleted', $screen, $before, [], $request->ip());
        });

        return redirect()
            ->route('admin.screens.index')
            ->with('status', "Screen \"{$name}\" was deleted.");
    }
}
