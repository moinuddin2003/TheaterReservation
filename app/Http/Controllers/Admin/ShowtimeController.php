<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CancelShowtimeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelShowtimeRequest;
use App\Http\Requests\ShowtimeIndexRequest;
use App\Http\Requests\ShowtimeRequest;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\Showtime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function __construct(private readonly CancelShowtimeAction $cancelShowtime) {}

    /**
     * List showtimes with optional date, screen and movie filters.
     */
    public function index(ShowtimeIndexRequest $request): View
    {
        $selectedDate = $request->selectedDate();
        $weekStart = $request->weekStart();
        $weekEnd = $request->weekEnd();
        $weekEndExclusive = $weekEnd->copy()->addDay()->startOfDay();
        $weekDays = collect(range(0, 6))
            ->map(fn (int $offset) => $weekStart->copy()->addDays($offset));
        $dayRanges = $weekDays->map(function (Carbon $day): array {
            $start = $day->copy()->startOfDay();

            return [
                'start' => $start->copy()->utc(),
                'end' => $start->copy()->addDay()->startOfDay()->utc(),
            ];
        });
        $caseClauses = [];
        $caseBindings = [];
        foreach ($dayRanges as $dayIndex => $range) {
            $caseClauses[] = 'WHEN starts_at >= ? AND starts_at < ? THEN ?';
            $caseBindings[] = $range['start']->toDateTimeString();
            $caseBindings[] = $range['end']->toDateTimeString();
            $caseBindings[] = $dayIndex;
        }

        $showtimeCounts = $this->applyFilters(Showtime::query(), $request)
            ->where('starts_at', '>=', $weekStart->copy()->startOfDay()->utc())
            ->where('starts_at', '<', $weekEndExclusive->copy()->setTimezone($request->timezone())->utc())
            ->selectRaw('CASE '.implode(' ', $caseClauses).' ELSE NULL END AS local_day, COUNT(*) AS total', $caseBindings)
            ->groupBy('local_day')
            ->pluck('total', 'local_day');

        $selectedDayStartUtc = $selectedDate->copy()->startOfDay()->utc();
        $showtimes = $this->applyFilters(Showtime::query()->with(['movie', 'screen.cinema']), $request)
            ->where('starts_at', '>=', $selectedDayStartUtc)
            ->orderBy('starts_at')
            ->orderBy(Screen::query()->select('name')->whereColumn('screens.id', 'showtimes.screen_id'))
            ->paginate(10)
            ->withQueryString();

        return view('admin.showtimes.index', [
            'showtimes' => $showtimes,
            'selectedDate' => $selectedDate,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'weekDays' => $weekDays,
            'showtimeCounts' => $showtimeCounts,
            'timezone' => $request->timezone(),
            'now' => Carbon::now($request->timezone()),
            'movies' => Movie::query()->orderBy('title')->get(['id', 'title']),
            'cinemas' => Cinema::query()->orderBy('name')->get(['id', 'name']),
            'screens' => Screen::query()->with('cinema')->orderBy('name')->get(),
            'filters' => [
                'search' => $request->search() ?? '',
                'cinema_id' => $request->cinemaId(),
                'screen_id' => $request->screenId(),
            ],
        ]);
    }

    /**
     * Apply the schedule's shared search and location filters.
     *
     * @param  Builder<Showtime>  $query
     * @return Builder<Showtime>
     */
    private function applyFilters(Builder $query, ShowtimeIndexRequest $request): Builder
    {
        return $query
            ->when($request->search(), function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('movie', fn (Builder $movies) => $movies->where('title', 'like', '%'.$search.'%'))
                        ->orWhereHas('screen', function (Builder $screens) use ($search): void {
                            $screens->where('name', 'like', '%'.$search.'%')
                                ->orWhereHas('cinema', fn (Builder $cinemas) => $cinemas->where('name', 'like', '%'.$search.'%'));
                        });
                });
            })
            ->when($request->cinemaId(), fn (Builder $query, int $cinemaId) => $query->whereHas(
                'screen.cinema',
                fn (Builder $cinemas) => $cinemas->whereKey($cinemaId),
            ))
            ->when($request->screenId(), fn (Builder $query, int $screenId) => $query->where('screen_id', $screenId));
    }

    /**
     * Show the form to schedule a new showtime.
     */
    public function create(Request $request): View
    {
        $startDate = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ])['date'] ?? null;

        return view('admin.showtimes.form', [
            'showtime' => null,
            'startDate' => $startDate,
            'movies' => Movie::query()->orderBy('title')->get(),
            'screens' => Screen::query()->with('cinema')->where('is_active', true)->orderBy('name')->get(),
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
        return view('admin.showtimes.form', [
            'showtime' => $showtime->load(['movie', 'screen']),
            'startDate' => null,
            'movies' => Movie::query()->orderBy('title')->get(),
            'screens' => Screen::query()
                ->with('cinema')
                ->where(fn ($query) => $query->where('is_active', true)->orWhereKey($showtime->screen_id))
                ->orderBy('name')
                ->get(),
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
        abort_unless($showtime->isScheduled() && ! $showtime->hasEnded(), 404);

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
        $impact = $this->cancelShowtime->execute(
            $showtime,
            $request->user(),
            $request->validated('cancel_reason'),
        );

        if ($impact === null) {
            return redirect()
                ->route('admin.showtimes.index')
                ->with('error', 'This showtime is no longer available for cancellation.');
        }

        $bookingCount = $impact['confirmed'] + $impact['pending_payment'];

        return redirect()
            ->route('admin.showtimes.index')
            ->with(
                'status',
                "Showtime cancelled. {$bookingCount} bookings cancelled, {$impact['tickets']} tickets voided and {$impact['active_holds']} seat holds released. No refunds were processed.",
            );
    }
}
