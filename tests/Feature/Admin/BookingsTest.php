<?php

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Setting;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

function createBookingForAdmin(User $customer, Showtime $showtime, string $status, string $reference): Booking
{
    return Booking::forceCreate([
        'reference' => $reference,
        'user_id' => $customer->id,
        'showtime_id' => $showtime->id,
        'status' => $status,
        'seats_count' => 1,
        'total_cents' => 1200,
        'currency' => 'USD',
        'hold_expires_at' => now()->addHour(),
    ]);
}

test('an administrator can view and filter the bookings list', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create(['name' => 'Sara Ahmed', 'email' => 'sara@example.com']);
    $movie = Movie::factory()->create(['title' => 'The Last Horizon']);
    $otherMovie = Movie::factory()->create(['title' => 'Another Movie']);
    $screen = Screen::factory()->create();
    $showtime = Showtime::factory()->create(['movie_id' => $movie->id, 'screen_id' => $screen->id]);
    $otherShowtime = Showtime::factory()->create(['movie_id' => $otherMovie->id, 'screen_id' => $screen->id]);
    $booking = createBookingForAdmin($customer, $showtime, BookingStatus::Confirmed->value, 'CB-ADMIN-1');
    createBookingForAdmin($customer, $otherShowtime, BookingStatus::Cancelled->value, 'CB-ADMIN-2');
    $seat = Seat::factory()->create(['screen_id' => $screen->id, 'label' => 'C5']);
    BookingItem::forceCreate([
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'seat_label' => 'C5',
        'seat_type' => 'standard',
        'price_cents' => 1200,
    ]);
    Payment::forceCreate([
        'booking_id' => $booking->id,
        'gateway' => 'stripe',
        'idempotency_key' => Str::random(64),
        'amount_cents' => 1200,
        'status' => PaymentStatus::Succeeded,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bookings.index', ['search' => 'Sara', 'movie_id' => $movie->id, 'status' => 'confirmed']))
        ->assertOk()
        ->assertSee('Total bookings')
        ->assertSee('Total revenue')
        ->assertSee('filters')
        ->assertSee('min-h-[112px]', false)
        ->assertSee('Showtime from')
        ->assertSee('Export CSV')
        ->assertSee('href="'.route('admin.bookings.export'), false)
        ->assertDontSee('2 total')
        ->assertSee('Recent bookings')
        ->assertSee('aria-label="Search bookings"', false)
        ->assertSee('The Last Horizon')
        ->assertSee('CB-ADMIN-1')
        ->assertSee('sara@example.com')
        ->assertSee('C5')
        ->assertSee('Showing 1–1 of 1 bookings')
        ->assertDontSee('CB-ADMIN-2')
        ->assertSee(route('admin.bookings.show', array_merge(
            ['search' => 'Sara', 'movie_id' => $movie->id, 'status' => 'confirmed'],
            ['booking' => $booking],
        )))
        ->assertDontSee('View booking CB-ADMIN-1')
        ->assertSee('search=Sara', false)
        ->assertSee('movie_id='.$movie->id, false);

    $this->get(route('admin.bookings.export', ['search' => 'Sara']))
        ->assertDownload('bookings.csv');
});

test('showtime date filters use inclusive cinema-local day boundaries', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    Setting::create(['key' => 'timezone', 'value' => 'Asia/Karachi']);

    collect([
        ['reference' => 'CB-BEFORE-RANGE', 'starts_at' => '2026-05-09 23:59:59'],
        ['reference' => 'CB-AT-RANGE-START', 'starts_at' => '2026-05-10 00:00:00'],
        ['reference' => 'CB-AT-RANGE-END', 'starts_at' => '2026-05-10 23:59:59'],
        ['reference' => 'CB-AFTER-RANGE', 'starts_at' => '2026-05-11 00:00:00'],
    ])->each(function (array $attributes) use ($customer): void {
        $showtime = Showtime::factory()->create([
            'starts_at' => Carbon::parse($attributes['starts_at'], 'Asia/Karachi')->utc(),
        ]);
        $booking = createBookingForAdmin(
            $customer,
            $showtime,
            BookingStatus::Confirmed->value,
            $attributes['reference'],
        );
        $booking->forceFill([
            'created_at' => Carbon::parse('2026-04-01 12:00:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-04-01 12:00:00', 'UTC'),
        ])->saveQuietly();
    });

    $this->actingAs($admin)
        ->get(route('admin.bookings.index', ['from' => '2026-05-10', 'to' => '2026-05-10']))
        ->assertOk()
        ->assertSee('CB-AT-RANGE-START')
        ->assertSee('CB-AT-RANGE-END')
        ->assertDontSee('CB-BEFORE-RANGE')
        ->assertDontSee('CB-AFTER-RANGE');

    $this->get(route('admin.bookings.index', ['to' => '2026-05-10']))
        ->assertOk()
        ->assertSee('CB-BEFORE-RANGE')
        ->assertSee('CB-AT-RANGE-START')
        ->assertSee('CB-AT-RANGE-END')
        ->assertDontSee('CB-AFTER-RANGE');

    $this->get(route('admin.bookings.index', ['from' => '2026-05-10']))
        ->assertOk()
        ->assertDontSee('CB-BEFORE-RANGE')
        ->assertSee('CB-AT-RANGE-START')
        ->assertSee('CB-AT-RANGE-END')
        ->assertSee('CB-AFTER-RANGE');
});

