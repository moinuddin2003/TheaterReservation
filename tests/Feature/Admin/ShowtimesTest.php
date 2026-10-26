<?php

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Setting;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

test('the showtime schedule paginates ten showtimes and keeps week filters', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $movie = Movie::factory()->create(['title' => 'The Schedule Test']);
    $screen = Screen::factory()->create(['name' => 'Screen One']);
    $screen->cinema->update(['phone' => '555-0148']);
    $weekStart = now()->addWeek()->startOfWeek();

    foreach (range(0, 11) as $index) {
        $startsAt = $weekStart->copy()->addHours(9 + $index * 2);
        Showtime::factory()->create([
            'movie_id' => $movie->id,
            'screen_id' => $screen->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(120),
        ]);
    }

    $query = ['date' => $weekStart->toDateString(), 'search' => 'Schedule Test'];

    $this->actingAs($admin)
        ->get(route('admin.showtimes.index', $query))
        ->assertOk()
        ->assertSee('Showing 1–10 of 12 showtimes')
        ->assertSee('The Schedule Test')
        ->assertSee('Screen One')
        ->assertSee('555-0148')
        ->assertSee('Edit showtime')
        ->assertSee('Cancel showtime')
        ->assertSee('More actions for The Schedule Test at')
        ->assertDontSee('name="movie_id"', false)
        ->assertSee('Screen One · '.$screen->cinema->name)
        ->assertDontSee('aria-hidden="true">+</span>', false)
        ->assertDontSee('Selected showtime')
        ->assertSee('date='.$weekStart->copy()->addDay()->toDateString(), false)
        ->assertSee('search=Schedule%20Test', false);

    $this->get(route('admin.showtimes.index', $query + ['page' => 2]))
        ->assertOk()
        ->assertSee('Showing 11–12 of 12 showtimes')
        ->assertSee('date='.$weekStart->toDateString(), false)
        ->assertSee('search=Schedule%20Test', false);
});

test('an administrator can create a showtime and its end time uses movie runtime', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $movie = Movie::factory()->create(['title' => 'Runtime Feature', 'duration_minutes' => 100]);
    $screen = Screen::factory()->create();
    $startsAt = now()->addDays(2)->startOfHour()->addHour();
    $endsAt = $startsAt->copy()->addMinutes(100 + (int) config('cinebook.screen_turnaround_minutes', 15));

    $this->actingAs($admin)
        ->get(route('admin.showtimes.create'))
        ->assertOk()
        ->assertSee('Create showtime')
        ->assertSee('Runtime Feature')
        ->assertSee($screen->name);

    $this->post(route('admin.showtimes.store'), [
        'movie_id' => $movie->id,
        'screen_id' => $screen->id,
        'starts_at' => $startsAt->format('Y-m-d\TH:i'),
        'base_price' => '12.50',
    ])
        ->assertRedirect(route('admin.showtimes.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('showtimes', [
        'movie_id' => $movie->id,
        'screen_id' => $screen->id,
        'starts_at' => $startsAt->toDateTimeString(),
        'ends_at' => $endsAt->toDateTimeString(),
        'base_price_cents' => 1250,
        'status' => 'scheduled',
    ]);
});

test('showtime scheduling rejects overlapping screenings on the same screen', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $movie = Movie::factory()->create(['duration_minutes' => 100]);
    $otherMovie = Movie::factory()->create();
    $screen = Screen::factory()->create();
    $startsAt = now()->addDays(2)->startOfHour()->addHour();
    Showtime::factory()->create([
        'movie_id' => $movie->id,
        'screen_id' => $screen->id,
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->copy()->addMinutes(115),
    ]);

    $this->actingAs($admin)
        ->from(route('admin.showtimes.create'))
        ->post(route('admin.showtimes.store'), [
            'movie_id' => $otherMovie->id,
            'screen_id' => $screen->id,
            'starts_at' => $startsAt->copy()->addMinutes(30)->format('Y-m-d\TH:i'),
            'base_price' => '10.00',
        ])
        ->assertRedirect(route('admin.showtimes.create'))
        ->assertSessionHasErrors(['starts_at']);
});

test('the schedule date filter defaults invalid input to today in the configured timezone', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    Setting::create(['key' => 'timezone', 'value' => 'America/Los_Angeles']);

    $this->actingAs($admin)
        ->get(route('admin.showtimes.index', ['date' => 'not-a-date']))
        ->assertOk()
        ->assertSee('value="'.Carbon::now('America/Los_Angeles')->toDateString().'"', false);
});

