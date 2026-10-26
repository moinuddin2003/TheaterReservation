<?php

use App\Enums\Role;
use App\Models\Refund;
use App\Models\User;
use Database\Seeders\CinemaSeeder;
use Database\Seeders\GenreSeeder;
use Database\Seeders\MovieSeeder;
use Database\Seeders\PaymentSeeder;
use Database\Seeders\ShowtimeSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-26 11:00:00'));
    $this->seed([
        GenreSeeder::class,
        CinemaSeeder::class,
        MovieSeeder::class,
        ShowtimeSeeder::class,
        PaymentSeeder::class,
    ]);
});

it('shows refund summaries and the recent refund list', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.refunds.index'))
        ->assertOk()
        ->assertSee('Total refunds')
        ->assertSee('Requests in selected date range')
        ->assertSee('Refunded amount')
        ->assertSee('Successfully returned to customers')
        ->assertSee('$20.00')
        ->assertSee('Pending refunds')
        ->assertSee('Awaiting provider confirmation')
        ->assertSee('Failed refunds')
        ->assertSee('Need attention')
        ->assertSee('RF-00001')
        ->assertSee('Maya Chen')
        ->assertSee('Customer request')
        ->assertSee('Temporary connection error')
        ->assertSee('Choose date range')
        ->assertSee('aria-label="Search refunds"', false)
        ->assertDontSee('vs. previous period')
        ->assertDontSee('Refund confirmation')
        ->assertDontSee('Refund lifecycle')
        ->assertDontSee('>Action<', false)
        ->assertDontSee('>View', false);
});

it('searches refunds by customer and booking reference within the date range', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.refunds.index', ['search' => 'Maya Chen']))
        ->assertOk()
        ->assertSee('DEMO-PAY-003')
        ->assertDontSee('DEMO-PAY-001');

    $this->get(route('admin.refunds.index', [
        'search' => 'DEMO-PAY-001',
        'from' => '2026-10-24',
        'to' => '2026-10-25',
    ]))
        ->assertOk()
        ->assertSee('DEMO-PAY-001')
        ->assertDontSee('DEMO-PAY-005');
});

it('exports refunds as csv and includes the requested date range', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $response = $this->actingAs($admin)
        ->get(route('admin.refunds.export', [
            'from' => '2026-10-01',
            'to' => '2026-10-26',
        ]))
        ->assertOk()
        ->assertDownload('refunds.csv');

    expect($response->streamedContent())
        ->toContain('Refund ID')
        ->toContain('DEMO-PAY-003')
        ->toContain('failed');
});

it('creates the same five demo refunds when the payment seeder runs again', function () {
    $this->seed(PaymentSeeder::class);

    expect(Refund::query()->where('idempotency_key', 'like', 'demo-refund-%')->count())->toBe(5);
});