test('showtime date filters include the full selected multi-day range', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    Setting::create(['key' => 'timezone', 'value' => 'Asia/Karachi']);

    collect([
        ['reference' => 'CB-BEFORE-OCTOBER', 'starts_at' => '2026-09-30 23:59:59'],
        ['reference' => 'CB-OCTOBER-START', 'starts_at' => '2026-10-01 00:00:00'],
        ['reference' => 'CB-OCTOBER-MIDDLE', 'starts_at' => '2026-10-16 12:00:00'],
        ['reference' => 'CB-NOVEMBER-END', 'starts_at' => '2026-11-01 23:59:59'],
        ['reference' => 'CB-AFTER-NOVEMBER', 'starts_at' => '2026-11-02 00:00:00'],
    ])->each(function (array $attributes) use ($customer): void {
        $showtime = Showtime::factory()->create([
            'starts_at' => Carbon::parse($attributes['starts_at'], 'Asia/Karachi')->utc(),
        ]);
        $booking = createBookingForAdmin(
            $customer,
            $showtime,
            BookingStatus::Confirmed->value,
            $attributes['reference'],
        );
        $booking->forceFill([
            'created_at' => Carbon::parse('2026-09-01 12:00:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-09-01 12:00:00', 'UTC'),
        ])->saveQuietly();
    });

    $this->actingAs($admin)
        ->get(route('admin.bookings.index', ['from' => '2026-10-01', 'to' => '2026-11-01']))
        ->assertOk()
        ->assertSee('CB-OCTOBER-START')
        ->assertSee('CB-OCTOBER-MIDDLE')
        ->assertSee('CB-NOVEMBER-END')
        ->assertDontSee('CB-BEFORE-OCTOBER')
        ->assertDontSee('CB-AFTER-NOVEMBER');
});

