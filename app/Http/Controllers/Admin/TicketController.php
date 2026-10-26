<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\ScanResult;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketIndexRequest;
use App\Models\Movie;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(TicketIndexRequest $request): View
    {
        $filters = $request->filters();
        $timezone = $request->timezone();
        $from = $filters['from'] ? Carbon::parse($filters['from'], $timezone)->startOfDay()->utc() : null;
        $to = $filters['to'] ? Carbon::parse($filters['to'], $timezone)->addDay()->startOfDay()->utc() : null;
        $todayStart = Carbon::now($timezone)->startOfDay()->utc();
        $tomorrowStart = $todayStart->copy()->addDay();
        $tickets = $this->filteredTickets($filters, $from, $to);
        $scans = $this->filteredScans($filters, $from, $to);

        return view('admin.tickets.index', [
            'filters' => $filters,
            'timezone' => $timezone,
            'movies' => Movie::query()->orderBy('title')->get(['id', 'title']),
            'staff' => User::query()->where('role', Role::Staff)->orderBy('name')->get(['id', 'name']),
            'tickets' => (clone $tickets)
                ->with(['booking.user', 'bookingItem', 'showtime.movie', 'showtime.screen'])
                ->withCount('scans')
                ->orderByDesc('tickets.created_at')
                ->orderByDesc('tickets.id')
                ->paginate(10, ['*'], 'tickets_page')
                ->withQueryString(),
            'scans' => (clone $scans)
                ->with([
                    'ticket.booking.user',
                    'ticket.bookingItem',
                    'ticket.showtime.movie',
                    'showtime.movie',
                    'showtime.screen',
                    'scannedBy',
                ])
                ->orderByDesc('ticket_scans.scanned_at')
                ->orderByDesc('ticket_scans.id')
                ->paginate(10, ['*'], 'scans_page')
                ->withQueryString(),
            'counts' => [
                'tickets' => Ticket::query()->count(),
                'scans' => TicketScan::query()->count(),
            ],
            'metrics' => [
                'issued_today' => Ticket::query()
                    ->where('created_at', '>=', $todayStart)
                    ->where('created_at', '<', $tomorrowStart)
                    ->count(),
                'scanned_today' => TicketScan::query()
                    ->where('result', ScanResult::Admitted)
                    ->where('scanned_at', '>=', $todayStart)
                    ->where('scanned_at', '<', $tomorrowStart)
                    ->count(),
                'duplicates_today' => TicketScan::query()
                    ->where('result', ScanResult::AlreadyUsed)
                    ->where('scanned_at', '>=', $todayStart)
                    ->where('scanned_at', '<', $tomorrowStart)
                    ->count(),
                'voided' => Ticket::query()
                    ->whereIn('status', [TicketStatus::Void, TicketStatus::Voided])
                    ->count(),
            ],
        ]);
    }

    /**
     * @param  array{tab: string, search: string, status: string|null, result: string|null, movie_id: int|null, staff_id: int|null, from: string|null, to: string|null}  $filters
     * @return Builder<Ticket>
     */
    private function filteredTickets(array $filters, ?Carbon $from, ?Carbon $to): Builder
    {
        return Ticket::query()
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('tickets.status', $status))
            ->when($filters['movie_id'], fn (Builder $query, int $movieId) => $query->whereHas('showtime', fn (Builder $showtimes) => $showtimes->where('movie_id', $movieId)))
            ->when($from, fn (Builder $query, Carbon $from) => $query->where('tickets.created_at', '>=', $from))
            ->when($to, fn (Builder $query, Carbon $to) => $query->where('tickets.created_at', '<', $to))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('tickets.ticket_number', 'like', '%'.$search.'%')
                        ->orWhereHas('booking', fn (Builder $bookings) => $bookings
                            ->where('reference', 'like', '%'.$search.'%')
                            ->orWhereHas('user', fn (Builder $users) => $users
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%')));
                });
            });
    }

    /**
     * @param  array{tab: string, search: string, status: string|null, result: string|null, movie_id: int|null, staff_id: int|null, from: string|null, to: string|null}  $filters
     * @return Builder<TicketScan>
     */
    private function filteredScans(array $filters, ?Carbon $from, ?Carbon $to): Builder
    {
        return TicketScan::query()
            ->when($filters['result'], fn (Builder $query, string $result) => $query->where('ticket_scans.result', $result))
            ->when($filters['staff_id'], fn (Builder $query, int $staffId) => $query->where('ticket_scans.scanned_by', $staffId))
            ->when($filters['movie_id'], fn (Builder $query, int $movieId) => $query->whereHas('showtime', fn (Builder $showtimes) => $showtimes->where('movie_id', $movieId)))
            ->when($from, fn (Builder $query, Carbon $from) => $query->where('ticket_scans.scanned_at', '>=', $from))
            ->when($to, fn (Builder $query, Carbon $to) => $query->where('ticket_scans.scanned_at', '<', $to))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('ticket', fn (Builder $tickets) => $tickets
                        ->where('ticket_number', 'like', '%'.$search.'%')
                        ->orWhereHas('booking', fn (Builder $bookings) => $bookings
                            ->where('reference', 'like', '%'.$search.'%')
                            ->orWhereHas('user', fn (Builder $users) => $users
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%'))))
                        ->orWhereHas('scannedBy', fn (Builder $users) => $users->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('showtime.movie', fn (Builder $movies) => $movies->where('title', 'like', '%'.$search.'%'));
                });
            });
    }
}
