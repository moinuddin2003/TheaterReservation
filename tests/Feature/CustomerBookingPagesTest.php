<?php

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Enums\SeatType;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Str;

function createCustomerBooking(User $user, BookingStatus $status): Booking
{
    $movie = Movie::factory()->create();
    $screen = Screen::factory()->create();
    $showtime = Showtime::factory()
        ->for($movie)
        ->for($screen)
        ->create([
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ]);
    $seat = Seat::factory()->create([
        'screen_id' => $screen->id,
        'row_label' => 'G',
        'seat_number' => 7,
        'label' => 'G7',
        'type' => SeatType::Premium,
    ]);

    $booking = new Booking([
        'reference' => 'CB-'.strtoupper(Str::random(10)),
        'user_id' => $user->id,
        'showtime_id' => $showtime->id,
        'seats_count' => 1,
        'currency' => 'USD',
        'hold_expires_at' => now()->addMinutes(10),
    ]);
    $booking->forceFill([
        'status' => $status,
        'total_cents' => 1200,
        'confirmed_at' => $status === BookingStatus::Confirmed ? now() : null,
    ]);
    $booking->save();

    $item = new BookingItem([
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'seat_label' => $seat->label,
        'seat_type' => SeatType::Premium,
    ]);
    $item->forceFill(['price_cents' => 1200]);
    $item->save();

    if ($status === BookingStatus::Confirmed) {
        $ticket = new Ticket([
            'booking_item_id' => $item->id,
            'booking_id' => $booking->id,
            'showtime_id' => $showtime->id,
            'ticket_number' => 'T-'.$booking->id.'-01',
            'token' => Str::random(40),
        ]);
        $ticket->forceFill(['status' => TicketStatus::Valid]);
        $ticket->save();
    }

    if ($status === BookingStatus::PendingPayment) {
        $hold = new SeatHold([
            'hold_token' => Str::uuid()->toString(),
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'expires_at' => now()->addMinutes(10),
        ]);
        $hold->forceFill([
            'price_cents' => 1200,
            'status' => HoldStatus::Active,
        ]);
        $hold->save();

        $payment = new Payment([
            'booking_id' => $booking->id,
            'gateway' => 'stripe',
            'idempotency_key' => Str::random(64),
            'currency' => 'USD',
        ]);
        $payment->forceFill([
            'amount_cents' => 1200,
            'status' => PaymentStatus::Pending,
        ]);
        $payment->save();
    }

    return $booking;
}

test('customers can only see their own bookings', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    $booking = createCustomerBooking($user, BookingStatus::Confirmed);
    $privateBooking = createCustomerBooking($anotherUser, BookingStatus::Confirmed);

    $this->actingAs($user)
        ->get(route('bookings.index'))
        ->assertOk()
        ->assertSee($booking->reference)
        ->assertDontSee($privateBooking->reference);

    $this->get(route('bookings.show', $privateBooking->reference))
        ->assertNotFound();
});

test('booking status pages use saved booking and ticket details', function () {
    $user = User::factory()->create();
    $pendingBooking = createCustomerBooking($user, BookingStatus::PendingPayment);
    $confirmedBooking = createCustomerBooking($user, BookingStatus::Confirmed);

    $this->actingAs($user)
        ->get(route('bookings.checkout', $pendingBooking->reference))
        ->assertOk()
        ->assertSee('Your seats are on hold')
        ->assertSee('Checkout is not connected yet');

    $this->get(route('bookings.processing', $pendingBooking->reference))
        ->assertOk()
        ->assertSee('Payment processing');

    $this->get(route('bookings.confirmed', $confirmedBooking->reference))
        ->assertOk()
        ->assertSee('You’re going to the movies.')
        ->assertSee('T-'.$confirmedBooking->id.'-01');
});

test('booking screens require a signed-in customer', function () {
    $this->get(route('bookings.index'))
        ->assertRedirect(route('login'));
});

test('expired holds cannot open checkout and pending bookings are not confirmed', function () {
    $user = User::factory()->create();
    $booking = createCustomerBooking($user, BookingStatus::PendingPayment);
    $booking->seatHolds()->update(['expires_at' => now()->subMinute()]);

    $this->actingAs($user)
        ->get(route('bookings.checkout', $booking->reference))
        ->assertRedirect(route('bookings.show', $booking->reference))
        ->assertSessionHas('status');

    $this->get(route('bookings.confirmed', $booking->reference))
        ->assertRedirect(route('bookings.show', $booking->reference));
});
