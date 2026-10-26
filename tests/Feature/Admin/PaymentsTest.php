<?php

use App\Enums\Role;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\CinemaSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\GenreSeeder;
use Database\Seeders\MovieSeeder;
use Database\Seeders\PaymentSeeder;
use Database\Seeders\ShowtimeSeeder;

beforeEach(function () {
    $this->seed([
        GenreSeeder::class,
        CinemaSeeder::class,
        MovieSeeder::class,
        ShowtimeSeeder::class,
        PaymentSeeder::class,
    ]);
});

it('shows seeded payments and filters by customer and payment status', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertSee('Payments')
        ->assertSee('Payment filters')
        ->assertSee('Real-time payment data')
        ->assertSee('Payment state')
        ->assertSee('Recent payments')
        ->assertSee('aria-label="Search payments"', false)
        ->assertSee('PAY-00001')
        ->assertSee('href="'.route('admin.bookings.show', 'DEMO-PAY-001').'"', false)
        ->assertSee('Alex Morgan')
        ->assertSee('Movie / showtime')
        ->assertSee('Gateway')
        ->assertSee(Payment::query()->where('idempotency_key', 'demo-payment-001')->firstOrFail()->booking->showtime->movie->title)
        ->assertSee('Demo')
        ->assertSee('Processing')
        ->assertDontSee('Processing · stuck')
        ->assertDontSee('>Detail<', false);

    $this->actingAs($admin)
        ->get(route('admin.payments.index', [
            'search' => 'maya.chen.demo',
            'status' => 'succeeded',
        ]))
        ->assertOk()
        ->assertSee('Maya Chen')
        ->assertSee('DEMO-PAY-003')
        ->assertDontSee('DEMO-PAY-001');

    $this->actingAs($admin)
        ->get(route('admin.payments.index'))
        ->assertSee('$24.00')
        ->assertDontSee('USD 24.00');
});

it('exports the filtered payment list as csv', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $response = $this->actingAs($admin)
        ->get(route('admin.payments.export', ['status' => 'failed']))
        ->assertOk()
        ->assertDownload('payments.csv');

    expect($response->streamedContent())
        ->toContain('Payment ID')
        ->toContain('DEMO-PAY-004')
        ->not->toContain('DEMO-PAY-001');
});

it('seeds repeatable payment records with matching booking and ticket data', function () {
    $this->seed(PaymentSeeder::class);

    expect(Payment::query()->count())->toBe(8)
        ->and(Booking::query()->where('reference', 'like', 'DEMO-PAY-%')->count())->toBe(8)
        ->and(BookingItem::query()->count())->toBe(4)
        ->and(Ticket::query()->count())->toBe(4);
});

it('includes demo payments in the normal database seed command', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Payment::query()->where('gateway', 'demo')->count())->toBe(8)
        ->and(Booking::query()->where('reference', 'like', 'DEMO-PAY-%')->count())->toBe(8)
        ->and(Refund::query()->where('idempotency_key', 'like', 'demo-refund-%')->count())->toBe(5);
});