test('summary cards show all-time bookings and revenue regardless of showtime filters', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    Setting::create(['key' => 'timezone', 'value' => 'Asia/Karachi']);

    $outsideRangeShowtime = Showtime::factory()->create([
        'starts_at' => Carbon::parse('2026-10-22 12:00:00', 'Asia/Karachi')->utc(),
    ]);
    $outsideRangeBooking = createBookingForAdmin(
        $customer,
        $outsideRangeShowtime,
        BookingStatus::Confirmed->value,
        'CB-OUTSIDE-SUMMARY',
    );
    $outsideRangeBooking->forceFill([
        'created_at' => Carbon::now('UTC'),
        'updated_at' => Carbon::now('UTC'),
    ])->saveQuietly();
    Payment::forceCreate([
        'booking_id' => $outsideRangeBooking->id,
        'gateway' => 'stripe',
        'idempotency_key' => Str::random(64),
        'amount_cents' => 2500,
        'status' => PaymentStatus::Succeeded,
        'paid_at' => Carbon::now('UTC'),
    ]);

    $rangeShowtime = Showtime::factory()->create([
        'starts_at' => Carbon::parse('2026-10-05 12:00:00', 'Asia/Karachi')->utc(),
    ]);
    $rangeBooking = createBookingForAdmin(
        $customer,
        $rangeShowtime,
        BookingStatus::Confirmed->value,
        'CB-SELECTED-RANGE',
    );
    $rangeBooking->forceFill([
        'created_at' => Carbon::parse('2026-09-05 12:00:00', 'Asia/Karachi')->utc(),
        'updated_at' => Carbon::parse('2026-09-05 12:00:00', 'Asia/Karachi')->utc(),
    ])->saveQuietly();
    Payment::forceCreate([
        'booking_id' => $rangeBooking->id,
        'gateway' => 'stripe',
        'idempotency_key' => Str::random(64),
        'amount_cents' => 3700,
        'status' => PaymentStatus::Succeeded,
        'paid_at' => Carbon::parse('2026-09-05 12:00:00', 'Asia/Karachi')->utc(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bookings.index', ['from' => '2026-10-01', 'to' => '2026-10-10']))
        ->assertOk()
        ->assertSee('CB-SELECTED-RANGE')
        ->assertDontSee('CB-OUTSIDE-SUMMARY')
        ->assertSee('Total bookings')
        ->assertSee('text-cine-primary">2</p>', false)
        ->assertSee('Total revenue')
        ->assertSee('$62.00');
});

test('an administrator can open booking details with its seats payment refunds and activity', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create(['name' => 'Sara Ahmed', 'email' => 'sara@example.com']);
    $movie = Movie::factory()->create(['title' => 'The Last Horizon', 'duration_minutes' => 120]);
    $screen = Screen::factory()->create();
    $showtime = Showtime::factory()->create([
        'movie_id' => $movie->id,
        'screen_id' => $screen->id,
        'starts_at' => Carbon::parse('2027-04-15 19:30:00'),
        'ends_at' => Carbon::parse('2027-04-15 21:45:00'),
    ]);
    $booking = createBookingForAdmin($customer, $showtime, BookingStatus::Confirmed->value, 'CB-DETAIL-1');
    $seat = Seat::factory()->create(['screen_id' => $screen->id, 'label' => 'C5']);
    $item = BookingItem::forceCreate([
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'seat_label' => 'C5',
        'seat_type' => 'standard',
        'price_cents' => 1200,
    ]);
    $legacySeat = Seat::factory()->create(['screen_id' => $screen->id, 'label' => 'C6']);
    $legacyItem = BookingItem::forceCreate([
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'seat_id' => $legacySeat->id,
        'seat_label' => 'C6',
        'seat_type' => 'standard',
        'price_cents' => 1200,
    ]);
    Payment::forceCreate([
        'booking_id' => $booking->id,
        'gateway' => 'stripe',
        'gateway_payment_intent_id' => 'pi_test_123',
        'idempotency_key' => Str::random(64),
        'amount_cents' => 1200,
        'status' => PaymentStatus::Succeeded,
        'paid_at' => now(),
    ]);
    Ticket::create([
        'booking_item_id' => $item->id,
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'ticket_number' => 'T-DETAIL-1',
        'token' => Str::random(40),
    ]);
    Ticket::create([
        'booking_item_id' => $legacyItem->id,
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'ticket_number' => 'T-LEGACY-VOID',
        'token' => Str::random(40),
        'status' => 'voided',
    ]);
    $booking->forceFill(['confirmed_at' => now()])->save();

    $this->actingAs($admin)
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('CB-DETAIL-1')
        ->assertSee('The Last Horizon')
        ->assertSee('Sara Ahmed')
        ->assertSee('sara@example.com')
        ->assertSee('C5')
        ->assertSee('C6')
        ->assertSee('T-DETAIL-1')
        ->assertSee('T-LEGACY-VOID')
        ->assertSee('Void')
        ->assertSee('Payment ID')
        ->assertSee('pi_test_123')
        ->assertSee('Booking confirmed')
        ->assertSee('Ticket issued')
        ->assertSee('Order summary')
        ->assertSee('Cancel booking')
        ->assertSee('Issue refund')
        ->assertSee('Resend email')
        ->assertSee('x-data="adminModal(false)"', false)
        ->assertDontSee('data-action-key="cancel"', false)
        ->assertDontSee('data-action-key="override"', false)
        ->assertDontSee('element.tabIndex >= 0 && element.getClientRects().length > 0')
        ->assertSee('Open Settings')
        ->assertSee('Refunds')
        ->assertDontSee('120 min');
});

test('an administrator can cancel a booking and release its seats and tickets', function () {
    config()->set('mail.default', 'smtp');
    Mail::fake();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    $movie = Movie::factory()->create();
    $screen = Screen::factory()->create();
    $showtime = Showtime::factory()->create([
        'movie_id' => $movie->id,
        'screen_id' => $screen->id,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHours(2),
    ]);
    $booking = createBookingForAdmin($customer, $showtime, BookingStatus::Confirmed->value, 'CB-CANCEL-1');
    $seat = Seat::factory()->create(['screen_id' => $screen->id, 'label' => 'D4']);
    $item = BookingItem::forceCreate([
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'seat_label' => 'D4',
        'seat_type' => 'standard',
        'price_cents' => 1200,
        'status' => 'active',
    ]);
    $ticket = Ticket::create([
        'booking_item_id' => $item->id,
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'ticket_number' => 'T-CANCEL-1',
        'token' => Str::random(40),
        'status' => TicketStatus::Valid,
    ]);
    $hold = SeatHold::forceCreate([
        'hold_token' => (string) Str::uuid(),
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'user_id' => $customer->id,
        'booking_id' => $booking->id,
        'price_cents' => 1200,
        'status' => HoldStatus::Active,
        'expires_at' => now()->addDay(),
    ]);
    Payment::forceCreate([
        'booking_id' => $booking->id,
        'gateway' => 'stripe',
        'idempotency_key' => Str::random(64),
        'amount_cents' => 1200,
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.bookings.cancel', $booking), [
            'cancel_reason' => 'Customer request',
            'confirm_cancellation' => '1',
        ])
        ->assertRedirect(route('admin.bookings.show', $booking))
        ->assertSessionHas('status', 'Booking cancelled. Seats released, tickets voided and audit log saved. Customer email sent.');

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->fresh()->cancel_reason)->toBe('Customer request')
        ->and($item->fresh()->status)->toBe('cancelled')
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Void)
        ->and($ticket->fresh()->voided_at)->not->toBeNull()
        ->and($hold->fresh()->status)->toBe(HoldStatus::Released)
        ->and($hold->fresh()->released_at)->not->toBeNull()
        ->and($booking->payments()->first()->status)->toBe(PaymentStatus::Expired);

    Mail::assertSent(BookingCancelledMail::class, function (BookingCancelledMail $mail) use ($booking): bool {
        $html = $mail->render();

        return $mail->booking->is($booking)
            && str_contains($html, 'Seat D4')
            && ! str_contains($html, 'T-CANCEL-1');
    });
    expect(AuditLog::query()->where('action', 'booking.cancelled')->exists())->toBeTrue();
});

