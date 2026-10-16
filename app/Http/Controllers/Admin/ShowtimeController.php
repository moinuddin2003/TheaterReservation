<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CancelShowtimeAction;
use App\Actions\Admin\SaveShowtimeAction;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelShowtimeRequest;
use App\Http\Requests\StoreShowtimeRequest;
use App\Models\BookingItem;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\SeatHold;
use App\Models\Setting;
use App\Models\Showtime;
use App\Models\TicketScan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function index(Request $request): View
    {
        $dateValue = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date'] ?? null;
        $date = $dateValue ? Carbon::parse($dateValue) : today();
        $start = $date->copy()->startOfWeek();
        $end = $start->copy()->addWeek();
        $query = Showtime::query()->with(['movie', 'screen' => fn ($screen) => $screen->withCount('seats')])
            ->withCount([
                'bookingItems as sold_seats_count' => fn (Builder $items) => $items
                    ->where('status', 'active')
                    ->whereHas('booking', fn (Builder $bookings) => $bookings->where('status', 'confirmed')),
                'seatHolds as held_seats_count' => fn (Builder $holds) => $holds
                    ->where('status', 'active')
                    ->where('expires_at', '>', now()),
            ]);
        if ($request->filled('movie_id')) {
            $query->where('movie_id', $request->integer('movie_id'));
        }
        if ($request->filled('screen_id')) {
            $query->where('screen_id', $request->integer('screen_id'));
        }
        if (in_array($request->query('status'), ['scheduled', 'cancelled', 'completed'], true)) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('date')) {
            $query->where('starts_at', '>=', $start)->where('starts_at', '<', $end);
        }

        return view('admin.showtimes.index', [
            'showtimes' => $query->orderBy('starts_at')->paginate(20)->withQueryString(),
            'movies' => Movie::query()->orderBy('title')->get(['id', 'title']),
            'screens' => Screen::query()->orderBy('name')->get(['id', 'name']),
            'selectedDate' => $date,
            'weekDays' => collect(range(0, 6))->map(fn (int $offset) => $start->copy()->addDays($offset)),
            'salesCloseMinutes' => (int) Setting::query()->whereKey('sales_close_minutes')->value('value') ?: 15,
        ]);
    }

    public function create(): View
    {
        return $this->form(new Showtime);
    }

    public function store(StoreShowtimeRequest $request, SaveShowtimeAction $action): RedirectResponse
    {
        $showtime = $action->execute($request->validated());

        return to_route('admin.showtimes.show', $showtime)->with('status', 'Showtime created.');
    }

    public function show(Showtime $showtime): View
    {
        $showtime->load(['movie', 'screen.seats', 'tickets']);
        $bookingItems = BookingItem::query()
            ->with('booking')
            ->where('showtime_id', $showtime->id)
            ->where('status', 'active')
            ->get()
            ->keyBy('seat_id');
        $holds = SeatHold::query()
            ->where('showtime_id', $showtime->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->get()
            ->keyBy('seat_id');
        $seats = $showtime->screen->seats->map(function ($seat) use ($bookingItems, $holds) {
            $item = $bookingItems->get($seat->id);
            $state = $seat->is_disabled
                ? 'disabled'
                : ($item?->booking?->status?->value === 'confirmed'
                    ? 'booked'
                    : ($holds->has($seat->id) ? 'held' : 'available'));
            $seat->setAttribute('occupancy_state', $state);

            return $seat;
        });
        $scannedCount = TicketScan::query()->where('showtime_id', $showtime->id)->whereDate('scanned_at', today())->count();
        $salesCents = (int) DB::table('payments')
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->where('bookings.showtime_id', $showtime->id)
            ->where('payments.status', PaymentStatus::Succeeded->value)
            ->sum('payments.amount_cents');

        return view('admin.showtimes.show', compact('showtime', 'seats', 'scannedCount', 'salesCents'));
    }

    public function edit(Showtime $showtime): View
    {
        return $this->form($showtime);
    }

    public function update(StoreShowtimeRequest $request, Showtime $showtime, SaveShowtimeAction $action): RedirectResponse
    {
        $showtime = $action->execute($request->validated(), $showtime);

        return to_route('admin.showtimes.show', $showtime)->with('status', 'Showtime updated.');
    }

    public function cancel(CancelShowtimeRequest $request, Showtime $showtime, CancelShowtimeAction $action): RedirectResponse
    {
        $action->execute($showtime, $request->validated('reason'), $request->user());

        return back()->with('status', 'Showtime cancelled.');
    }

    private function form(Showtime $showtime): View
    {
        $movies = Movie::query()->where('status', '!=', 'archived')->orderBy('title')->get(['id', 'title', 'duration_minutes']);
        $screens = Screen::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.showtimes.form', compact('showtime', 'movies', 'screens'));
    }
}
