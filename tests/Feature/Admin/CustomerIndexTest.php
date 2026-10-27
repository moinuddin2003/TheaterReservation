<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Genre;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\User;

test('admins can see database-backed customers without customer creation or detail actions', function () {
    $admin = User::factory()->admin()->create();
    $verifiedCustomer = User::factory()->create(['name' => 'Ava Verified', 'email' => 'ava@example.test']);
    $unverifiedCustomer = User::factory()->unverified()->create([
        'name' => 'Noah Unverified',
        'email' => 'noah@example.test',
    ]);
    User::factory()->staff()->create(['name' => 'Staff Account']);

    $response = $this->actingAs($admin)
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertSee('Ava Verified')
        ->assertSee('Verified')
        ->assertSee('Noah Unverified')
        ->assertSee('Unverified')
        ->assertSee(route('admin.customers.show', $verifiedCustomer), false)
        ->assertDontSee('Staff Account')
        ->assertDontSee('Add customer')
        ->assertDontSee('Verification')
        ->assertDontSee('Detail');

    expect($response->viewData('customers'))->toHaveCount(2);
});

test('customer search and status filters are applied to database records', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'Ava Verified', 'email' => 'ava@example.test']);
    User::factory()->unverified()->create(['name' => 'Ava Unverified', 'email' => 'ava.inactive@example.test']);
    User::factory()->create(['name' => 'Noah Verified', 'email' => 'noah@example.test']);

    $this->actingAs($admin)
        ->get(route('admin.customers.index', ['search' => 'Ava', 'status' => 'verified']))
        ->assertOk()
        ->assertSee('Ava Verified')
        ->assertDontSee('Ava Unverified')
        ->assertDontSee('Noah Verified');

    $this->get(route('admin.customers.index', ['status' => 'unverified']))
        ->assertOk()
        ->assertSee('Ava Unverified')
        ->assertDontSee('Ava Verified')
        ->assertDontSee('Noah Verified');
});

test('customer details show booking history and a standard back link', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create(['name' => 'Ava Verified']);
    $showtime = Showtime::factory()->create();
    $booking = Booking::forceCreate([
        'reference' => 'CUSTOMER-DETAIL-1',
        'user_id' => $customer->id,
        'showtime_id' => $showtime->id,
        'status' => 'confirmed',
        'seats_count' => 2,
        'total_cents' => 4500,
        'currency' => 'USD',
        'hold_expires_at' => now()->addHour(),
    ]);
    $genre = Genre::factory()->create(['name' => 'Science Fiction', 'slug' => 'science-fiction']);
    $showtime->movie->genres()->attach($genre);
    $seat = Seat::factory()->create(['screen_id' => $showtime->screen_id]);
    BookingItem::forceCreate([
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'seat_label' => $seat->label,
        'seat_type' => 'standard',
        'price_cents' => 1500,
    ]);
    $payment = Payment::forceCreate([
        'booking_id' => $booking->id,
        'gateway' => 'stripe',
        'idempotency_key' => str_repeat('c', 64),
        'amount_cents' => 4500,
        'currency' => 'USD',
        'status' => 'succeeded',
    ]);
    Refund::forceCreate([
        'payment_id' => $payment->id,
        'booking_id' => $booking->id,
        'amount_cents' => 500,
        'currency' => 'USD',
        'status' => 'succeeded',
        'reason' => 'requested_by_customer',
        'idempotency_key' => str_repeat('d', 64),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.customers.show', $customer))
        ->assertOk()
        ->assertSee('Member since')
        ->assertSee('Spending summary')
        ->assertSee('Booking history')
        ->assertSee($booking->reference)
        ->assertSee($showtime->movie->title)
        ->assertSee(route('admin.bookings.show', $booking), false)
        ->assertSee(route('admin.customers.index'), false)
        ->assertSee('Back to customers')
        ->assertSee('Avg. ticket price')
        ->assertSee('Science Fiction')
        ->assertDontSee('Email customer')
        ->assertSee('Member since')
        ->assertSee('All statuses')
        ->assertDontSee('Coming soon')
        ->assertDontSee('chevron-right');

    expect($response->viewData('customer')->total_spent_cents)->toBe(4000)
        ->and($response->viewData('averageTicketPriceCents'))->toBe(1500)
        ->and($response->viewData('favoriteGenre'))->toBe('Science Fiction');

    $staff = User::factory()->staff()->create();
    $this->get(route('admin.customers.show', $staff))->assertNotFound();
});

test('customer booking history filters by status and sorts by creation date', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $showtime = Showtime::factory()->create();

    $olderBooking = Booking::forceCreate([
        'reference' => 'CUSTOMER-OLD-BOOKING',
        'user_id' => $customer->id,
        'showtime_id' => $showtime->id,
        'status' => 'confirmed',
        'seats_count' => 1,
        'total_cents' => 1200,
        'currency' => 'USD',
        'hold_expires_at' => now()->addHour(),
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);
    $newerBooking = Booking::forceCreate([
        'reference' => 'CUSTOMER-NEW-BOOKING',
        'user_id' => $customer->id,
        'showtime_id' => $showtime->id,
        'status' => 'cancelled',
        'seats_count' => 1,
        'total_cents' => 1200,
        'currency' => 'USD',
        'hold_expires_at' => now()->addHour(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.customers.show', [
            'customer' => $customer,
            'status' => 'confirmed',
            'sort' => 'oldest',
        ]))
        ->assertOk()
        ->assertSee($olderBooking->reference)
        ->assertDontSee($newerBooking->reference)
        ->assertSee('Oldest first');

    expect(
        $this->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->viewData('bookings')
            ->first()
            ->getKey(),
    )->toBe($newerBooking->getKey());
});

test('customer rows show booking counts and successful payment totals', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $booking = Booking::forceCreate([
        'reference' => 'CUSTOMER-BOOKING-1',
        'user_id' => $customer->id,
        'showtime_id' => $showtime->id,
        'status' => 'confirmed',
        'seats_count' => 2,
        'total_cents' => 4500,
        'currency' => 'USD',
        'hold_expires_at' => now()->addHour(),
    ]);

    Payment::forceCreate([
        'booking_id' => $booking->id,
        'gateway' => 'stripe',
        'idempotency_key' => str_repeat('a', 64),
        'amount_cents' => 4500,
        'currency' => 'USD',
        'status' => 'succeeded',
    ]);
    Payment::forceCreate([
        'booking_id' => $booking->id,
        'gateway' => 'stripe',
        'idempotency_key' => str_repeat('b', 64),
        'amount_cents' => 9000,
        'currency' => 'USD',
        'status' => 'pending',
    ]);

    $customers = $this->actingAs($admin)
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->viewData('customers');

    $customerRow = $customers->getCollection()->sole();

    expect($customerRow->bookings_count)->toBe(1)
        ->and($customerRow->total_spent_cents)->toBe(4500);
});
