<?php

namespace App\Services\Staff;

use App\Contracts\Staff\StaffData;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Cinema;
use App\Models\Showtime;
use App\Models\TicketScan;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reads the staff screens' data from the database (see StaffData for the shapes).
 * Times are in the app timezone (config/app.php), the same as showtimes.starts_at.
 */
class EloquentStaffData implements StaffData
{
    public function now(): DateTimeImmutable
    {
        return now()->toDateTimeImmutable();
    }

    public function venue(): string
    {
        return Cinema::query()->value('name') ?? config('cinema.venue');
    }

    public function shows(DateTimeImmutable $day): array
    {
        return $this->showsOn($day)
            ->with(['movie:id,title', 'screen:id,name'])
            ->withCount([
                'tickets as sold_count' => fn (Builder $q) => $q->whereIn('status', [TicketStatus::Valid->value, TicketStatus::Used->value]),
                'tickets as scanned_count' => fn (Builder $q) => $q->where('status', TicketStatus::Used->value),
            ])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Showtime $show): array => [
                'id' => $show->id,
                'movie' => $show->movie->title,
                'screen' => $show->screen->name,
                'starts_at' => $show->starts_at->format('Y-m-d H:i'),
                'sold' => $show->sold_count,
                'scanned' => $show->scanned_count,
            ])
            ->all();
    }

    public function rosterBookings(DateTimeImmutable $day): array
    {
        return Booking::query()
            ->confirmed()
            ->whereIn('showtime_id', $this->showsOn($day)->select('id'))
            ->with(['user:id,name', 'items' => fn ($q) => $q->where('status', 'active')->orderBy('id'), 'tickets:id,booking_id,status'])
            ->orderByDesc('confirmed_at')
            ->get()
            ->map(fn (Booking $booking): array => [
                'ref' => $booking->reference,
                'name' => $booking->user->name,
                'show_id' => $booking->showtime_id,
                'seats' => $booking->items->pluck('seat_label')->all(),
                'scanned' => $booking->tickets->filter(fn ($t) => $t->status === TicketStatus::Used)->count(),
            ])
            ->all();
    }

    public function recentAttempts(DateTimeImmutable $day): array
    {
        return TicketScan::query()
            ->whereIn('showtime_id', $this->showsOn($day)->select('id'))
            ->with('ticket.bookingItem:id,seat_label')
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('showtime_id')
            ->map(fn ($scans) => $scans->take(ScanScreenService::ATTEMPT_LIMIT)->map(fn (TicketScan $scan): array => [
                'time' => $scan->scanned_at->format('H:i:s'),
                'result' => $scan->result->value,
                'seat' => $scan->ticket?->bookingItem?->seat_label,
                'ticket' => $scan->ticket?->ticket_number,
                'used_at' => $scan->result->value === 'already_used' ? $scan->ticket?->used_at?->format('H:i') : null,
            ])->values()->all())
            ->all();
    }

    public function findBooking(string $reference): ?array
    {
        $booking = Booking::query()
            ->confirmed()
            ->where('reference', $reference)
            ->with(['user:id,name', 'showtime.movie:id,title', 'showtime.screen:id,name', 'tickets.bookingItem'])
            ->first();

        if (! $booking) {
            return null;
        }

        return [
            'ref' => $booking->reference,
            'name' => $booking->user->name,
            'show' => [
                'id' => $booking->showtime->id,
                'movie' => $booking->showtime->movie->title,
                'screen' => $booking->showtime->screen->name,
                'starts_at' => $booking->showtime->starts_at->format('Y-m-d H:i'),
            ],
            'tickets' => $booking->tickets->sortBy('id')->map(fn ($ticket): array => [
                'seat' => $ticket->bookingItem->seat_label,
                'seat_type' => ucfirst($ticket->bookingItem->seat_type->value),
                'code' => $ticket->ticket_number,
                'used_at' => $ticket->used_at?->toDateTimeImmutable(),
            ])->values()->all(),
        ];
    }

    /**
     * Scheduled (not cancelled) shows starting on the given day.
     *
     * @return Builder<Showtime>
     */
    private function showsOn(DateTimeImmutable $day): Builder
    {
        return Showtime::query()->scheduled()->onDate($day->format('Y-m-d'));
    }
}
