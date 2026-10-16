<?php

use App\Actions\Admin\CancelBookingAction;
use App\Actions\Admin\CancelShowtimeAction;
use App\Actions\Admin\CreateStaffUserAction;
use App\Actions\Admin\IssueRefundAction;
use App\Actions\Admin\ProcessRefundAction;
use App\Actions\Admin\ReplayStripeEventAction;
use App\Actions\Admin\ResendBookingEmailAction;
use App\Actions\Admin\RetryRefundAction;
use App\Actions\Admin\UpdateSettingsAction;
use App\Actions\Admin\VoidTicketAction;
use App\Actions\CancelBookingAction as CustomerCancelBookingAction;
use App\Actions\CreateCheckoutAction;
use App\Actions\ExpireHoldsAction;
use App\Actions\HandleLatePaymentAction;
use App\Actions\HandlePaymentFailureAction;
use App\Actions\HoldSeatsAction;
use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\Role;
use App\Enums\SeatType;
use App\Enums\ShowtimeStatus;
use App\Enums\TicketStatus;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Cinema;
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
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentResult;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($this->admin);
});

test('hold seats creates an expiring pending booking for at most six seats', function () {
    $fixture = makeAdminShowtimeFixture();
    $booking = app(HoldSeatsAction::class)->execute(
        $fixture['customer'],
        $fixture['showtime'],
        [$fixture['seat']->id],
    );

    expect($booking->status)->toBe(BookingStatus::PendingPayment)
        ->and($booking->total_cents)->toBe(2500)
        ->and($booking->hold_expires_at->isFuture())->toBeTrue()
        ->and($booking->items)->toHaveCount(1)
        ->and($booking->seatHolds)->toHaveCount(1);

    expect(fn () => app(HoldSeatsAction::class)->execute(
        $fixture['customer'],
        $fixture['showtime'],
        array_fill(0, 7, $fixture['seat']->id),
    ))->toThrow(ValidationException::class);
});

test('checkout creates a pending Stripe payment without confirming the booking', function () {
    $fixture = makeAdminBookingFixture(status: BookingStatus::PendingPayment);
    SeatHold::query()->create([
        'hold_token' => (string) Str::uuid(),
        'showtime_id' => $fixture['showtime']->id,
        'seat_id' => $fixture['seat']->id,
        'user_id' => $fixture['customer']->id,
        'booking_id' => $fixture['booking']->id,
        'price_cents' => 2500,
        'status' => HoldStatus::Active,
        'expires_at' => now()->addMinutes(10),
    ]);
    $this->mock(PaymentGateway::class, fn ($mock) => $mock
        ->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn(new PaymentResult(true, id: 'cs_test_123', url: 'https://checkout.test/session')));

    $result = app(CreateCheckoutAction::class)->execute(
        $fixture['booking'],
        'https://cinebook.test/success',
        'https://cinebook.test/cancel',
    );

    expect($result['checkout_url'])->toBe('https://checkout.test/session')
        ->and($fixture['booking']->fresh()->status)->toBe(BookingStatus::PendingPayment)
        ->and($fixture['booking']->payments()->first()->status)->toBe(PaymentStatus::Pending);
});

test('expired holds release the pending booking inventory', function () {
    $fixture = makeAdminBookingFixture(status: BookingStatus::PendingPayment);
    SeatHold::query()->create([
        'hold_token' => (string) Str::uuid(),
        'showtime_id' => $fixture['showtime']->id,
        'seat_id' => $fixture['seat']->id,
        'user_id' => $fixture['customer']->id,
        'booking_id' => $fixture['booking']->id,
        'price_cents' => 2500,
        'status' => HoldStatus::Active,
        'expires_at' => now()->subMinute(),
    ]);

    expect(app(ExpireHoldsAction::class)->execute())->toBe(1)
        ->and($fixture['booking']->fresh()->status)->toBe(BookingStatus::Expired)
        ->and($fixture['booking']->items()->first()->status)->toBe('cancelled')
        ->and($fixture['booking']->seatHolds()->first()->status)->toBe(HoldStatus::Expired);
});