test('an administrator needs an override reason to cancel inside the cancellation window', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    Setting::query()->create(['key' => 'cancel_window_hours', 'value' => '24']);
    $showtime = Showtime::factory()->create([
        'starts_at' => now()->addHours(12),
        'ends_at' => now()->addHours(14),
    ]);
    $booking = createBookingForAdmin($customer, $showtime, BookingStatus::Confirmed->value, 'CB-CANCEL-2');

    $this->actingAs($admin)
        ->from(route('admin.bookings.show', $booking))
        ->post(route('admin.bookings.cancel', $booking), [
            'cancel_reason' => 'Customer request',
            'confirm_cancellation' => '1',
        ])
        ->assertRedirect(route('admin.bookings.show', $booking))
        ->assertSessionHasErrors('override_reason');

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);

    $this->post(route('admin.bookings.cancel', $booking), [
        'cancel_reason' => 'Customer request',
        'override_reason' => 'Exception approved by manager',
        'confirm_cancellation' => '1',
    ])->assertRedirect(route('admin.bookings.show', $booking));

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
});

test('an administrator can issue a full Stripe refund from booking details', function () {
    config()->set('services.stripe.secret', 'sk_test_booking_actions');
    Http::fake([
        'https://api.stripe.com/v1/refunds' => Http::response([
            'id' => 're_booking_test_123',
            'status' => 'succeeded',
        ], 200),
    ]);
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $booking = createBookingForAdmin($customer, $showtime, BookingStatus::Confirmed->value, 'CB-REFUND-1');
    $payment = Payment::forceCreate([
        'booking_id' => $booking->id,
        'gateway' => 'stripe',
        'gateway_payment_intent_id' => 'pi_booking_test_123',
        'idempotency_key' => Str::random(64),
        'amount_cents' => 1200,
        'status' => PaymentStatus::Succeeded,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.bookings.refund', $booking), [
            'amount' => '12.00',
            'refund_type' => 'full',
            'reason' => 'requested_by_customer',
            'confirm_refund' => '1',
        ])
        ->assertRedirect(route('admin.bookings.show', $booking))
        ->assertSessionHas('status', 'Refund processed successfully.');

    $refund = Refund::query()->sole();
    expect($refund->payment_id)->toBe($payment->id)
        ->and($refund->amount_cents)->toBe(1200)
        ->and($refund->status->value)->toBe('succeeded')
        ->and($refund->gateway_refund_id)->toBe('re_booking_test_123')
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($booking->fresh()->status)->toBe(BookingStatus::Refunded);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.stripe.com/v1/refunds'
        && $request['payment_intent'] === 'pi_booking_test_123'
        && $request['amount'] === 1200
        && $request['reason'] === 'requested_by_customer'
        && $request->hasHeader('Idempotency-Key'));
});

