<?php

use App\Actions\HoldSeatsAction;
use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\Role;
use App\Enums\SeatType;
use App\Enums\TicketStatus;
use App\Mail\BookingConfirmed;
use App\Mail\LatePaymentRefund;
use App\Mail\PaymentNotCompleted;
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
use App\Models\StripeEvent;
use App\Models\Ticket;
use App\Models\User;
use App\Services\BookingCancellationService;
use App\Services\Payments\StripeGateway;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Mockery;
use Stripe\Checkout\Session;
use Stripe\Exception\IdempotencyException;
use Stripe\Refund as StripeRefund;

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

test('non-customers are forbidden from customer booking routes', function (Role $role) {
    $user = User::factory()->create(['role' => $role]);
    $showtime = Showtime::factory()->create();

    $this->actingAs($user)
        ->get(route('bookings.index'))
        ->assertForbidden();

    $this->post(route('showtimes.holds.store', $showtime), ['seat_ids' => []])
        ->assertForbidden();

    $this->post(route('bookings.checkout.start', 'CB-TEST-REFERENCE'))
        ->assertForbidden();
})->with([
    'admin' => Role::Admin,
    'staff' => Role::Staff,
]);

function addSuccessfulStripePayment(Booking $booking): Payment
{
    $payment = new Payment([
        'booking_id' => $booking->id,
        'gateway' => 'stripe',
        'gateway_payment_intent_id' => 'pi_test_'.$booking->id,
        'idempotency_key' => Str::random(64),
        'currency' => 'USD',
    ]);
    $payment->forceFill([
        'amount_cents' => 1200,
        'status' => PaymentStatus::Succeeded,
        'paid_at' => now(),
    ]);
    $payment->save();

    return $payment;
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
    $pendingBooking->payments()->delete();
    $confirmedBooking = createCustomerBooking($user, BookingStatus::Confirmed);

    $this->actingAs($user)
        ->get(route('bookings.checkout', $pendingBooking->reference))
        ->assertOk()
        ->assertSee('Your seats are on hold')
        ->assertSee($pendingBooking->hold_expires_at->copy()->timezone(config('cinema.timezone'))->format('M j, Y \a\t H:i'))
        ->assertSee('Continue to secure Stripe checkout')
        ->assertDontSee('Checkout is not connected yet');

    $this->get(route('bookings.show', $pendingBooking->reference))
        ->assertOk()
        ->assertSee($pendingBooking->created_at->copy()->timezone(config('cinema.timezone'))->format('M j, Y \a\t H:i'))
        ->assertSee('Payment needed')
        ->assertSee('G7')
        ->assertSee('Your seats are being held for you')
        ->assertSee('Complete payment before then to confirm your booking.')
        ->assertSee('Continue to payment')
        ->assertSee(route('bookings.checkout', $pendingBooking->reference))
        ->assertDontSee('Check payment status')
        ->assertDontSee('Seat details have not been recorded')
        ->assertDontSee('No refund record');

    $this->get(route('bookings.processing', $pendingBooking->reference))
        ->assertOk()
        ->assertSee('Payment processing')
        ->assertSee('No payment was started for this booking.')
        ->assertSee('Continue to checkout')
        ->assertSee(route('bookings.checkout', $pendingBooking->reference));

    $this->get(route('bookings.confirmed', $confirmedBooking->reference))
        ->assertOk()
        ->assertSee('You’re going to the movies.')
        ->assertSee($confirmedBooking->showtime->movie->title)
        ->assertSee('T-'.$confirmedBooking->id.'-01')
        ->assertSee('View and print ticket')
        ->assertSee(route('bookings.tickets.show', [$confirmedBooking->reference, $confirmedBooking->tickets->first()->id]))
        ->assertDontSee('Total 12.00 USD');
});

