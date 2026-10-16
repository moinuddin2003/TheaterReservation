<?php

use App\Enums\BookingStatus;
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
use App\Models\Setting;
use App\Models\Showtime;
use App\Models\StripeEvent;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Models\User;
use App\Services\Admin\UserActivity;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentResult;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

function makeAdminOperationsFixture(?User $actor = null): array
{
    $customer = User::factory()->create(['role' => Role::Customer]);
    $staff = User::factory()->create(['role' => Role::Staff]);
    $cinema = Cinema::query()->create(['name' => 'Operations Cinema']);
    $screen = Screen::query()->create([
        'cinema_id' => $cinema->id,
        'name' => 'Operations Screen',
        'rows_count' => 1,
        'seats_per_row' => 1,
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
        'title' => 'Operations Feature',
        'slug' => 'operations-'.Str::lower(Str::random(8)),
        'synopsis' => 'Operations feature synopsis.',
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
    $booking = Booking::query()->create([
        'reference' => 'OPS-'.Str::upper(Str::random(12)),
        'user_id' => $customer->id,
        'showtime_id' => $showtime->id,
        'status' => BookingStatus::Confirmed,
        'seats_count' => 1,
        'total_cents' => 2500,
        'currency' => 'USD',
        'hold_expires_at' => now()->addMinutes(10),
        'confirmed_at' => now(),
    ]);
    $item = BookingItem::query()->create([
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
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
        'status' => PaymentStatus::Succeeded,
        'paid_at' => now(),
    ]);
    $refund = Refund::query()->create([
        'payment_id' => $payment->id,
        'booking_id' => $booking->id,
        'amount_cents' => 1000,
        'currency' => 'USD',
        'status' => RefundStatus::Pending,
        'reason' => 'test_refund',
        'idempotency_key' => (string) Str::uuid(),
        'initiated_by' => $actor?->id,
    ]);
    $ticket = Ticket::query()->create([
        'booking_item_id' => $item->id,
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'ticket_number' => 'CBT-'.Str::upper(Str::random(12)),
        'token' => Str::lower(Str::random(40)),
        'status' => TicketStatus::Valid,
    ]);

    return compact('customer', 'staff', 'cinema', 'screen', 'seat', 'movie', 'showtime', 'booking', 'item', 'payment', 'refund', 'ticket');
}

test('admin can search and open booking payment refund ticket user stripe and audit detail pages', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $data = makeAdminOperationsFixture($admin);
    $scan = TicketScan::query()->create([
        'ticket_id' => $data['ticket']->id,
        'showtime_id' => $data['showtime']->id,
        'token_hash' => hash('sha256', $data['ticket']->token),
        'scanned_by' => $data['staff']->id,
        'result' => 'valid',
        'message' => 'Accepted',
        'ip_address' => '127.0.0.1',
        'scanned_at' => now(),
    ]);
    $event = StripeEvent::query()->create([
        'stripe_event_id' => 'evt_'.Str::lower(Str::random(16)),
        'type' => 'payment_intent.succeeded',
        'status' => 'failed',
        'error_message' => 'test error',
    ]);
    $log = AuditLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'booking.confirmed',
        'auditable_type' => 'Booking',
        'auditable_id' => $data['booking']->id,
        'before' => ['status' => 'pending_payment'],
        'after' => ['status' => 'confirmed'],
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    $this->actingAs($admin);
    foreach ([
        ['admin.bookings.index', []],
        ['admin.bookings.index', ['q' => $data['booking']->reference, 'status' => 'confirmed']],
        ['admin.bookings.show', [$data['booking']]],
        ['admin.payments.index', ['q' => $data['booking']->reference, 'status' => 'succeeded']],
        ['admin.payments.show', [$data['payment']]],
        ['admin.refunds.index', ['status' => 'pending', 'q' => $data['booking']->reference]],
        ['admin.refunds.show', [$data['refund']]],
        ['admin.tickets.index', ['tab' => 'tickets', 'q' => $data['ticket']->ticket_number]],
        ['admin.tickets.index', ['tab' => 'scans', 'result' => 'valid', 'staff' => $data['staff']->id]],
        ['admin.tickets.show', [$data['ticket']]],
        ['admin.users.index', ['tab' => 'customers', 'q' => $data['customer']->email]],
        ['admin.users.show', [$data['customer']]],
        ['admin.settings.edit', ['tab' => 'general']],
        ['admin.settings.edit', ['tab' => 'rules']],
        ['admin.settings.edit', ['tab' => 'payments']],
        ['admin.settings.edit', ['tab' => 'emails']],
        ['admin.stripe-events.index', ['type' => $event->type, 'status' => 'failed']],
        ['admin.stripe-events.show', [$event]],
        ['admin.audit-logs.index', ['actor' => $admin->id, 'entity' => 'Booking']],
        ['admin.audit-logs.show', [$log]],
    ] as [$route, $parameters]) {
        $this->get(route($route, $parameters))->assertOk();
    }

    expect($scan->exists)->toBeTrue();
});

test('customers and staff cannot access added details or submit added admin actions', function (Role $role) {
    $data = makeAdminOperationsFixture();
    $other = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs(User::factory()->create(['role' => $role]));

    foreach ([
        ['admin.bookings.show', [$data['booking']]],
        ['admin.payments.show', [$data['payment']]],
        ['admin.refunds.show', [$data['refund']]],
        ['admin.tickets.show', [$data['ticket']]],
        ['admin.users.show', [$data['customer']]],
        ['admin.stripe-events.show', [StripeEvent::query()->create(['stripe_event_id' => 'evt_forbidden', 'type' => 'checkout.session.completed'])]],
        ['admin.audit-logs.show', [AuditLog::query()->create(['action' => 'forbidden', 'auditable_type' => 'Booking', 'auditable_id' => $data['booking']->id, 'created_at' => now()])]],
    ] as [$route, $parameters]) {
        $this->get(route($route, $parameters))->assertForbidden();
    }
    $this->post(route('admin.bookings.resend-email', $data['booking']))->assertForbidden();
    $this->patch(route('admin.users.active', $other), ['active' => false])->assertForbidden();
})->with([
    'customer' => [Role::Customer],
    'staff' => [Role::Staff],
]);

test('settings validation and staff create deactivate and reactivate are audited', function () {
    $admin = User::factory()->create(['role' => Role::Admin, 'password' => 'secret-password']);
    $this->actingAs($admin);
    $this->patch(route('admin.settings.update'), ['values' => ['timezone' => 'not/a-timezone']])
        ->assertSessionHasErrors('values.timezone');
    $this->patch(route('admin.settings.update'), ['values' => [
        'cinema_name' => 'Updated Cinema',
        'support_email' => 'ops@example.test',
        'timezone' => 'America/Los_Angeles',
    ]])->assertRedirect();
    expect(Setting::query()->whereKey('cinema_name')->value('value'))->toBe('Updated Cinema')
        ->and(Setting::query()->whereKey('timezone')->value('value'))->toBe('America/Los_Angeles');

    $this->post(route('admin.users.staff.store'), [])->assertSessionHasErrors(['name', 'email', 'password']);
    $this->post(route('admin.users.staff.store'), [
        'name' => 'New Staff',
        'email' => 'new.staff@example.test',
        'password' => 'Temporary-123',
    ])->assertRedirect();
    $staff = User::query()->where('email', 'new.staff@example.test')->firstOrFail();
    $this->patch(route('admin.users.active', $staff), ['active' => false])->assertRedirect();
    expect(app(UserActivity::class)->isActive($staff))->toBeFalse();
    auth()->logout();
    $this->post(route('login'), ['email' => $staff->email, 'password' => 'Temporary-123'])->assertSessionHasErrors('email');
    $this->actingAs($admin)->patch(route('admin.users.active', $staff), ['active' => true])->assertRedirect();
    expect(app(UserActivity::class)->isActive($staff))->toBeTrue()
        ->and(AuditLog::query()->whereIn('action', ['setting.updated', 'staff.created', 'user.deactivated', 'user.activated'])->count())->toBeGreaterThanOrEqual(5);
});

test('ticket void requires a reason and records it in the audit trail', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $data = makeAdminOperationsFixture($admin);
    $this->actingAs($admin);
    $this->post(route('admin.tickets.void', $data['ticket']), [])->assertSessionHasErrors('reason');
    $this->post(route('admin.tickets.void', $data['ticket']), ['reason' => 'Customer requested cancellation'])->assertRedirect();

    $log = AuditLog::query()->where('action', 'ticket.voided')->firstOrFail();
    expect($data['ticket']->fresh()->status)->toBe(TicketStatus::Voided)
        ->and($log->after['reason'])->toBe('Customer requested cancellation');
});

