<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Movie;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_column(BookingStatus::cases(), 'value'))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'movie' => ['nullable', 'integer', 'exists:movies,id'],
            'sort' => ['nullable', Rule::in(['created_at', 'status', 'total_cents', 'reference'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $bookings = Booking::query()
            ->with(['user', 'showtime.movie', 'payments'])
            ->withCount(['items as seats_count'])
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(function ($search) use ($q): void {
                $search->where('reference', 'like', '%'.$q.'%')
                    ->orWhereHas('user', fn ($users) => $users->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%'));
            }))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['movie'] ?? null, fn ($query, $movie) => $query->whereHas('showtime', fn ($showtimes) => $showtimes->where('movie_id', $movie)))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'movies' => Movie::query()->orderBy('title')->get(['id', 'title']),
            'timezone' => $this->timezone(),
        ]);
    }

    public function show(Booking $booking): View
    {
        $booking->load([
            'user',
            'showtime.movie',
            'showtime.screen.cinema',
            'items.seat',
            'payments.refunds',
            'refunds.initiator',
            'tickets.scans',
            'seatHolds',
        ]);
        $timeline = collect();
        foreach ($booking->seatHolds as $hold) {
            $timeline->push(['at' => $hold->created_at, 'label' => 'Seat hold created', 'detail' => $hold->seat_id ? 'Seat #'.$hold->seat_id : null]);
        }
        foreach ($booking->payments as $payment) {
            $timeline->push(['at' => $payment->created_at, 'label' => 'Payment '.$payment->status->value, 'detail' => '$'.number_format($payment->amount_cents / 100, 2)]);
            if ($payment->paid_at) {
                $timeline->push(['at' => $payment->paid_at, 'label' => 'Payment confirmed', 'detail' => $payment->gateway]);
            }
        }
        foreach ($booking->tickets as $ticket) {
            $timeline->push(['at' => $ticket->created_at, 'label' => 'Ticket issued', 'detail' => $ticket->ticket_number]);
        }
        foreach ($booking->refunds as $refund) {
            $timeline->push(['at' => $refund->created_at, 'label' => 'Refund '.$refund->status->value, 'detail' => '$'.number_format($refund->amount_cents / 100, 2)]);
        }
        if ($booking->cancelled_at) {
            $timeline->push(['at' => $booking->cancelled_at, 'label' => 'Booking cancelled', 'detail' => $booking->cancel_reason]);
        }
        $bookingAudit = class_basename($booking);
        foreach (AuditLog::query()->where('auditable_type', $bookingAudit)->where('auditable_id', $booking->id)->get() as $log) {
            $timeline->push(['at' => $log->created_at, 'label' => str($log->action)->replace('.', ' ')->headline()->toString(), 'detail' => null]);
        }
        $timeline = $timeline->sortBy(fn (array $entry) => $entry['at']?->getTimestamp() ?? 0)->values();

        return view('admin.bookings.show', [
            'booking' => $booking,
            'timeline' => $timeline,
            'timezone' => $this->timezone(),
            'canCancel' => in_array($booking->status, [BookingStatus::PendingPayment, BookingStatus::Confirmed], true),
            'canRefund' => $booking->payments->contains(fn ($payment) => $payment->status->value === 'succeeded'),
        ]);
    }

    private function timezone(): string
    {
        $timezone = (string) Setting::query()->whereKey('timezone')->value('value');

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : config('app.timezone', 'UTC');
    }
}
