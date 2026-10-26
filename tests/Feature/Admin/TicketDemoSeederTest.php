<?php

use App\Actions\GenerateScreenSeatsAction;
use App\Enums\Role;
use App\Models\Screen;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Models\User;
use Database\Seeders\TicketDemoSeeder;

beforeEach(function () {
    User::factory()->staff()->create();
    $screen = Screen::factory()->create(['name' => 'Screen 1']);
    app(GenerateScreenSeatsAction::class)->execute($screen, ['A', 'B', 'C', 'D']);
    Showtime::factory()->create(['screen_id' => $screen->id]);
});

it('seeds a repeatable set of tickets and scan attempts', function () {
    $this->seed(TicketDemoSeeder::class);

    expect(Ticket::query()->where('ticket_number', 'like', 'DEMO-TICKET-1%')->count())->toBe(20)
        ->and(TicketScan::query()->count())->toBe(21)
        ->and(TicketScan::query()->where('result', 'admitted')->count())->toBe(6)
        ->and(TicketScan::query()->where('result', 'already_used')->count())->toBe(6)
        ->and(TicketScan::query()->where('result', 'void')->count())->toBe(4)
        ->and(TicketScan::query()->where('result', 'invalid')->count())->toBe(5);

    $counts = [Ticket::query()->count(), TicketScan::query()->count(), User::query()->count()];
    $this->seed(TicketDemoSeeder::class);

    expect([Ticket::query()->count(), TicketScan::query()->count(), User::query()->count()])->toBe($counts);
});

it('links ticket numbers to their booking details and removes the separate open action', function () {
    $this->seed(TicketDemoSeeder::class);
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.tickets.index'))
        ->assertOk()
        ->assertSee('href="'.route('admin.bookings.show', 'DEMO-TIX-0001').'"', false)
        ->assertSee('DEMO-TICKET-101')
        ->assertDontSee('>Open</a>', false);

    $this->get(route('admin.tickets.index', ['tab' => 'scans']))
        ->assertOk()
        ->assertSee('Recent scan activity')
        ->assertSee('Invalid');
});