test('cancelling with a refund atomically cancels the booking and creates the Stripe refund', function () {
    config()->set('services.stripe.secret', 'sk_test_booking_actions');
    config()->set('mail.default', 'smtp');
    Http::fake([
        'https://api.stripe.com/v1/refunds' => Http::response([
            'id' => 're_cancel_booking_test',
            'status' => 'succeeded',
        ], 200),
    ]);
    Mail::fake();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    $showtime = Showtime::factory()->create([
        'starts_at' => now()->addDays(4),
        'ends_at' => now()->addDays(4)->addHours(2),
    ]);
    $booking = createBookingForAdmin($customer, $showtime, BookingStatus::Confirmed->value, 'CB-CANCEL-REFUND');
    Payment::forceCreate([
        'booking_id' => $booking->id,
        'gateway' => 'stripe',
        'gateway_payment_intent_id' => 'pi_cancel_booking_test',
        'idempotency_key' => Str::random(64),
        'amount_cents' => 1200,
        'status' => PaymentStatus::Succeeded,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin)
        ->postJson(route('admin.bookings.cancel', $booking), [
            'cancel_reason' => 'Customer request',
            'also_refund' => '1',
            'amount' => '12.00',
            'confirm_cancellation' => '1',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Booking cancelled. Seats released, tickets voided and audit log saved. Customer email sent. Refund processed successfully.');

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and(Refund::query()->sole()->status->value)->toBe('succeeded')
        ->and(Refund::query()->sole()->gateway_refund_id)->toBe('re_cancel_booking_test');
    Mail::assertSent(BookingCancelledMail::class);
});

test('an administrator can void a valid ticket with a required reason', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $booking = createBookingForAdmin($customer, $showtime, BookingStatus::Confirmed->value, 'CB-VOID-1');
    $seat = Seat::factory()->create(['screen_id' => $showtime->screen_id, 'label' => 'E3']);
    $item = BookingItem::forceCreate([
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'seat_label' => 'E3',
        'seat_type' => 'standard',
        'price_cents' => 1200,
    ]);
    $ticket = Ticket::create([
        'booking_item_id' => $item->id,
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'ticket_number' => 'T-VOID-1',
        'token' => Str::random(40),
    ]);

    $this->actingAs($admin)
        ->postJson(route('admin.bookings.tickets.void', [$booking, $ticket]), [
            'reason' => 'Duplicate ticket created',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Ticket voided. No refund was issued.');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Void)
        ->and(AuditLog::query()->where('action', 'ticket.voided')->exists())->toBeTrue();

    $this->postJson(route('admin.bookings.tickets.void', [$booking, $ticket]), [
        'reason' => 'Attempt to void again',
    ])->assertUnprocessable()->assertJsonValidationErrors('reason');
});

test('an administrator can resend a confirmation email only for an upcoming confirmed booking', function () {
    config()->set('mail.default', 'smtp');
    Mail::fake();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $booking = createBookingForAdmin($customer, $showtime, BookingStatus::Confirmed->value, 'CB-RESEND-1');

    $this->actingAs($admin)
        ->postJson(route('admin.bookings.resend-email', $booking))
        ->assertOk()
        ->assertJsonPath('message', 'Confirmation email sent to '.$customer->email.'.');

    Mail::assertSent(BookingConfirmedMail::class);
    expect(AuditLog::query()->where('action', 'booking.confirmation_resent')->exists())->toBeTrue();
});

test('resending an email reports when the active mail transport does not deliver messages', function () {
    config()->set('mail.default', 'log');
    Mail::fake();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $booking = createBookingForAdmin($customer, $showtime, BookingStatus::Confirmed->value, 'CB-RESEND-LOG');

    $this->actingAs($admin)
        ->postJson(route('admin.bookings.resend-email', $booking))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email')
        ->assertJsonPath(
            'errors.email.0',
            'Email delivery is not configured. The active mail transport does not deliver messages; configure SMTP or another delivery transport and try again.',
        );

    Mail::assertNothingSent();
});

test('cancellation still updates the booking when email delivery is not configured', function () {
    config()->set('mail.default', 'log');
    Mail::fake();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    $showtime = Showtime::factory()->create([
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHours(2),
    ]);
    $booking = createBookingForAdmin($customer, $showtime, BookingStatus::Confirmed->value, 'CB-CANCEL-LOG');

    $this->actingAs($admin)
        ->post(route('admin.bookings.cancel', $booking), [
            'cancel_reason' => 'Customer request',
            'confirm_cancellation' => '1',
        ])
        ->assertRedirect(route('admin.bookings.show', $booking))
        ->assertSessionHas(
            'status',
            'Booking cancelled. Seats released, tickets voided and audit log saved. Customer email could not be sent; check the mail configuration.',
        );

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
    Mail::assertNothingSent();
});

test('booking actions are hidden after the showtime has started', function () {
    config()->set('services.stripe.secret', null);
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    $showtime = Showtime::factory()->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);
    $booking = createBookingForAdmin($customer, $showtime, BookingStatus::Confirmed->value, 'CB-PAST-1');
    Payment::forceCreate([
        'booking_id' => $booking->id,
        'gateway' => 'stripe',
        'gateway_payment_intent_id' => 'pi_past_test',
        'idempotency_key' => Str::random(64),
        'amount_cents' => 1200,
        'status' => PaymentStatus::Succeeded,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertDontSee('Resend email')
        ->assertDontSee('Issue refund')
        ->assertDontSee('Cancel booking');
});
