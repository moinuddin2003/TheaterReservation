<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScreenRequest;
use App\Models\Cinema;
use App\Models\Screen;
use Illuminate\Http\RedirectResponse;
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
            ->withCount(['seats', 'showtimes'])
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
    public function store(ScreenRequest $request): RedirectResponse
    {
        $screen = Screen::create($request->validated());

        return redirect()
            ->route('admin.screens.show', $screen)
            ->with('status', "Screen \"{$screen->name}\" was created. Generate its seats next.");
    }

    /**
     * Show a screen with its seat layout and showtime count.
     */
    public function show(Screen $screen): View
    {
        $screen->load('cinema')->loadCount('showtimes');

        $seats = $screen->seats()
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get()
            ->groupBy('row_label');

        return view('admin.screens.show', [
            'screen' => $screen,
            'seatRows' => $seats,
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
    public function update(ScreenRequest $request, Screen $screen): RedirectResponse
    {
        $screen->update($request->validated());

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
    public function destroy(Screen $screen): RedirectResponse
    {
        if ($screen->showtimes()->exists()) {
            return redirect()
                ->route('admin.screens.index')
                ->with('error', "Screen \"{$screen->name}\" cannot be deleted while it has showtimes.");
        }

        $name = $screen->name;
        $screen->delete();

        return redirect()
            ->route('admin.screens.index')
            ->with('status', "Screen \"{$name}\" was deleted.");
    }
}