test('verified customers can hold available seats and continue to Stripe test checkout', function () {
    $user = User::factory()->create();
    Setting::query()->create(['key' => 'hold_minutes', 'value' => '15']);
    Setting::query()->create(['key' => 'max_seats_per_booking', 'value' => '2']);
    Setting::query()->create(['key' => 'premium_surcharge_cents', 'value' => '350']);
    $showtime = Showtime::factory()->create(['base_price_cents' => 1000]);
    $standardSeat = Seat::factory()->for($showtime->screen)->create([
        'row_label' => 'A',
        'seat_number' => 1,
        'label' => 'A1',
        'type' => SeatType::Standard,
    ]);
    $premiumSeat = Seat::factory()->premium()->for($showtime->screen)->create([
        'row_label' => 'A',
        'seat_number' => 2,
        'label' => 'A2',
    ]);
    $extraSeat = Seat::factory()->for($showtime->screen)->create([
        'row_label' => 'B',
        'seat_number' => 3,
        'label' => 'B3',
    ]);
    $gateway = Mockery::mock(StripeGateway::class);
    $gateway->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn(Session::constructFrom([
            'id' => 'cs_test_booking',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_booking',
            'status' => 'open',
        ]));
    $gateway->shouldNotReceive('retrieveCheckoutSession');
    $this->app->instance(StripeGateway::class, $gateway);

    $this->actingAs($user);

    $this->get(route('showtimes.show', $showtime))
        ->assertOk()
        ->assertSee('data-hold-form', false)
        ->assertDontSee('Sign in to continue selecting seats')
        ->assertSee('data-seat-id="'.$standardSeat->id.'"', false)
        ->assertSee('data-premium-price="13.5"', false)
        ->assertSee('data-max-selection="2"', false)
        ->assertSee('Your 15-minute hold')
        ->assertSee('seat--spacer', false);

    $this->post(route('showtimes.holds.store', $showtime), [
        'seat_ids' => [$standardSeat->id, $premiumSeat->id, $extraSeat->id],
    ])
        ->assertSessionHasErrors('seat_ids');

    expect(Booking::query()->exists())->toBeFalse();

    $this->post(route('showtimes.holds.store', $showtime), [
        'seat_ids' => [$standardSeat->id, $premiumSeat->id],
    ])
        ->assertRedirect(route('bookings.checkout', Booking::query()->firstOrFail()->reference));

    $booking = Booking::query()->firstOrFail();
    expect($booking->total_cents)->toBe(2350)
        ->and($booking->seats_count)->toBe(2)
        ->and($booking->seatHolds()->count())->toBe(2)
        ->and($booking->hold_expires_at->betweenIncluded(now()->addMinutes(14), now()->addMinutes(16)))->toBeTrue();

    $this->get(route('bookings.checkout', $booking->reference))
        ->assertOk()
        ->assertSee('<strong>02 CHECKOUT</strong>', false)
        ->assertSee('A1')
        ->assertSee('A2')
        ->assertSee('Continue to secure Stripe checkout');

    $this->post(route('bookings.checkout.start', $booking->reference))
        ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_booking');

    expect($booking->payments()->firstOrFail()->gateway_session_id)->toBe('cs_test_booking');
});

test('a customer cannot hold a seat already held by another customer', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $seat = Seat::factory()->for($showtime->screen)->create([
        'row_label' => 'B',
        'seat_number' => 1,
        'label' => 'B1',
    ]);
    app(HoldSeatsAction::class)->execute($showtime, $firstUser, [$seat->id]);

    $this->actingAs($secondUser)
        ->from(route('showtimes.show', $showtime))
        ->post(route('showtimes.holds.store', $showtime), ['seat_ids' => [$seat->id]])
        ->assertRedirect(route('showtimes.show', $showtime))
        ->assertSessionHasErrors('seat_ids');

    expect(Booking::query()->count())->toBe(1)
        ->and(SeatHold::query()->where('status', HoldStatus::Active)->count())->toBe(1);
});

