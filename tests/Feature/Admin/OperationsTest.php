<?php

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Seat;
use App\Models\Setting;
use App\Models\Showtime;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

test('an administrator can update reservation settings and the change is audited', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'hold_minutes' => 15,
            'max_seats_per_booking' => 8,
            'cancel_window_hours' => 4,
            'premium_surcharge_cents' => '3.50',
        ])
        ->assertRedirect(route('admin.settings.edit'));

    expect(Setting::query()->whereKey('hold_minutes')->value('value'))->toBe('15')
        ->and(Setting::query()->whereKey('max_seats_per_booking')->value('value'))->toBe('8')
        ->and(Setting::query()->whereKey('premium_surcharge_cents')->value('value'))->toBe('350');

    expect(AuditLog::query()->where('action', 'settings.updated')->count())->toBe(4);
});

test('reservation settings use database values before config defaults', function () {
    config(['cinebook.hold_minutes' => 19]);
    $settings = app(SettingsService::class);

    expect($settings->get('hold_minutes'))->toBe(19);

    Setting::query()->create(['key' => 'hold_minutes', 'value' => '7']);

    expect($settings->get('hold_minutes'))->toBe('7');

    $this->actingAs(User::factory()->create(['role' => Role::Admin]))
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertViewHas('settings', fn (array $values): bool => $values['hold_minutes'] === '7');
});

test('an administrator can filter audit history and open its detail drawer', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $other = User::factory()->create(['name' => 'Other Admin']);

    AuditLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'settings.updated',
        'auditable_type' => Setting::class,
        'auditable_id' => 0,
        'before' => ['key' => 'hold_minutes', 'value' => '2'],
        'after' => ['key' => 'hold_minutes', 'value' => '3'],
        'ip_address' => '192.0.2.10',
    ]);
    AuditLog::query()->create([
        'user_id' => $other->id,
        'action' => 'movie.updated',
        'auditable_type' => Setting::class,
        'auditable_id' => 0,
    ]);

    $log = AuditLog::query()->where('action', 'settings.updated')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.audit-logs.index', [
            'actor' => $admin->name,
            'action' => 'settings.updated',
            'target' => 'hold_minutes',
            'log' => $log->id,
        ]))
        ->assertOk()
        ->assertSee('settings.updated')
        ->assertSee('Before / after')
        ->assertSee('192.0.2.10')
        ->assertDontSee('movie.updated');
});

test('an administrator can inspect, retry and remove a failed job', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $job = [
        'uuid' => (string) str()->uuid(),
        'connection' => 'database',
        'queue' => 'notifications',
        'exception' => "MailTransportTimeout\nApp\\Jobs\\SendBookingConfirmation: Delivery timed out after 30 seconds.",
        'failed_at' => now(),
    ];

    if (Schema::hasColumn('failed_jobs', 'payload')) {
        $queueJobId = Queue::connection('database')->push(
            CallQueuedClosure::create(static fn (): null => null)->name('SendBookingConfirmation'),
            '',
            'notifications',
        );
        $job['payload'] = DB::table('jobs')->where('id', $queueJobId)->value('payload');
        DB::table('jobs')->where('id', $queueJobId)->delete();
    }

    $jobId = DB::table('failed_jobs')->insertGetId($job);

    $detail = $this->actingAs($admin)
        ->get(route('admin.failed-jobs.show', $jobId))
        ->assertOk()
        ->assertSee('SendBookingConfirmation')
        ->assertSee('Confirm delete');

    if (Schema::hasColumn('failed_jobs', 'payload')) {
        $detail->assertSee('Confirm retry');
        $this->post(route('admin.failed-jobs.retry', $jobId))
            ->assertRedirect(route('admin.failed-jobs.index'));
        expect(DB::table('failed_jobs')->where('id', $jobId)->exists())->toBeFalse();
        expect(AuditLog::query()->where('action', 'failed_job.retried')->where('auditable_id', $jobId)->exists())->toBeTrue();
    } else {
        $detail->assertSee('Retry is unavailable');
        $this->post(route('admin.failed-jobs.retry', $jobId))
            ->assertStatus(409);
        expect(DB::table('failed_jobs')->where('id', $jobId)->exists())->toBeTrue();
        $this->delete(route('admin.failed-jobs.destroy', $jobId))
            ->assertRedirect(route('admin.failed-jobs.index'));
        expect(DB::table('failed_jobs')->where('id', $jobId)->exists())->toBeFalse();
        expect(AuditLog::query()->where('action', 'failed_job.deleted')->where('auditable_id', $jobId)->exists())->toBeTrue();
    }

    $deleteJob = $job;
    $deleteJob['uuid'] = (string) str()->uuid();
    $deleteJob['failed_at'] = now();
    $deleteJobId = DB::table('failed_jobs')->insertGetId($deleteJob);
    $this->delete(route('admin.failed-jobs.destroy', $deleteJobId))
        ->assertRedirect(route('admin.failed-jobs.index'));
    expect(DB::table('failed_jobs')->where('id', $deleteJobId)->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', 'failed_job.deleted')->where('auditable_id', $deleteJobId)->exists())->toBeTrue();
});