test('refund gateway failures are visible and leave an audited failed refund', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $data = makeAdminOperationsFixture($admin);
    $this->mock(PaymentGateway::class, fn ($mock) => $mock
        ->shouldReceive('createRefund')
        ->once()
        ->andReturn(new PaymentResult(false, error: 'Card processor unavailable')));

    $this->actingAs($admin)
        ->post(route('admin.refunds.process', $data['refund']))
        ->assertRedirect()
        ->assertSessionHasErrors('refund');

    expect($data['refund']->fresh()->status)->toBe(RefundStatus::Failed)
        ->and($data['refund']->fresh()->failure_message)->toBe('Card processor unavailable')
        ->and(AuditLog::query()->where('action', 'refund.failed')->exists())->toBeTrue();
});

test('booking cancellation honors the cutoff and allows a reasoned admin override', function () {
    Mail::fake();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $data = makeAdminOperationsFixture($admin);
    $data['showtime']->forceFill(['starts_at' => now()->addHour()])->save();
    $this->actingAs($admin);

    $this->post(route('admin.bookings.cancel', $data['booking']), ['reason' => 'Customer request'])
        ->assertSessionHasErrors('booking');
    $this->post(route('admin.bookings.cancel', $data['booking']), [
        'reason' => 'Approved by admin',
        'override' => true,
    ])->assertRedirect();

    expect($data['booking']->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and(Refund::query()->where('booking_id', $data['booking']->id)->where('status', RefundStatus::Pending)->exists())->toBeTrue();
});