test('the selected date shows showtimes on that date and all future dates only', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $screen = Screen::factory()->create();
    $selectedDate = Carbon::parse('2027-04-15 00:00:00', 'UTC');
    $earlierMovie = Movie::factory()->create(['title' => 'Earlier Screening']);
    $selectedMovie = Movie::factory()->create(['title' => 'Selected Day Screening']);
    $futureMovie = Movie::factory()->create(['title' => 'Future Screening']);

    foreach ([
        [$earlierMovie, $selectedDate->copy()->subMinute()],
        [$selectedMovie, $selectedDate->copy()->addHours(10)],
        [$futureMovie, $selectedDate->copy()->addDay()],
    ] as [$movie, $startsAt]) {
        Showtime::factory()->create([
            'movie_id' => $movie->id,
            'screen_id' => $screen->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(2),
        ]);
    }

    $this->actingAs($admin)
        ->get(route('admin.showtimes.index', ['date' => $selectedDate->toDateString()]))
        ->assertOk()
        ->assertSee('Selected Day Screening')
        ->assertSee('Future Screening')
        ->assertDontSee('Earlier Screening')
        ->assertSee('Weekly view · Apr 12 - Apr 18, 2027')
        ->assertSee('date='.$selectedDate->copy()->subWeek()->toDateString(), false)
        ->assertSee('date='.$selectedDate->copy()->addWeek()->toDateString(), false)
        ->assertViewHas('showtimeCounts', fn ($counts) => (int) $counts->get(3) === 1 && (int) $counts->get(4) === 1);
});

test('the selected date boundary uses the configured cinema timezone', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    Setting::create(['key' => 'timezone', 'value' => 'America/Los_Angeles']);
    $screen = Screen::factory()->create();
    $beforeMidnightMovie = Movie::factory()->create(['title' => 'Before Local Midnight']);
    $atMidnightMovie = Movie::factory()->create(['title' => 'At Local Midnight']);

    foreach ([
        [$beforeMidnightMovie, Carbon::parse('2027-04-15 06:59:00', 'UTC')],
        [$atMidnightMovie, Carbon::parse('2027-04-15 07:00:00', 'UTC')],
    ] as [$movie, $startsAt]) {
        Showtime::factory()->create([
            'movie_id' => $movie->id,
            'screen_id' => $screen->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(2),
        ]);
    }

    $this->actingAs($admin)
        ->get(route('admin.showtimes.index', ['date' => '2027-04-15']))
        ->assertOk()
        ->assertSee('At Local Midnight')
        ->assertDontSee('Before Local Midnight');
});

test('showtime status follows the cinema clock and movie runtime', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $screen = Screen::factory()->create();
    Carbon::setTestNow(Carbon::parse('2027-04-15 12:00:00', 'UTC'));

    foreach ([
        ['Past completed screening', '2027-04-15 09:00:00', 'Completed'],
        ['Current screening', '2027-04-15 11:00:00', 'Now Showing'],
        ['Future screening', '2027-04-15 13:00:00', 'Scheduled'],
    ] as [$title, $start, $status]) {
        $movie = Movie::factory()->create(['title' => $title, 'duration_minutes' => 90]);
        $startsAt = Carbon::parse($start, 'UTC');
        Showtime::factory()->create([
            'movie_id' => $movie->id,
            'screen_id' => $screen->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(105),
        ]);
    }

    $cancelledMovie = Movie::factory()->create(['title' => 'Cancelled screening', 'duration_minutes' => 90]);
    Showtime::factory()->cancelled()->create([
        'movie_id' => $cancelledMovie->id,
        'screen_id' => $screen->id,
        'starts_at' => Carbon::parse('2027-04-15 13:00:00', 'UTC'),
        'ends_at' => Carbon::parse('2027-04-15 14:45:00', 'UTC'),
    ]);

    try {
        $this->actingAs($admin)
            ->get(route('admin.showtimes.index', ['date' => '2027-04-15']))
            ->assertOk()
            ->assertSee('Completed')
            ->assertSee('Now Showing')
            ->assertSee('Scheduled')
            ->assertSee('Cancelled');
    } finally {
        Carbon::setTestNow();
    }
});

test('the empty schedule create action pre-fills the selected date', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.showtimes.index', ['date' => '2027-04-15']))
        ->assertOk()
        ->assertSee('No showtimes on Thu 15 Apr')
        ->assertSee(route('admin.showtimes.create', ['date' => '2027-04-15']));

    $this->get(route('admin.showtimes.create', ['date' => '2027-04-15']))
        ->assertOk()
        ->assertSee('value="2027-04-15T09:00"', false);
});

