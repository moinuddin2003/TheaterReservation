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
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function index(Request $request): View
    {
        $dateValue = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:100'],
            'movie_id' => ['nullable', 'integer', 'exists:movies,id'],
            'screen_id' => ['nullable', 'integer', 'exists:screens,id'],
            'status' => ['nullable', Rule::in(['scheduled', 'cancelled', 'completed'])],
            'sort' => ['nullable', Rule::in(['starts_at', 'base_price_cents', 'status'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $timezone = $this->timezone();
        $date = isset($dateValue['date']) ? Carbon::parse($dateValue['date'], $timezone) : Carbon::now($timezone);
        $start = $date->copy()->startOfWeek();
        $end = $start->copy()->addWeek();
        $startUtc = $start->copy()->utc();
        $endUtc = $end->copy()->utc();
        $query = Showtime::query()->with(['movie', 'screen' => fn ($screen) => $screen->withCount('seats')])
            ->withCount([
                'bookingItems as sold_seats_count' => fn (Builder $items) => $items
                    ->where('status', 'active')
                    ->whereHas('booking', fn (Builder $bookings) => $bookings->where('status', 'confirmed')),
                'seatHolds as held_seats_count' => fn (Builder $holds) => $holds
                    ->where('status', 'active')
                    ->where('expires_at', '>', now()),
            ])
            ->when($dateValue['movie_id'] ?? null, fn ($query, $movie) => $query->where('movie_id', $movie))
            ->when($dateValue['screen_id'] ?? null, fn ($query, $screen) => $query->where('screen_id', $screen))
            ->when($dateValue['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($dateValue['q'] ?? null, fn ($query, $term) => $query->whereHas('movie', fn ($movies) => $movies->where('title', 'like', '%'.$term.'%')))
            ->where('starts_at', '>=', $startUtc)
            ->where('starts_at', '<', $endUtc);
        $sort = $dateValue['sort'] ?? 'starts_at';
        $direction = $dateValue['direction'] ?? 'asc';

        return view('admin.showtimes.index', [
            'showtimes' => $query->orderBy($sort, $direction)->paginate(20)->withQueryString(),
            'movies' => Movie::query()->orderBy('title')->get(['id', 'title']),
            'screens' => Screen::query()->orderBy('name')->get(['id', 'name']),
            'selectedDate' => $date,
            'weekDays' => collect(range(0, 6))->map(fn (int $offset) => $start->copy()->addDays($offset)),
            'salesCloseMinutes' => (int) Setting::query()->whereKey('sales_close_minutes')->value('value') ?: 15,
            'timezone' => $timezone,
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
        $timezone = $this->timezone();
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
        $todayStart = Carbon::now($timezone)->startOfDay()->utc();
        $tomorrowStart = $todayStart->copy()->addDay();
        $scannedCount = TicketScan::query()->where('showtime_id', $showtime->id)
            ->where('scanned_at', '>=', $todayStart)
            ->where('scanned_at', '<', $tomorrowStart)
            ->count();
        $salesCents = (int) DB::table('payments')
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->where('bookings.showtime_id', $showtime->id)
            ->where('payments.status', PaymentStatus::Succeeded->value)
            ->sum('payments.amount_cents');

        $salesCloseMinutes = (int) Setting::query()->whereKey('sales_close_minutes')->value('value') ?: 15;

        return view('admin.showtimes.show', compact('showtime', 'seats', 'scannedCount', 'salesCents', 'timezone', 'salesCloseMinutes'));
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

        return view('admin.showtimes.form', compact('showtime', 'movies', 'screens') + ['timezone' => $this->timezone()]);
    }

    private function timezone(): string
    {
        $timezone = (string) Setting::query()->whereKey('timezone')->value('value');

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : config('app.timezone', 'UTC');
    }
}
