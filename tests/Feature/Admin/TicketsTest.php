<?php

use App\Actions\GenerateScreenSeatsAction;
use App\Enums\Role;
use App\Models\Screen;
use App\Models\Showtime;
use App\Models\User;
use Database\Seeders\StaffDemoSeeder;
use Illuminate\Support\Carbon;

function ticketAdminDemoShow(): Showtime
{
    $screen = Screen::factory()->create(['name' => 'Screen 1']);
    app(GenerateScreenSeatsAction::class)->execute($screen, ['G', 'H']);

    $shows = collect([11, 15, 19])->map(fn (int $hour) => Showtime::factory()->create([
        'screen_id' => $screen->id,
        'starts_at' => sprintf('2026-10-21 %02d:00', $hour),
        'ends_at' => sprintf('2026-10-21 %02d:43', $hour + 2),
        'base_price_cents' => 1000,
    ]));

    return $shows->first();
}

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-21 12:00:00'));
    User::factory()->staff()->create();
    ticketAdminDemoShow();
    $this->seed(StaffDemoSeeder::class);
});

it('shows the ticket and scan tabs with today metrics', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.tickets.index'))
        ->assertOk()
        ->assertSee('Tickets issued today')
        ->assertSee('Scanned today')
        ->assertSee('Duplicate attempts')
        ->assertSee('Voided tickets')
        ->assertSee('Tickets')
        ->assertSee('Scan log')
        ->assertSee('Open scanner')
        ->assertSee('Issued tickets')
        ->assertSee('<link rel="icon" href="data:,">', false);
});

it('filters tickets and scan activity using their respective controls', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.tickets.index', ['tab' => 'tickets', 'search' => 'CB-261021-0468']))
        ->assertOk()
        ->assertSee('CB-261021-0468')
        ->assertDontSee('CB-261021-0478');

    $this->get(route('admin.tickets.index', ['tab' => 'scans', 'result' => 'too_early']))
        ->assertOk()
        ->assertSee('Recent scan activity')
        ->assertSee('No scan activity found');
});

it('rejects invalid ticket filters', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.tickets.index', ['status' => 'not-a-ticket-status']))
        ->assertSessionHasErrors('status');
});

it('keeps tickets admin-only', function () {
    $customer = User::factory()->create(['role' => Role::Customer]);

    $this->actingAs($customer)
        ->get(route('admin.tickets.index'))
        ->assertForbidden();
});
