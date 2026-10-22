<?php

use App\Actions\GenerateScreenSeatsAction;
use App\Models\Booking;
use App\Models\Screen;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Models\User;
use Database\Seeders\StaffDemoSeeder;

function demoShowWithSeats(): Showtime
{
    $screen = Screen::factory()->create(['name' => 'Screen 1']);
    app(GenerateScreenSeatsAction::class)->execute($screen, ['G', 'H']);

    return Showtime::factory()->create([
        'screen_id' => $screen->id,
        'starts_at' => '2026-10-21 11:00',
        'ends_at' => '2026-10-21 13:43',
        'base_price_cents' => 1000,
    ]);
}

test('the seeder builds the roster story: 21 bookings, 45 seats, 19 admitted, one cancelled booking', function () {
    User::factory()->staff()->create();
    $show = demoShowWithSeats();

    $this->seed(StaffDemoSeeder::class);

    $confirmed = Booking::query()->confirmed()->where('showtime_id', $show->id);
    $tickets = Ticket::query()->where('showtime_id', $show->id);

    expect($confirmed->count())->toBe(21)
        ->and((clone $tickets)->whereIn('status', ['valid', 'used'])->count())->toBe(45)
        ->and((clone $tickets)->where('status', 'used')->count())->toBe(19)
        ->and((clone $tickets)->where('status', 'void')->count())->toBe(2)
        ->and(Booking::query()->where('status', 'cancelled')->count())->toBe(1)
        ->and(Booking::query()->where('reference', 'CB-261021-0482')->first()->user->name)->toBe('Alex Morgan');
});

test('the seeder records the admissions and a few rejected attempts as scan history', function () {
    User::factory()->staff()->create();
    demoShowWithSeats();

    $this->seed(StaffDemoSeeder::class);

    expect(TicketScan::query()->where('result', 'admitted')->count())->toBe(19)
        ->and(TicketScan::query()->where('result', 'already_used')->count())->toBe(1)
        ->and(TicketScan::query()->where('result', 'invalid')->count())->toBe(1);
});

test('running the seeder again adds nothing', function () {
    User::factory()->staff()->create();
    demoShowWithSeats();

    $this->seed(StaffDemoSeeder::class);
    $counts = [Booking::count(), Ticket::count(), TicketScan::count(), User::count()];

    $this->seed(StaffDemoSeeder::class);

    expect([Booking::count(), Ticket::count(), TicketScan::count(), User::count()])->toBe($counts);
});

test('without the demo day shows or a staff user the seeder leaves the database alone', function () {
    User::factory()->staff()->create();

    $this->seed(StaffDemoSeeder::class);

    expect(Booking::count())->toBe(0)->and(User::count())->toBe(1);
});

test('demo tickets get short tokens, also when they were seeded earlier with long ones', function () {
    User::factory()->staff()->create();
    demoShowWithSeats();

    $this->seed(StaffDemoSeeder::class);

    expect(Ticket::query()->where('token', 'DEMO-0482-01')->exists())->toBeTrue();

    $ticket = Ticket::query()->where('ticket_number', 'T-0482-02')->first();
    $ticket->forceFill(['token' => str_repeat('a', 40)])->save();

    $this->seed(StaffDemoSeeder::class);

    expect($ticket->refresh()->token)->toBe('DEMO-0482-02');
});