test('sales reports attribute successful refunds to the selected movie and date', function () {
    $this->seed(DatabaseSeeder::class);
    $admin = User::factory()->create(['role' => Role::Admin]);
    $booking = Booking::query()->where('reference', 'DEMO-PAY-001')->firstOrFail();
    $movieId = $booking->showtime->movie_id;
    $otherMovieId = Movie::query()->whereKeyNot($movieId)->value('id');

    $this->actingAs($admin)
        ->get(route('admin.reports.index', [
            'from' => now()->subDays(30)->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('Sales reports')
        ->assertSee('$90')
        ->assertSee('$20')
        ->assertSee('$70')
        ->assertSee('$20.00');

    $this->get(route('admin.reports.index', [
        'from' => now()->subDays(30)->toDateString(),
        'to' => now()->toDateString(),
        'movie_id' => $otherMovieId,
    ]))
        ->assertOk()
        ->assertSee('No sales in this range')
        ->assertSee('No movie sales');
});

test('sales reports include fully refunded and cancelled bookings in gross totals', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $customer = User::factory()->create();
    $showtime = Showtime::factory()->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHours(2),
    ]);
    $confirmedAt = now()->subDay();

    foreach ([
        ['reference' => 'CB-FULL-REFUND-001', 'status' => 'refunded', 'item_status' => 'active'],
        ['reference' => 'CB-FULL-REFUND-002', 'status' => 'cancelled', 'item_status' => 'cancelled'],
    ] as $case) {
        $seat = Seat::factory()->for($showtime->screen)->create();
        $booking = Booking::forceCreate([
            'reference' => $case['reference'],
            'user_id' => $customer->id,
            'showtime_id' => $showtime->id,
            'status' => $case['status'],
            'seats_count' => 1,
            'total_cents' => 2000,
            'currency' => 'USD',
            'hold_expires_at' => $confirmedAt,
            'confirmed_at' => $confirmedAt,
            'cancelled_at' => $case['status'] === 'cancelled' ? now()->subHours(12) : null,
        ]);
        BookingItem::forceCreate([
            'booking_id' => $booking->id,
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
            'seat_label' => $seat->label,
            'seat_type' => $seat->type->value,
            'price_cents' => 2000,
            'status' => $case['item_status'],
        ]);
        $payment = Payment::forceCreate([
            'booking_id' => $booking->id,
            'gateway' => 'stripe',
            'idempotency_key' => Str::random(64),
            'amount_cents' => 2000,
            'currency' => 'USD',
            'status' => 'refunded',
            'paid_at' => $confirmedAt,
        ]);
        Refund::forceCreate([
            'payment_id' => $payment->id,
            'booking_id' => $booking->id,
            'amount_cents' => 2000,
            'currency' => 'USD',
            'status' => 'succeeded',
            'reason' => 'requested_by_customer',
            'idempotency_key' => Str::random(64),
            'processed_at' => now()->subHours(12),
        ]);
    }

    $this->actingAs($admin)
        ->get(route('admin.reports.index', [
            'from' => now()->subDays(2)->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertViewHas('grossCents', 4000)
        ->assertViewHas('refundsCents', 4000)
        ->assertViewHas('tickets', 2)
        ->assertSee('$40.00')
        ->assertSee('$0.00');
});

test('sales report export includes the daily refund values', function () {
    $this->seed(DatabaseSeeder::class);
    $admin = User::factory()->create(['role' => Role::Admin]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', [
            'from' => now()->subDays(30)->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertDownload('sales-report.csv');

    $csv = $response->streamedContent();
    $rows = collect(explode("\n", trim($csv)))
        ->skip(1)
        ->filter()
        ->map(fn (string $row): array => str_getcsv($row))
        ->all();
    $refundsCents = array_sum(array_map(fn (array $row): int => (int) round((float) $row[3] * 100), $rows));

    expect($csv)->toContain('Refunds USD')
        ->and($refundsCents)->toBe(2000)
        ->and(AuditLog::query()->where('action', 'report.csv_export_requested')->where('auditable_type', 'ReportExport')->exists())->toBeTrue();

    $this->get(route('admin.audit-logs.index', ['action' => 'report.csv_export_requested']))
        ->assertOk()
        ->assertSee('ReportExport')
        ->assertSee('report.csv_export_requested');
});