test('payment failure releases pending booking inventory', function () {
    $fixture = makeAdminBookingFixture(status: BookingStatus::PendingPayment);
    SeatHold::query()->create([
        'hold_token' => (string) Str::uuid(),
        'showtime_id' => $fixture['showtime']->id,
        'seat_id' => $fixture['seat']->id,
        'user_id' => $fixture['customer']->id,
        'booking_id' => $fixture['booking']->id,
        'price_cents' => 2500,
        'status' => HoldStatus::Active,
        'expires_at' => now()->addMinutes(10),
    ]);

    $booking = app(HandlePaymentFailureAction::class)->execute($fixture['payment'], 'Card declined');

    expect($booking->status)->toBe(BookingStatus::Failed)
        ->and($fixture['payment']->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($fixture['booking']->items()->first()->status)->toBe('cancelled')
        ->and($fixture['booking']->seatHolds()->first()->status)->toBe(HoldStatus::Released);
});

test('late successful payments expire the booking and initiate a refund', function () {
    Queue::fake();
    $fixture = makeAdminBookingFixture(status: BookingStatus::PendingPayment);
    SeatHold::query()->create([
        'hold_token' => (string) Str::uuid(),
        'showtime_id' => $fixture['showtime']->id,
        'seat_id' => $fixture['seat']->id,
        'user_id' => $fixture['customer']->id,
        'booking_id' => $fixture['booking']->id,
        'price_cents' => 2500,
        'status' => HoldStatus::Expired,
        'expires_at' => now()->subMinute(),
        'released_at' => now()->subMinute(),
    ]);

    $booking = app(HandleLatePaymentAction::class)->execute($fixture['payment'], 'pi_late_payment');

    expect($booking->status)->toBe(BookingStatus::Expired)
        ->and($fixture['payment']->fresh()->status)->toBe(PaymentStatus::Succeeded)
        ->and($fixture['booking']->refunds()->first()->status)->toBe(RefundStatus::Pending);
});

test('a timely successful webhook confirms and issues one ticket per seat', function () {
    Mail::fake();
    $fixture = makeAdminBookingFixture(status: BookingStatus::PendingPayment);
    SeatHold::query()->create([
        'hold_token' => (string) Str::uuid(),
        'showtime_id' => $fixture['showtime']->id,
        'seat_id' => $fixture['seat']->id,
        'user_id' => $fixture['customer']->id,
        'booking_id' => $fixture['booking']->id,
        'price_cents' => 2500,
        'status' => HoldStatus::Active,
        'expires_at' => now()->addMinutes(10),
    ]);

    $booking = app(HandleLatePaymentAction::class)->execute($fixture['payment'], 'pi_success');

    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($fixture['payment']->fresh()->status)->toBe(PaymentStatus::Succeeded)
        ->and($fixture['booking']->tickets()->count())->toBe(1)
        ->and(strlen($fixture['booking']->tickets()->first()->token))->toBe(40);
});

test('staff creation always assigns the staff role and records an audit event', function () {
    $user = app(CreateStaffUserAction::class)->execute('Front Desk', 'frontdesk@example.test', 'secret-pass');

    expect($user->role)->toBe(Role::Staff)
        ->and($user->role)->not->toBe(Role::Admin);
    expect(AuditLog::query()->where('action', 'staff.created')->count())->toBe(1);
});

test('refund issuance cannot exceed the unpaid refund balance', function () {
    $fixture = makeAdminBookingFixture(status: BookingStatus::Confirmed, paymentStatus: PaymentStatus::Succeeded);
    $refund = app(IssueRefundAction::class)->execute($fixture['booking'], 2500, 'customer_request', $this->admin);

    expect($refund->status)->toBe(RefundStatus::Pending)
        ->and($refund->amount_cents)->toBe(2500);
    expect(fn () => app(IssueRefundAction::class)->execute($fixture['booking'], 1, 'over_refund', $this->admin))
        ->toThrow(ValidationException::class);
});

test('refund processing records successful Stripe response', function () {
    $fixture = makeAdminBookingFixture(status: BookingStatus::Confirmed, paymentStatus: PaymentStatus::Succeeded);
    $refund = createAdminRefund($fixture['booking'], $fixture['payment']);
    $this->mock(PaymentGateway::class, fn ($mock) => $mock
        ->shouldReceive('createRefund')
        ->once()
        ->andReturn(new PaymentResult(true, id: 're_test_123')));

    $processed = app(ProcessRefundAction::class)->execute($refund);

    expect($processed->status)->toBe(RefundStatus::Succeeded)
        ->and($processed->gateway_refund_id)->toBe('re_test_123')
        ->and($processed->processed_at)->not->toBeNull();
});

test('retrying a failed refund uses a fresh idempotency key', function () {
    $fixture = makeAdminBookingFixture(status: BookingStatus::Confirmed, paymentStatus: PaymentStatus::Succeeded);
    $refund = createAdminRefund($fixture['booking'], $fixture['payment'], RefundStatus::Failed);
    $oldKey = $refund->idempotency_key;
    $this->mock(PaymentGateway::class, fn ($mock) => $mock
        ->shouldReceive('createRefund')
        ->once()
        ->andReturn(new PaymentResult(true, id: 're_retry_123')));

    $retried = app(RetryRefundAction::class)->execute($refund);

    expect($retried->status)->toBe(RefundStatus::Succeeded)
        ->and($retried->idempotency_key)->not->toBe($oldKey);
});

test('booking cancellation applies refunds and voids tickets', function () {
    Mail::fake();
    $fixture = makeAdminBookingFixture(status: BookingStatus::Confirmed, paymentStatus: PaymentStatus::Succeeded);
    $ticket = createAdminTicket($fixture['booking'], $fixture['item']);

    $cancelled = app(CancelBookingAction::class)->execute($fixture['booking'], 'Customer request', actor: $this->admin);

    expect($cancelled->status)->toBe(BookingStatus::Cancelled)
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Voided)
        ->and($fixture['booking']->refunds()->first()->status)->toBe(RefundStatus::Pending);
});

