<?php

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Movie;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

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
    } else {
        $detail->assertSee('Retry is unavailable');
        $this->post(route('admin.failed-jobs.retry', $jobId))
            ->assertStatus(409);
        expect(DB::table('failed_jobs')->where('id', $jobId)->exists())->toBeTrue();
        $this->delete(route('admin.failed-jobs.destroy', $jobId))
            ->assertRedirect(route('admin.failed-jobs.index'));
        expect(DB::table('failed_jobs')->where('id', $jobId)->exists())->toBeFalse();
    }
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
        ->and($refundsCents)->toBe(2000);
});
