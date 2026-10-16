<?php

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Movie;
use App\Models\Refund;
use App\Models\Screen;
use App\Models\SeatHold;
use App\Models\Showtime;
use App\Models\StripeEvent;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\DB;

test('demo data seeder reuses existing admin and staff accounts unchanged', function () {
    $admin = User::factory()->create([
        'name' => 'Existing Admin',
        'email' => 'admin@example.com',
        'password' => 'admin-existing-password',
        'role' => 'admin',
    ]);
    $staff = User::factory()->create([
        'name' => 'Existing Staff',
        'email' => 'staff@example.com',
        'password' => 'staff-existing-password',
        'role' => 'staff',
    ]);
    $adminDetails = $admin->only(['name', 'email', 'password', 'role']);
    $staffDetails = $staff->only(['name', 'email', 'password', 'role']);

    $this->artisan('db:seed', ['--class' => DemoDataSeeder::class])
        ->assertExitCode(0);

    expect($admin->fresh()->only(['name', 'email', 'password', 'role']))->toEqual($adminDetails)
        ->and($staff->fresh()->only(['name', 'email', 'password', 'role']))->toEqual($staffDetails)
        ->and(Movie::query()->count())->toBe(24)
        ->and(Booking::query()->count())->toBe(150)
        ->and(User::query()->where('email', 'like', 'demo.customer%@example.com')->count())->toBe(22)
        ->and(User::query()->where('email', 'like', 'demo.staff%@example.com')->count())->toBe(3);

    $this->artisan('db:seed', ['--class' => DemoDataSeeder::class])
        ->expectsOutput('Demo data already seeded')
        ->assertExitCode(0);

    expect(User::query()->where('email', 'admin@example.com')->count())->toBe(1)
        ->and(User::query()->where('email', 'staff@example.com')->count())->toBe(1)
        ->and(Movie::query()->count())->toBe(24)
        ->and(Booking::query()->count())->toBe(150);
});

test('demo data seeder creates the fixtures once and skips subsequent runs', function () {
    $this->seed(DemoDataSeeder::class);

    expect(Movie::query()->count())->toBe(24)
        ->and(Screen::query()->count())->toBe(4)
        ->and(Showtime::query()->count())->toBe(70)
        ->and(Booking::query()->count())->toBe(150)
        ->and(Refund::query()->count())->toBeGreaterThanOrEqual(10)
        ->and(StripeEvent::query()->count())->toBe(40)
        ->and(AuditLog::query()->count())->toBe(80)
        ->and(SeatHold::query()->where('status', 'expired')->exists())->toBeTrue()
        ->and(TicketScan::query()->where('result', 'duplicate')->exists())->toBeTrue()
        ->and(TicketScan::query()->where('result', 'invalid')->exists())->toBeTrue();

    $itemsTotalsMatch = DB::table('bookings')
        ->joinSub(
            DB::table('booking_items')->selectRaw('booking_id, SUM(price_cents) as items_total')->groupBy('booking_id'),
            'items',
            'items.booking_id',
            '=',
            'bookings.id',
        )
        ->whereColumn('bookings.total_cents', '<>', 'items.items_total')
        ->doesntExist();
    $paymentTotalsMatch = DB::table('payments')
        ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
        ->whereColumn('payments.amount_cents', '<>', 'bookings.total_cents')
        ->doesntExist();
    $activeTicketBookingsAreConfirmed = Ticket::query()
        ->join('bookings', 'bookings.id', '=', 'tickets.booking_id')
        ->where('tickets.status', '!=', 'voided')
        ->where('bookings.status', '!=', 'confirmed')
        ->doesntExist();
    $refundsWithinPaidAmount = DB::table('refunds')
        ->join('payments', 'payments.id', '=', 'refunds.payment_id')
        ->whereColumn('refunds.amount_cents', '>', 'payments.amount_cents')
        ->doesntExist();
    $noShowtimeOverlap = DB::table('showtimes as first_show')
        ->join('showtimes as second_show', function ($join) {
            $join->on('first_show.screen_id', '=', 'second_show.screen_id')
                ->whereColumn('first_show.id', '<', 'second_show.id')
                ->whereColumn('first_show.starts_at', '<', 'second_show.ends_at')
                ->whereColumn('second_show.starts_at', '<', 'first_show.ends_at');
        })
        ->exists() === false;

    expect($itemsTotalsMatch)->toBeTrue()
        ->and($paymentTotalsMatch)->toBeTrue()
        ->and($activeTicketBookingsAreConfirmed)->toBeTrue()
        ->and($refundsWithinPaidAmount)->toBeTrue()
        ->and($noShowtimeOverlap)->toBeTrue();

    $movieCount = Movie::query()->count();
    $bookingCount = Booking::query()->count();

    $this->artisan('db:seed', ['--class' => DemoDataSeeder::class])
        ->expectsOutput('Demo data already seeded')
        ->assertExitCode(0);

    expect(Movie::query()->count())->toBe($movieCount)
        ->and(Booking::query()->count())->toBe($bookingCount);
});