test('cancelling a showtime closes bookings voids tickets releases seats and leaves refunds untouched', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $confirmedSeat = Seat::factory()->create(['screen_id' => $showtime->screen_id]);
    $pendingSeat = Seat::factory()->create(['screen_id' => $showtime->screen_id]);
    $confirmedBooking = Booking::forceCreate([
        'reference' => 'CNF-'.Str::upper(Str::random(10)),
        'user_id' => $customer->id,
        'showtime_id' => $showtime->id,
        'status' => BookingStatus::Confirmed,
        'seats_count' => 1,
        'total_cents' => 2500,
        'confirmed_at' => now(),
        'hold_expires_at' => now()->addHour(),
    ]);
    $pendingBooking = Booking::forceCreate([
        'reference' => 'PND-'.Str::upper(Str::random(10)),
        'user_id' => $customer->id,
        'showtime_id' => $showtime->id,
        'status' => BookingStatus::PendingPayment,
        'seats_count' => 1,
        'total_cents' => 1200,
        'hold_expires_at' => now()->addHour(),
    ]);
    $confirmedItem = BookingItem::forceCreate([
        'booking_id' => $confirmedBooking->id,
        'showtime_id' => $showtime->id,
        'seat_id' => $confirmedSeat->id,
        'seat_label' => $confirmedSeat->label,
        'seat_type' => $confirmedSeat->type->value,
        'price_cents' => 2500,
    ]);
    BookingItem::forceCreate([
        'booking_id' => $pendingBooking->id,
        'showtime_id' => $showtime->id,
        'seat_id' => $pendingSeat->id,
        'seat_label' => $pendingSeat->label,
        'seat_type' => $pendingSeat->type->value,
        'price_cents' => 1200,
    ]);
    $ticket = Ticket::create([
        'booking_item_id' => $confirmedItem->id,
        'booking_id' => $confirmedBooking->id,
        'showtime_id' => $showtime->id,
        'ticket_number' => 'T-'.Str::upper(Str::random(10)),
        'token' => Str::random(40),
    ]);
    $activeHold = SeatHold::forceCreate([
        'hold_token' => (string) Str::uuid(),
        'showtime_id' => $showtime->id,
        'seat_id' => $pendingSeat->id,
        'user_id' => $customer->id,
        'booking_id' => $pendingBooking->id,
        'price_cents' => 1200,
        'expires_at' => now()->addMinutes(10),
    ]);
    $paidPayment = Payment::forceCreate([
        'booking_id' => $confirmedBooking->id,
        'gateway' => 'test',
        'idempotency_key' => Str::random(64),
        'amount_cents' => 2500,
        'status' => PaymentStatus::Succeeded,
        'paid_at' => now(),
    ]);
    $pendingPayment = Payment::forceCreate([
        'booking_id' => $pendingBooking->id,
        'gateway' => 'test',
        'idempotency_key' => Str::random(64),
        'amount_cents' => 1200,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.showtimes.cancel.confirm', $showtime))
        ->assertOk()
        ->assertSee('Confirmed bookings')
        ->assertSee('$25.00')
        ->assertSee('1 active seat holds will be released')
        ->assertSee('Payment refunds are not part of this action.')
        ->assertSee('name="confirm_cancellation"', false);

    $this->post(route('admin.showtimes.cancel', $showtime), [
        'cancel_reason' => 'Projector failure',
        'confirm_cancellation' => '1',
    ])
        ->assertRedirect(route('admin.showtimes.index'))
        ->assertSessionHas('status', 'Showtime cancelled. 2 bookings cancelled, 1 tickets voided and 1 seat holds released. No refunds were processed.');

    expect($showtime->fresh()->status->value)->toBe('cancelled')
        ->and($showtime->fresh()->cancel_reason)->toBe('Projector failure')
        ->and($showtime->fresh()->cancelled_by)->toBe($admin->id)
        ->and($confirmedBooking->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($confirmedBooking->fresh()->cancel_reason)->toBe('Projector failure')
        ->and($pendingBooking->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($confirmedItem->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Void)
        ->and($ticket->fresh()->voided_at)->not->toBeNull()
        ->and($activeHold->fresh()->status)->toBe(HoldStatus::Released)
        ->and($activeHold->fresh()->released_at)->not->toBeNull()
        ->and($paidPayment->fresh()->status)->toBe(PaymentStatus::Succeeded)
        ->and($pendingPayment->fresh()->status)->toBe(PaymentStatus::Expired)
        ->and($pendingPayment->fresh()->failure_reason)->toBe('Showtime cancelled before payment completed.')
        ->and($confirmedBooking->refunds()->count())->toBe(0);
});

test('cancellation requires a reason and explicit confirmation', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $showtime = Showtime::factory()->create();

    $this->actingAs($admin)
        ->from(route('admin.showtimes.cancel.confirm', $showtime))
        ->post(route('admin.showtimes.cancel', $showtime), [])
        ->assertRedirect(route('admin.showtimes.cancel.confirm', $showtime))
        ->assertSessionHasErrors(['cancel_reason', 'confirm_cancellation']);

    expect($showtime->fresh()->status->value)->toBe('scheduled');
});
