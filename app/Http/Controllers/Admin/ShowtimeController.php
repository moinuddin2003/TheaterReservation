<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CancelShowtimeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelShowtimeRequest;
use App\Http\Requests\ShowtimeRequest;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\Showtime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function __construct(private readonly CancelShowtimeAction $cancelShowtime) {}

    /**
     * List showtimes with optional date, screen and movie filters.
     */
    public function index(Request $request): View
    {
        $date = $request->query('date');
        $date = is_string($date) && $date !== '' ? $date : null;
        $screenId = $request->integer('screen_id') ?: null;
        $movieId = $request->integer('movie_id') ?: null;

        $showtimes = Showtime::query()
            ->with(['movie', 'screen'])
            ->withCount('bookings')
            ->when($date, fn ($query, $date) => $query->onDate($date))
            ->when($screenId, fn ($query, $id) => $query->where('screen_id', $id))
            ->when($movieId, fn ($query, $id) => $query->where('movie_id', $id))
            ->orderBy('starts_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.showtimes.index', [
            'showtimes' => $showtimes,
            'movies' => Movie::query()->orderBy('title')->get(),
            'screens' => Screen::query()->orderBy('name')->get(),
            'filters' => [
                'date' => $date,
                'screen_id' => $screenId,
                'movie_id' => $movieId,
            ],
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