test('unverified customers cannot hold seats', function () {
    $user = User::factory()->unverified()->create();
    $showtime = Showtime::factory()->create();
    $seat = Seat::factory()->for($showtime->screen)->create();

    $this->actingAs($user)
        ->get(route('showtimes.show', $showtime))
        ->assertOk()
        ->assertSee('Verify your email to continue selecting seats');

    $this->actingAs($user)
        ->post(route('showtimes.holds.store', $showtime), ['seat_ids' => [$seat->id]])
        ->assertRedirect(route('verification.notice'));

    expect(Booking::query()->count())->toBe(0)
        ->and(SeatHold::query()->count())->toBe(0);
});

test('guests return to seat selection with their choices after logging in', function () {
    $user = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $seat = Seat::factory()->for($showtime->screen)->create([
        'row_label' => 'A',
        'seat_number' => 1,
        'label' => 'A1',
    ]);

    $this->get(route('showtimes.show', $showtime))
        ->assertOk()
        ->assertSee('Sign in to continue selecting seats')
        ->assertSee(route('showtimes.holds.prepare', $showtime), false);

    $this->post(route('showtimes.holds.prepare', $showtime), ['seat_ids' => [$seat->id]])
        ->assertRedirect(route('login'));

    $this->get(route('login'))->assertOk();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('showtimes.show', $showtime));

    $this->get(route('showtimes.show', $showtime))
        ->assertOk()
        ->assertSee('data-seat-id="'.$seat->id.'"', false)
        ->assertSee('class="seat seat--selected', false);

    expect(Booking::query()->count())->toBe(0)
        ->and(SeatHold::query()->count())->toBe(0);

    $this->post(route('showtimes.holds.store', $showtime), ['seat_ids' => [$seat->id]])
        ->assertRedirect(route('bookings.checkout', Booking::query()->firstOrFail()->reference));

    expect(Booking::query()->count())->toBe(1)
        ->and(SeatHold::query()->where('status', HoldStatus::Active)->count())->toBe(1);
});

test('unverified customers return to seat selection with their choices after verifying their email', function () {
    $user = User::factory()->unverified()->create();
    $showtime = Showtime::factory()->create();
    $seat = Seat::factory()->for($showtime->screen)->create([
        'row_label' => 'A',
        'seat_number' => 1,
        'label' => 'A1',
    ]);

    $this->actingAs($user)
        ->post(route('showtimes.holds.prepare', $showtime), ['seat_ids' => [$seat->id]])
        ->assertRedirect(route('verification.notice'));

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->get($verificationUrl);
    $response->assertRedirect(route('showtimes.show', $showtime));

    $this->get(route('showtimes.show', $showtime))
        ->assertOk()
        ->assertSee('data-seat-id="'.$seat->id.'"', false)
        ->assertSee('class="seat seat--selected', false);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and(Booking::query()->count())->toBe(0)
        ->and(SeatHold::query()->count())->toBe(0);

    $this->post(route('showtimes.holds.store', $showtime), ['seat_ids' => [$seat->id]])
        ->assertRedirect(route('bookings.checkout', Booking::query()->firstOrFail()->reference));

    expect(Booking::query()->count())->toBe(1)
        ->and(SeatHold::query()->where('status', HoldStatus::Active)->count())->toBe(1);
});

test('expired checkout holds release their seats and notify the customer', function () {
    Mail::fake();
    $user = User::factory()->create();
    $booking = createCustomerBooking($user, BookingStatus::PendingPayment);
    $payment = $booking->payments()->firstOrFail();
    $payment->update(['gateway_session_id' => 'cs_test_abandoned']);
    $booking->forceFill(['hold_expires_at' => now()->subMinute()])->save();
    $booking->seatHolds()->update(['expires_at' => now()->subMinute()]);
    $availableSeat = Seat::factory()->for($booking->showtime->screen)->create([
        'row_label' => 'H',
        'seat_number' => 8,
        'label' => 'H8',
    ]);

    app(HoldSeatsAction::class)->execute($booking->showtime, $user, [$availableSeat->id]);

    expect($booking->refresh()->status)->toBe(BookingStatus::Expired)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Expired)
        ->and($booking->seatHolds()->firstOrFail()->status)->toBe(HoldStatus::Expired);

    Mail::assertQueuedOnce(PaymentNotCompleted::class, function (PaymentNotCompleted $mail) use ($booking, $user) {
        return $mail->booking->is($booking)
            && $mail->hasTo($user->email)
            && str_contains($mail->render(), 'The seat hold has now been released')
            && str_contains($mail->render(), $booking->reference);
    });
});