test('customer cancellation is rejected inside the two-hour window', function () {
    $fixture = makeAdminBookingFixture(status: BookingStatus::PendingPayment);
    $fixture['showtime']->forceFill([
        'starts_at' => now()->addMinutes(90),
        'ends_at' => now()->addMinutes(210),
    ])->save();

    expect(fn () => app(CustomerCancelBookingAction::class)->execute(
        $fixture['booking'],
        $fixture['customer'],
    ))->toThrow(ValidationException::class);
});

test('an administrator can override the two-hour cancellation cutoff with a reason', function () {
    $fixture = makeAdminBookingFixture(status: BookingStatus::PendingPayment);
    $fixture['showtime']->forceFill([
        'starts_at' => now()->addMinutes(90),
        'ends_at' => now()->addMinutes(210),
    ])->save();

    $cancelled = app(CancelBookingAction::class)->execute(
        $fixture['booking'],
        'Emergency maintenance',
        true,
        $this->admin,
    );

    expect($cancelled->status)->toBe(BookingStatus::Cancelled);
});

test('cancelling a showtime cancels its bookings', function () {
    Mail::fake();
    $fixture = makeAdminBookingFixture(status: BookingStatus::Confirmed, paymentStatus: PaymentStatus::Succeeded);

    $cancelled = app(CancelShowtimeAction::class)->execute(
        $fixture['showtime'],
        'Technical maintenance',
        $this->admin,
    );

    expect($cancelled->status)->toBe(ShowtimeStatus::Cancelled)
        ->and($fixture['booking']->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($fixture['booking']->refunds()->count())->toBe(1);
});

test('resending a booking email records the administrative action', function () {
    Mail::fake();
    $fixture = makeAdminBookingFixture(status: BookingStatus::Confirmed);

    app(ResendBookingEmailAction::class)->execute($fixture['booking']);

    expect(AuditLog::query()->where('action', 'booking.email_resent')->count())->toBe(1);
});

test('failed Stripe events can be replayed', function () {
    Queue::fake();
    $event = StripeEvent::query()->create([
        'stripe_event_id' => 'evt_failed_123',
        'type' => 'payment_intent.succeeded',
        'status' => 'failed',
        'error_message' => 'temporary',
    ]);

    app(ReplayStripeEventAction::class)->execute($event);

    expect($event->fresh()->status)->toBe('received')
        ->and($event->fresh()->error_message)->toBeNull();
});

test('settings updates enforce booking rules and invalidate cache', function () {
    Cache::put('cinebook.settings', ['cinema_name' => 'Old Name']);

    app(UpdateSettingsAction::class)->execute([
        'cinema_name' => 'CineBook Central',
        'seat_hold_minutes' => '10',
        'max_seats_per_booking' => '6',
        'sales_close_minutes' => '15',
        'customer_cancel_hours' => '2',
    ]);

    expect(Setting::query()->where('key', 'cinema_name')->value('value'))->toBe('CineBook Central')
        ->and(app(SettingsService::class)->get('cinema_name'))->toBe('CineBook Central');
    expect(AuditLog::query()->where('action', 'setting.updated')->count())->toBe(5);
});

test('valid tickets can be voided with an audit event', function () {
    $fixture = makeAdminBookingFixture(status: BookingStatus::Confirmed);
    $ticket = createAdminTicket($fixture['booking'], $fixture['item']);

    $voided = app(VoidTicketAction::class)->execute($ticket);

    expect($voided->status)->toBe(TicketStatus::Voided)
        ->and(AuditLog::query()->where('action', 'ticket.voided')->count())->toBe(1);
});

function makeAdminShowtimeFixture(): array
{
    $customer = User::factory()->create(['role' => Role::Customer]);
    $cinema = Cinema::query()->create(['name' => 'Test Cinema']);
    $screen = Screen::query()->create([
        'cinema_id' => $cinema->id,
        'name' => 'Screen 1',
        'rows_count' => 1,
        'seats_per_row' => 1,
        'is_active' => true,
    ]);
    $seat = Seat::query()->create([
        'screen_id' => $screen->id,
        'row_label' => 'A',
        'seat_number' => 1,
        'label' => 'A1',
        'type' => SeatType::Standard,
        'is_disabled' => false,
    ]);
    $movie = Movie::query()->create([
        'title' => 'Test Feature',
        'slug' => 'test-feature-'.Str::lower(Str::random(6)),
        'synopsis' => 'A test movie.',
        'duration_minutes' => 100,
        'status' => 'now_showing',
    ]);
    $showtime = Showtime::query()->create([
        'movie_id' => $movie->id,
        'screen_id' => $screen->id,
        'starts_at' => now()->addHours(4),
        'ends_at' => now()->addHours(6),
        'base_price_cents' => 2500,
        'status' => ShowtimeStatus::Scheduled,
    ]);

    return compact('customer', 'cinema', 'screen', 'seat', 'movie', 'showtime');
}

function makeAdminBookingFixture(
    BookingStatus $status = BookingStatus::PendingPayment,
    PaymentStatus $paymentStatus = PaymentStatus::Pending,
): array {
    $context = makeAdminShowtimeFixture();
    $booking = Booking::query()->create([
        'reference' => 'CB-'.Str::upper(Str::random(12)),
        'user_id' => $context['customer']->id,
        'showtime_id' => $context['showtime']->id,
        'status' => $status,
        'seats_count' => 1,
        'total_cents' => 2500,
        'currency' => 'USD',
        'hold_expires_at' => now()->addMinutes(10),
        'confirmed_at' => $status === BookingStatus::Confirmed ? now() : null,
    ]);
    $item = BookingItem::query()->create([
        'booking_id' => $booking->id,
        'showtime_id' => $context['showtime']->id,
        'seat_id' => $context['seat']->id,
        'seat_label' => 'A1',
        'seat_type' => SeatType::Standard,
        'price_cents' => 2500,
        'status' => 'active',
    ]);
    $payment = Payment::query()->create([
        'booking_id' => $booking->id,
        'gateway' => 'stripe',
        'gateway_payment_intent_id' => 'pi_'.Str::lower(Str::random(16)),
        'idempotency_key' => (string) Str::uuid(),
        'amount_cents' => 2500,
        'currency' => 'USD',
        'status' => $paymentStatus,
        'paid_at' => $paymentStatus === PaymentStatus::Succeeded ? now() : null,
    ]);

    return $context + compact('booking', 'item', 'payment');
}

function createAdminRefund(Booking $booking, Payment $payment, RefundStatus $status = RefundStatus::Pending): Refund
{
    return Refund::query()->create([
        'payment_id' => $payment->id,
        'booking_id' => $booking->id,
        'amount_cents' => 1000,
        'currency' => 'USD',
        'status' => $status,
        'reason' => 'test_refund',
        'idempotency_key' => (string) Str::uuid(),
    ]);
}

function createAdminTicket(Booking $booking, BookingItem $item): Ticket
{
    return Ticket::query()->create([
        'booking_item_id' => $item->id,
        'booking_id' => $booking->id,
        'showtime_id' => $booking->showtime_id,
        'ticket_number' => 'CBT-'.Str::upper(Str::random(12)),
        'token' => Str::lower(Str::random(40)),
        'status' => TicketStatus::Valid,
    ]);
}
