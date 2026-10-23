<?php

namespace Database\Seeders\Staff;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Writes one demo booking with its seat rows, tickets and the admission scans of its used tickets.
 */
class DemoBookingBuilder
{
    public function __construct(
        private readonly User $staff,
        private readonly DemoScanClock $clock,
    ) {}

    /**
     * @param  Collection<int, Seat>  $seats
     * @param  array{ref: int, seats: int, used: int, cancelled: bool}  $plan
     */
    public function create(Showtime $show, User $customer, array $plan, Collection $seats): Booking
    {
        $reference = $this->reference($show, $plan['ref']);
        $cancelled = $plan['cancelled'];
        $prices = $seats->map(fn (Seat $seat): int => $this->price($show, $seat));

        $booking = Booking::forceCreate([
            'reference' => $reference,
            'user_id' => $customer->id,
            'showtime_id' => $show->id,
            'status' => $cancelled ? 'cancelled' : 'confirmed',
            'seats_count' => $seats->count(),
            'total_cents' => $prices->sum(),
            'currency' => 'USD',
            'hold_expires_at' => $show->starts_at->copy()->subDays(2),
            'confirmed_at' => $show->starts_at->copy()->subDays(2)->addMinutes($plan['ref']),
            'cancelled_at' => $cancelled ? $show->starts_at->copy()->subDay() : null,
            'cancelled_by' => $cancelled ? $customer->id : null,
            'cancel_reason' => $cancelled ? 'Cancelled by customer' : null,
        ]);

        foreach ($seats->values() as $index => $seat) {
            $this->item($booking, $show, $seat, $prices[$index], $index + 1, $index < $plan['used'], $cancelled);
        }

        return $booking;
    }

    /**
     * A short, easy-to-type demo token for a ticket number: T-0482-01 becomes DEMO-0482-01.
     * Real tickets get random 40 character tokens; the token column pads short values.
     */
    public static function token(string $ticketNumber): string
    {
        return 'DEMO-'.substr($ticketNumber, 2);
    }

    private function item(Booking $booking, Showtime $show, Seat $seat, int $price, int $number, bool $used, bool $cancelled): void
    {
        $item = BookingItem::forceCreate([
            'booking_id' => $booking->id,
            'showtime_id' => $show->id,
            'seat_id' => $seat->id,
            'seat_label' => $seat->label,
            'seat_type' => $seat->type,
            'price_cents' => $price,
            'status' => $cancelled ? 'cancelled' : 'active',
        ]);

        $usedAt = $used ? $this->clock->next($show) : null;

        $ticketNumber = sprintf('T-%s-%02d', substr($booking->reference, -4), $number);

        $ticket = Ticket::forceCreate([
            'booking_item_id' => $item->id,
            'booking_id' => $booking->id,
            'showtime_id' => $show->id,
            'ticket_number' => $ticketNumber,
            'token' => self::token($ticketNumber),
            'status' => $cancelled ? 'void' : ($used ? 'used' : 'valid'),
            'used_at' => $usedAt,
            'used_by' => $used ? $this->staff->id : null,
            'voided_at' => $cancelled ? $booking->cancelled_at : null,
        ]);

        if ($usedAt) {
            $this->recordAdmission($ticket, $show, $usedAt);
        }
    }

    private function recordAdmission(Ticket $ticket, Showtime $show, \DateTimeInterface $at): void
    {
        TicketScan::forceCreate([
            'ticket_id' => $ticket->id,
            'showtime_id' => $show->id,
            'token_hash' => hash('sha256', $ticket->token),
            'scanned_by' => $this->staff->id,
            'result' => 'admitted',
            'message' => null,
            'ip_address' => '127.0.0.1',
            'scanned_at' => $at,
        ]);
    }

    /** Booking references look like CB-261021-0482 (year, month, day of the show). */
    private function reference(Showtime $show, int $number): string
    {
        return sprintf('CB-%s-%04d', $show->starts_at->format('ymd'), $number);
    }

    private function price(Showtime $show, Seat $seat): int
    {
        $extra = $seat->type->value === 'premium' ? (int) config('cinebook.premium_surcharge_cents') : 0;

        return $show->base_price_cents + $extra;
    }
}
