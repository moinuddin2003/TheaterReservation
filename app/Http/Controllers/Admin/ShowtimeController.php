<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CancelShowtimeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelShowtimeRequest;
use App\Http\Requests\ShowtimeRequest;
use App\Models\Showtime;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function __construct(private readonly CancelShowtimeAction $cancelShowtime) {}

    /**
     * List showtimes with optional date, screen and movie filters.
     */
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Showtimes',
            'pageSubtitle' => 'Manage the screening schedule for your movies and cinemas.',
            'breadcrumb' => 'Cinema',
            'icon' => 'calendar-clock',
            'emptyText' => 'Showtime management is coming soon.',
        ]);
    }

    /**
     * Show the form to schedule a new showtime.
     */
    public function create(): View
    {
        return view('admin.showtimes.create', [
            'movies' => Movie::query()->orderBy('title')->get(),
            'screens' => Screen::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Persist a new showtime.
     */
    public function store(ShowtimeRequest $request): RedirectResponse
    {
        $showtime = new Showtime([
            'movie_id' => $request->validated('movie_id'),
            'screen_id' => $request->validated('screen_id'),
            'starts_at' => $request->startsAt(),
            'ends_at' => $request->endsAt(),
        ]);
        $showtime->base_price_cents = $request->basePriceCents();
        $showtime->save();

        return redirect()
            ->route('admin.showtimes.index')
            ->with('status', 'Showtime scheduled.');
    }

    /**
     * Show the form to edit a scheduled showtime.
     */
    public function edit(Showtime $showtime): View
    {
        return view('admin.showtimes.edit', [
            'showtime' => $showtime->load(['movie', 'screen']),
            'movies' => Movie::query()->orderBy('title')->get(),
            'screens' => Screen::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update a scheduled showtime.
     */
    public function update(ShowtimeRequest $request, Showtime $showtime): RedirectResponse
    {
        if (! $showtime->isScheduled()) {
            return redirect()
                ->route('admin.showtimes.index')
                ->with('error', 'A cancelled showtime cannot be edited.');
        }

        $showtime->fill([
            'movie_id' => $request->validated('movie_id'),
            'screen_id' => $request->validated('screen_id'),
            'starts_at' => $request->startsAt(),
            'ends_at' => $request->endsAt(),
        ]);
        $showtime->base_price_cents = $request->basePriceCents();
        $showtime->save();

        return redirect()
            ->route('admin.showtimes.index')
            ->with('status', 'Showtime updated.');
    }

    /**
     * Show the cancellation impact before confirming (screen 30).
     */
    public function confirmCancel(Showtime $showtime): View
    {
        return view('admin.showtimes.cancel', [
            'showtime' => $showtime->load(['movie', 'screen']),
            'impact' => $this->cancelShowtime->impact($showtime),
        ]);
    }

    /**
     * Cancel a showtime, recording the reason and actor.
     */
    public function cancel(CancelShowtimeRequest $request, Showtime $showtime): RedirectResponse
    {
        if (! $showtime->isScheduled()) {
            return redirect()
                ->route('admin.showtimes.index')
                ->with('error', 'This showtime is already cancelled.');
        }

        if ($showtime->hasEnded()) {
            return redirect()
                ->route('admin.showtimes.index')
                ->with('error', 'A showtime that has already ended cannot be cancelled.');
        }

        $this->cancelShowtime->execute($showtime, $request->user(), $request->validated('cancel_reason'));

        return redirect()
            ->route('admin.showtimes.index')
            ->with('status', 'Showtime cancelled.');
    }
}
