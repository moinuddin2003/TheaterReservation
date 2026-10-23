<?php

namespace Tests\Support;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Builds the rows the staff screens read (shows, bookings, seats, tickets) for database tests.
 */
final class StaffDbFixture
{
    public static function show(string $startsAt = '2026-10-15 19:30', string $movie = 'The Last Horizon', string $screen = 'Screen 1'): Showtime
    {
        return Showtime::factory()->create([
            'movie_id' => Movie::factory()->create(['title' => $movie])->id,
            'screen_id' => Screen::factory()->create(['name' => $screen])->id,
            'starts_at' => $startsAt,
            'ends_at' => Carbon::parse($startsAt)->addMinutes(140),
        ]);
    }

    /**
     * One booking with one ticket per seat.
     *
     * @param  array<string, string>  $seats  seat label => type (standard|premium)
     * @param  list<string>  $used  seat labels whose ticket is already used
     */
    public static function booking(Showtime $show, string $reference, string $name, array $seats, array $used = [], string $status = 'confirmed'): Booking
    {
        $booking = Booking::forceCreate([
            'reference' => $reference,
            'user_id' => User::factory()->customer()->create(['name' => $name])->id,
            'showtime_id' => $show->id,
            'status' => $status,
            'seats_count' => count($seats),
            'total_cents' => 1000 * count($seats),
            'currency' => 'USD',
            'hold_expires_at' => $show->starts_at,
            'confirmed_at' => $show->starts_at,
        ]);

        $number = 0;

        foreach ($seats as $label => $type) {
            $number++;
            $item = BookingItem::forceCreate([
                'booking_id' => $booking->id,
                'showtime_id' => $show->id,
                'seat_id' => Seat::factory()->create(['screen_id' => $show->screen_id, 'row_label' => $label[0], 'seat_number' => (int) substr($label, 1), 'label' => $label, 'type' => $type])->id,
                'seat_label' => $label,
                'seat_type' => $type,
                'price_cents' => 1000,
                'status' => 'active',
            ]);

            Ticket::forceCreate([
                'booking_item_id' => $item->id,
                'booking_id' => $booking->id,
                'showtime_id' => $show->id,
                'ticket_number' => sprintf('T-%s-%02d', substr($reference, -4), $number),
                'token' => self::token($reference, $label),
                'status' => in_array($label, $used, true) ? 'used' : 'valid',
                'used_at' => in_array($label, $used, true) ? '2026-10-15 18:48:00' : null,
            ]);
        }

        return $booking;
    }

    /** The 40 character token of a ticket made by booking(). */
    public static function token(string $reference, string $seat): string
    {
        return substr(hash('sha256', $reference.$seat), 0, 40);
    }
}
