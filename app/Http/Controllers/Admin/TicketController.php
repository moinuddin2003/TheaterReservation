<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\ScanResult;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['tickets', 'scans'])],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_column(TicketStatus::cases(), 'value'))],
            'result' => ['nullable', Rule::in(array_column(ScanResult::cases(), 'value'))],
            'staff' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sort' => ['nullable', Rule::in(['created_at', 'status', 'ticket_number'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $tab = $filters['tab'] ?? 'tickets';
        $tickets = collect();
        $scans = collect();
        if ($tab === 'tickets') {
            $tickets = Ticket::query()->with(['booking.user', 'bookingItem.seat', 'showtime.movie', 'showtime.screen.cinema'])
                ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                    ->where('ticket_number', 'like', '%'.$search.'%')
                    ->orWhere('token', 'like', '%'.$search.'%')
                    ->orWhereHas('booking', fn ($bookings) => $bookings->where('reference', 'like', '%'.$search.'%'))))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
                ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
                ->withCount('scans')
                ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
                ->paginate(15)->withQueryString();
        } else {
            $scans = TicketScan::query()->with(['ticket.booking', 'ticket.showtime.movie', 'scanner'])
                ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                    ->where('message', 'like', '%'.$search.'%')
                    ->orWhereHas('ticket', fn ($tickets) => $tickets->where('ticket_number', 'like', '%'.$search.'%'))))
                ->when($filters['result'] ?? null, fn ($query, $result) => $query->where('result', $result))
                ->when($filters['staff'] ?? null, fn ($query, $staff) => $query->where('scanned_by', $staff))
                ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('scanned_at', '>=', $from))
                ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('scanned_at', '<=', $to))
                ->orderByDesc('scanned_at')->paginate(15)->withQueryString();
        }

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'scans' => $scans,
            'tab' => $tab,
            'staffUsers' => User::query()->where('role', Role::Staff->value)->orderBy('name')->get(['id', 'name']),
            'timezone' => $this->timezone(),
        ]);
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['booking.user', 'bookingItem.seat', 'showtime.movie', 'showtime.screen.cinema', 'scans.scanner']);

        return view('admin.tickets.show', ['ticket' => $ticket, 'timezone' => $this->timezone()]);
    }

    private function timezone(): string
    {
        $timezone = (string) Setting::query()->whereKey('timezone')->value('value');

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : config('app.timezone', 'UTC');
    }
}
