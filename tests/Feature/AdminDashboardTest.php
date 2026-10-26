<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

it('shows live dashboard totals and handles an empty schedule', function () {
    $this->travelTo(Carbon::parse('2026-10-26 14:30:00'));
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Confirmed bookings today')
        ->assertSee('Net revenue today')
        ->assertSee('$0.00')
        ->assertSee('0.0%')
        ->assertSee('Needs attention')
        ->assertSee('Failed webhook events')
        ->assertSee('No showtimes scheduled');
});

it('calculates revenue and occupancy from confirmed sales and active holds', function () {
    $this->travelTo(Carbon::parse('2026-10-26 14:30:00'));

    $screen = Screen::factory()
        ->for(Cinema::factory()->create())
        ->create(['rows_count' => 1, 'seats_per_row' => 3]);
    $movie = Movie::factory()->create(['title' => 'Dashboard Feature']);
    $showtime = Showtime::factory()
        ->for($movie)
        ->for($screen)
        ->create([
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(4),
        ]);
    $soldSeat = Seat::factory()->for($screen)->create([
        'row_label' => 'A',
        'seat_number' => 1,
        'label' => 'A1',
    ]);
    $heldSeat = Seat::factory()->for($screen)->create([
        'row_label' => 'A',
        'seat_number' => 2,
        'label' => 'A2',
    ]);
    Seat::factory()->for($screen)->disabled()->create([
        'row_label' => 'A',
        'seat_number' => 3,
        'label' => 'A3',
    ]);
    $customer = User::factory()->create();
    $booking = Booking::query()->forceCreate([
        'reference' => 'DASHBOARD-1',
        'user_id' => $customer->id,
        'showtime_id' => $showtime->id,
        'status' => 'confirmed',
        'seats_count' => 1,
        'total_cents' => 1200,
        'currency' => 'USD',
        'hold_expires_at' => now()->addMinutes(10),
        'confirmed_at' => now(),
    ]);
    BookingItem::query()->forceCreate([
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'seat_id' => $soldSeat->id,
        'seat_label' => $soldSeat->label,
        'seat_type' => 'standard',
        'price_cents' => 1200,
        'status' => 'active',
    ]);
    SeatHold::query()->forceCreate([
        'hold_token' => (string) Str::uuid(),
        'showtime_id' => $showtime->id,
        'seat_id' => $heldSeat->id,
        'user_id' => $customer->id,
        'price_cents' => 1200,
        'status' => 'active',
        'expires_at' => now()->addMinutes(5),
    ]);
    $payment = Payment::query()->forceCreate([
        'booking_id' => $booking->id,
        'gateway' => 'stripe',
        'idempotency_key' => Str::random(64),
        'amount_cents' => 1200,
        'currency' => 'USD',
        'status' => 'succeeded',
        'paid_at' => now(),
    ]);
    Refund::query()->forceCreate([
        'payment_id' => $payment->id,
        'booking_id' => $booking->id,
        'amount_cents' => 200,
        'currency' => 'USD',
        'status' => 'succeeded',
        'reason' => 'customer_request',
        'idempotency_key' => Str::random(64),
        'processed_at' => now(),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('$10.00')
        ->assertSee('100.0%')
        ->assertSee('1 sold + 1 held / 2 capacity')
        ->assertSee('Dashboard Feature')
        ->assertSee('1 tickets sold');
});