test('a Stripe configuration failure is shown and leaves the active hold retryable', function () {
    config(['services.stripe.secret' => 'sk_live_not_a_real_key']);
    $user = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $seat = Seat::factory()->for($showtime->screen)->create([
        'row_label' => 'B',
        'seat_number' => 2,
        'label' => 'B2',
    ]);
    $booking = app(HoldSeatsAction::class)->execute($showtime, $user, [$seat->id]);

    $this->actingAs($user)
        ->from(route('bookings.checkout', $booking->reference))
        ->post(route('bookings.checkout.start', $booking->reference))
        ->assertRedirect(route('bookings.checkout', $booking->reference))
        ->assertSessionHasErrors('checkout');

    expect($booking->refresh()->isPendingPayment())->toBeTrue()
        ->and($booking->seatHolds()->where('status', HoldStatus::Active)->count())->toBe(1)
        ->and($booking->payments()->firstOrFail()->status)->toBe(PaymentStatus::Pending);
});

test('a Stripe idempotency conflict retries checkout with a fresh payment attempt', function () {
    $user = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $seat = Seat::factory()->for($showtime->screen)->create([
        'row_label' => 'B',
        'seat_number' => 3,
        'label' => 'B3',
    ]);
    $booking = app(HoldSeatsAction::class)->execute($showtime, $user, [$seat->id]);
    $attempt = 0;
    $gateway = Mockery::mock(StripeGateway::class);
    $gateway->shouldReceive('createCheckoutSession')
        ->twice()
        ->andReturnUsing(function () use (&$attempt): Session {
            $attempt++;

            if ($attempt === 1) {
                throw new IdempotencyException('Checkout parameters changed.');
            }

            return Session::constructFrom([
                'id' => 'cs_test_retry',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_retry',
                'status' => 'open',
            ]);
        });
    $this->app->instance(StripeGateway::class, $gateway);

    $this->actingAs($user)
        ->post(route('bookings.checkout.start', $booking->reference))
        ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_retry');

    $payments = $booking->payments()->orderBy('id')->get();
    expect($payments)->toHaveCount(2)
        ->and($payments[0]->status)->toBe(PaymentStatus::Expired)
        ->and($payments[1]->status)->toBe(PaymentStatus::Pending)
        ->and($payments[1]->gateway_session_id)->toBe('cs_test_retry')
        ->and($payments[0]->idempotency_key)->not->toBe($payments[1]->idempotency_key);
});

test('a paid Stripe checkout confirms the booking and creates tickets', function () {
    Mail::fake();
    $secret = 'whsec_test_checkout';
    config(['services.stripe.webhook_secret' => $secret]);
    $user = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $seat = Seat::factory()->for($showtime->screen)->create([
        'row_label' => 'C',
        'seat_number' => 3,
        'label' => 'C3',
    ]);
    $booking = app(HoldSeatsAction::class)->execute($showtime, $user, [$seat->id]);
    $payment = new Payment([
        'gateway' => 'stripe',
        'gateway_session_id' => null,
        'idempotency_key' => Str::random(64),
        'currency' => 'USD',
    ]);
    $payment->forceFill([
        'amount_cents' => $booking->total_cents,
        'status' => PaymentStatus::Pending,
    ]);
    $booking->payments()->save($payment);
    $payload = json_encode([
        'id' => 'evt_test_checkout_paid',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_paid',
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'amount_total' => $booking->total_cents,
                'currency' => 'usd',
                'payment_intent' => 'pi_test_paid',
                'metadata' => ['payment_id' => (string) $payment->getKey()],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

    $this->call('POST', route('stripe.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => $signature,
    ], $payload)
        ->assertOk()
        ->assertJson(['received' => true]);

    expect($booking->refresh()->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->tickets()->count())->toBe(1)
        ->and($booking->tickets()->firstOrFail()->ticket_number)->toBe('T-'.$booking->id.'-01')
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Succeeded)
        ->and($payment->gateway_session_id)->toBe('cs_test_paid')
        ->and($booking->seatHolds()->firstOrFail()->status)->toBe(HoldStatus::Converted);

    Mail::assertQueuedOnce(BookingConfirmed::class, function (BookingConfirmed $mail) use ($booking, $user) {
        return $mail->booking->is($booking)
            && $mail->hasTo($user->email)
            && str_contains($mail->render(), $booking->reference)
            && str_contains($mail->render(), 'T-'.$booking->id.'-01');
    });

    $duplicatePayload = json_encode([
        'id' => 'evt_test_checkout_paid_intent',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_paid',
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'amount' => $payment->amount_cents,
                'amount_received' => $payment->amount_cents,
                'currency' => 'usd',
                'metadata' => [
                    'booking_id' => (string) $booking->getKey(),
                    'payment_id' => (string) $payment->getKey(),
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
    $duplicateTimestamp = time();
    $duplicateSignature = 't='.$duplicateTimestamp.',v1='.hash_hmac(
        'sha256',
        $duplicateTimestamp.'.'.$duplicatePayload,
        $secret,
    );

    $this->call('POST', route('stripe.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => $duplicateSignature,
    ], $duplicatePayload)
        ->assertOk()
        ->assertJson(['received' => true]);

    Mail::assertQueuedCount(1);
});

test('a paid Stripe payment intent confirms its booking including a previously ignored event', function () {
    Mail::fake();
    $secret = 'whsec_test_payment_intent';
    config(['services.stripe.webhook_secret' => $secret]);
    $user = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $seat = Seat::factory()->for($showtime->screen)->create([
        'row_label' => 'E',
        'seat_number' => 5,
        'label' => 'E5',
    ]);
    $booking = app(HoldSeatsAction::class)->execute($showtime, $user, [$seat->id]);
    $payment = new Payment([
        'gateway' => 'stripe',
        'gateway_session_id' => 'cs_test_intent',
        'idempotency_key' => Str::random(64),
        'currency' => $booking->currency,
    ]);
    $payment->forceFill([
        'amount_cents' => $booking->total_cents,
        'status' => PaymentStatus::Pending,
    ]);
    $booking->payments()->save($payment);
    $eventId = 'evt_test_payment_intent_paid';
    $savedEvent = new StripeEvent([
        'stripe_event_id' => $eventId,
        'type' => 'payment_intent.succeeded',
    ]);
    $savedEvent->forceFill(['status' => 'ignored'])->save();
    $payload = json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_paid',
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'amount' => $payment->amount_cents,
                'amount_received' => $payment->amount_cents,
                'currency' => 'usd',
                'metadata' => [
                    'booking_id' => (string) $booking->getKey(),
                    'payment_id' => (string) $payment->getKey(),
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

    $this->call('POST', route('stripe.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => $signature,
    ], $payload)
        ->assertOk()
        ->assertJson(['received' => true]);

    expect($booking->refresh()->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->tickets()->count())->toBe(1)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Succeeded)
        ->and($payment->gateway_payment_intent_id)->toBe('pi_test_paid')
        ->and(StripeEvent::query()->where('stripe_event_id', $eventId)->firstOrFail()->status)->toBe('processed');

    Mail::assertQueuedOnce(BookingConfirmed::class);
});

test('a failed Stripe checkout releases its hold and sends one payment email', function () {
    Mail::fake();
    $secret = 'whsec_test_failed_checkout';
    config(['services.stripe.webhook_secret' => $secret]);
    $user = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $seat = Seat::factory()->for($showtime->screen)->create();
    $booking = app(HoldSeatsAction::class)->execute($showtime, $user, [$seat->id]);
    $payment = new Payment([
        'gateway' => 'stripe',
        'gateway_session_id' => 'cs_test_failed',
        'idempotency_key' => Str::random(64),
        'currency' => $booking->currency,
    ]);
    $payment->forceFill([
        'amount_cents' => $booking->total_cents,
        'status' => PaymentStatus::Pending,
    ]);
    $booking->payments()->save($payment);
    $payload = json_encode([
        'id' => 'evt_test_failed_checkout',
        'object' => 'event',
        'type' => 'checkout.session.async_payment_failed',
        'data' => [
            'object' => [
                'id' => 'cs_test_failed',
                'object' => 'checkout.session',
            ],
        ],
    ], JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

    $this->call('POST', route('stripe.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => $signature,
    ], $payload)
        ->assertOk()
        ->assertJson(['received' => true]);

    expect($booking->refresh()->status)->toBe(BookingStatus::Failed)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Failed)
        ->and($booking->seatHolds()->firstOrFail()->status)->toBe(HoldStatus::Released);

    Mail::assertQueuedOnce(PaymentNotCompleted::class, function (PaymentNotCompleted $mail) use ($booking, $user) {
        return $mail->booking->is($booking)
            && $mail->hasTo($user->email)
            && str_contains($mail->render(), 'The seat hold has now been released');
    });
});

test('an abandoned Stripe checkout expires its hold and emails the customer', function () {
    Mail::fake();
    $secret = 'whsec_test_expired_checkout';
    config(['services.stripe.webhook_secret' => $secret]);
    $user = User::factory()->create();
    $booking = createCustomerBooking($user, BookingStatus::PendingPayment);
    $payment = $booking->payments()->firstOrFail();
    $payment->update(['gateway_session_id' => 'cs_test_expired']);
    $payload = json_encode([
        'id' => 'evt_test_expired_checkout',
        'object' => 'event',
        'type' => 'checkout.session.expired',
        'data' => [
            'object' => [
                'id' => 'cs_test_expired',
                'object' => 'checkout.session',
            ],
        ],
    ], JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

    $this->call('POST', route('stripe.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => $signature,
    ], $payload)
        ->assertOk()
        ->assertJson(['received' => true]);

    expect($booking->refresh()->status)->toBe(BookingStatus::Expired)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Expired)
        ->and($booking->seatHolds()->firstOrFail()->status)->toBe(HoldStatus::Expired);

    Mail::assertQueuedOnce(PaymentNotCompleted::class, function (PaymentNotCompleted $mail) use ($booking, $user) {
        return $mail->booking->is($booking)
            && $mail->hasTo($user->email)
            && str_contains($mail->render(), 'Your payment was not completed');
    });
});

test('unrelated succeeded Stripe payment intents are acknowledged without affecting bookings', function () {
    $secret = 'whsec_test_unrelated_payment_intent';
    config(['services.stripe.webhook_secret' => $secret]);
    $payload = json_encode([
        'id' => 'evt_test_unrelated_payment_intent',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_unrelated',
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'amount_received' => 1200,
                'currency' => 'usd',
                'metadata' => [],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

    $this->call('POST', route('stripe.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => $signature,
    ], $payload)
        ->assertOk()
        ->assertJson(['received' => true]);

    expect(Booking::query()->count())->toBe(0)
        ->and(StripeEvent::query()->where('stripe_event_id', 'evt_test_unrelated_payment_intent')
            ->firstOrFail()->status)->toBe('ignored');
});

test('late Stripe payments are automatically refunded after the seat hold expires', function () {
    Mail::fake();
    $secret = 'whsec_test_late_checkout';
    config(['services.stripe.webhook_secret' => $secret]);
    $user = User::factory()->create();
    $showtime = Showtime::factory()->create();
    $seat = Seat::factory()->for($showtime->screen)->create([
        'row_label' => 'D',
        'seat_number' => 4,
        'label' => 'D4',
    ]);
    $booking = app(HoldSeatsAction::class)->execute($showtime, $user, [$seat->id]);
    $payment = new Payment([
        'gateway' => 'stripe',
        'gateway_session_id' => 'cs_test_late',
        'idempotency_key' => Str::random(64),
        'currency' => 'USD',
    ]);
    $payment->forceFill([
        'amount_cents' => $booking->total_cents,
        'status' => PaymentStatus::Pending,
    ]);
    $booking->payments()->save($payment);
    $this->travel(11)->minutes();

    $gateway = Mockery::mock(StripeGateway::class);
    $gateway->shouldReceive('createRefund')
        ->once()
        ->with('pi_test_late', $booking->total_cents, 'late-payment-'.$payment->id)
        ->andReturn(StripeRefund::constructFrom([
            'id' => 're_late_'.$booking->id,
            'status' => 'succeeded',
        ]));
    $this->app->instance(StripeGateway::class, $gateway);
    $payload = json_encode([
        'id' => 'evt_test_late_checkout',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_late',
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'amount_total' => $booking->total_cents,
                'currency' => 'usd',
                'payment_intent' => 'pi_test_late',
            ],
        ],
    ], JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

    $this->call('POST', route('stripe.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => $signature,
    ], $payload)
        ->assertOk()
        ->assertJson(['received' => true]);

    $paymentIntentPayload = json_encode([
        'id' => 'evt_test_late_payment_intent',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_late',
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'amount' => $booking->total_cents,
                'amount_received' => $booking->total_cents,
                'currency' => 'usd',
                'metadata' => [
                    'booking_id' => (string) $booking->getKey(),
                    'payment_id' => (string) $payment->getKey(),
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
    $paymentIntentTimestamp = time();
    $paymentIntentSignature = 't='.$paymentIntentTimestamp.',v1='.hash_hmac(
        'sha256',
        $paymentIntentTimestamp.'.'.$paymentIntentPayload,
        $secret,
    );

    $this->call('POST', route('stripe.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => $paymentIntentSignature,
    ], $paymentIntentPayload)
        ->assertOk()
        ->assertJson(['received' => true]);

    $refund = Refund::query()->where('booking_id', $booking->getKey())->firstOrFail();
    expect($booking->refresh()->status)->toBe(BookingStatus::Refunded)
        ->and($booking->tickets()->count())->toBe(0)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($refund->reason)->toBe('late_payment')
        ->and($refund->status)->toBe(RefundStatus::Succeeded);

    Mail::assertQueuedOnce(LatePaymentRefund::class, function (LatePaymentRefund $mail) use ($booking, $user) {
        return $mail->refund->booking->is($booking)
            && $mail->hasTo($user->email)
            && str_contains($mail->render(), 'requested an automatic refund')
            && str_contains($mail->render(), $booking->reference);
    });
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

    $this->get(route('bookings.processing', $booking->reference))
        ->assertOk()
        ->assertSee('Your seat hold has expired.')
        ->assertDontSee('Continue to checkout');

    $this->get(route('bookings.confirmed', $booking->reference))
        ->assertRedirect(route('bookings.show', $booking->reference));
});

test('customers can view and print their saved ticket with a local QR code', function () {
    $user = User::factory()->create();
    $booking = createCustomerBooking($user, BookingStatus::Confirmed);
    $ticket = $booking->tickets()->firstOrFail();

    expect($ticket->token)->toMatch('/^[a-f0-9]{40}$/');

    $this->actingAs($user)
        ->get(route('bookings.tickets.show', [$booking->reference, $ticket->id]))
        ->assertOk()
        ->assertSee($ticket->ticket_number)
        ->assertDontSee($ticket->token, false)
        ->assertSee('data:image/png;base64', false)
        ->assertSee('Print or save this ticket');
});

test('a customer cannot print another customer’s ticket', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    $privateBooking = createCustomerBooking($anotherUser, BookingStatus::Confirmed);
    $ticket = $privateBooking->tickets()->firstOrFail();

    $this->actingAs($user)
        ->get(route('bookings.tickets.show', [$privateBooking->reference, $ticket->id]))
        ->assertNotFound();
});

test('customers can cancel an eligible booking and receive a saved Stripe refund', function () {
    $user = User::factory()->create();
    $booking = createCustomerBooking($user, BookingStatus::Confirmed);
    $payment = addSuccessfulStripePayment($booking);
    $ticket = $booking->tickets()->firstOrFail();
    $gateway = Mockery::mock(StripeGateway::class);
    $gateway->shouldReceive('createRefund')
        ->once()
        ->with($payment->gateway_payment_intent_id, 1200, Mockery::type('string'))
        ->andReturn(StripeRefund::constructFrom([
            'id' => 're_test_'.$booking->id,
            'status' => 'succeeded',
        ]));
    $this->app->instance(StripeGateway::class, $gateway);

    $this->actingAs($user)
        ->from(route('bookings.cancel', $booking->reference))
        ->post(route('bookings.cancel.request', $booking->reference))
        ->assertRedirect(route('bookings.show', $booking->reference))
        ->assertSessionHas('status');

    $booking->refresh();
    $ticket->refresh();
    $payment->refresh();
    $refund = Refund::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($booking->status)->toBe(BookingStatus::Refunded)
        ->and($ticket->status)->toBe(TicketStatus::Void)
        ->and($payment->status)->toBe(PaymentStatus::Refunded)
        ->and($refund->status)->toBe(RefundStatus::Succeeded)
        ->and($refund->gateway_refund_id)->toBe('re_test_'.$booking->id);
});

test('pending Stripe refunds cancel the tickets and update when Stripe confirms success', function () {
    $user = User::factory()->create();
    $booking = createCustomerBooking($user, BookingStatus::Confirmed);
    $payment = addSuccessfulStripePayment($booking);
    $ticket = $booking->tickets()->firstOrFail();
    $gateway = Mockery::mock(StripeGateway::class);
    $gateway->shouldReceive('createRefund')
        ->once()
        ->andReturn(StripeRefund::constructFrom([
            'id' => 're_pending_'.$booking->id,
            'status' => 'pending',
        ]));
    $this->app->instance(StripeGateway::class, $gateway);

    $this->actingAs($user)
        ->post(route('bookings.cancel.request', $booking->reference))
        ->assertRedirect(route('bookings.show', $booking->reference));

    $booking->refresh();
    $ticket->refresh();
    $refund = Refund::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($booking->status)->toBe(BookingStatus::Cancelled)
        ->and($ticket->status)->toBe(TicketStatus::Void)
        ->and($refund->status)->toBe(RefundStatus::Pending);

    app(BookingCancellationService::class)
        ->updateStripeRefund($refund->gateway_refund_id, 'succeeded');

    expect($booking->refresh()->status)->toBe(BookingStatus::Refunded)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($refund->refresh()->status)->toBe(RefundStatus::Succeeded);
});

test('bookings inside the cancellation window cannot start a Stripe refund', function () {
    $user = User::factory()->create();
    $booking = createCustomerBooking($user, BookingStatus::Confirmed);
    $booking->showtime->update([
        'starts_at' => now()->addHours(12),
        'ends_at' => now()->addHours(14),
    ]);
    Setting::query()->create(['key' => 'cancel_window_hours', 'value' => '24']);
    addSuccessfulStripePayment($booking);
    $gateway = Mockery::mock(StripeGateway::class);
    $gateway->shouldNotReceive('createRefund');
    $this->app->instance(StripeGateway::class, $gateway);

    $this->actingAs($user)
        ->from(route('bookings.cancel', $booking->reference))
        ->post(route('bookings.cancel.request', $booking->reference))
        ->assertRedirect(route('bookings.cancel', $booking->reference))
        ->assertSessionHasErrors('booking');

    expect($booking->refresh()->status)->toBe(BookingStatus::Confirmed)
        ->and(Refund::query()->where('booking_id', $booking->id)->exists())->toBeFalse();
});

test('Stripe refund requests require a test-mode secret key', function () {
    config(['services.stripe.secret' => 'sk_live_not_a_real_key']);

    expect(fn () => app(StripeGateway::class)->createRefund('pi_test', 1200, 'request-key'))
        ->toThrow(RuntimeException::class);
});
