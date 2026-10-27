<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Models\User;

test('staff and admins are listed without category tabs or a manage column', function () {
    $admin = User::factory()->admin()->create(['name' => 'Panel Owner']);
    $staff = User::factory()->staff()->create(['name' => 'Door Staff']);

    $this->actingAs($admin)
        ->get(route('admin.staff-admins.index'))
        ->assertOk()
        ->assertSee('Team members')
        ->assertSee('Scans today')
        ->assertSee('Panel Owner')
        ->assertSee('Door Staff')
        ->assertSee(route('admin.staff-admins.show', $staff), false)
        ->assertDontSee(route('admin.staff-admins.show', $admin), false)
        ->assertDontSee('Manage</th>', false)
        ->assertDontSee('Customers 148')
        ->assertSee('Filters');
});

test('staff and admin search and role filters use database records', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create(['name' => 'Morgan Admin']);
    User::factory()->staff()->create(['name' => 'Alex Staff', 'email' => 'alex@example.test']);
    User::factory()->staff()->create(['name' => 'Casey Staff', 'email' => 'casey@example.test']);

    $this->actingAs($admin)
        ->get(route('admin.staff-admins.index', ['search' => 'Alex', 'role' => 'staff']))
        ->assertOk()
        ->assertSee('Alex Staff')
        ->assertDontSee('Casey Staff')
        ->assertDontSee('Morgan Admin');

    $this->get(route('admin.staff-admins.index', ['role' => 'admin']))
        ->assertOk()
        ->assertSee('Morgan Admin')
        ->assertDontSee('Alex Staff')
        ->assertDontSee('Casey Staff');
});

test('staff details show the staff member scan history and admins have no detail page', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create(['name' => 'Alex Staff']);
    $customer = User::factory()->create(['name' => 'Movie Guest']);
    $showtime = Showtime::factory()->create();
    $booking = Booking::forceCreate([
        'reference' => 'STAFF-SCAN-BOOKING',
        'user_id' => $customer->id,
        'showtime_id' => $showtime->id,
        'status' => 'confirmed',
        'seats_count' => 1,
        'total_cents' => 1500,
        'currency' => 'USD',
        'hold_expires_at' => now()->addHour(),
    ]);
    $seat = Seat::factory()->create(['screen_id' => $showtime->screen_id]);
    $bookingItem = BookingItem::forceCreate([
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'seat_label' => $seat->label,
        'seat_type' => 'standard',
        'price_cents' => 1500,
    ]);
    $ticket = Ticket::forceCreate([
        'booking_item_id' => $bookingItem->id,
        'booking_id' => $booking->id,
        'showtime_id' => $showtime->id,
        'ticket_number' => 'STAFF-TICKET-01',
        'token' => str_repeat('a', 40),
        'status' => 'used',
        'used_at' => now(),
        'used_by' => $staff->id,
    ]);
    TicketScan::forceCreate([
        'ticket_id' => $ticket->id,
        'showtime_id' => $showtime->id,
        'token_hash' => hash('sha256', $ticket->token),
        'scanned_by' => $staff->id,
        'result' => 'admitted',
        'scanned_at' => now(),
    ]);
    TicketScan::forceCreate([
        'ticket_id' => null,
        'showtime_id' => null,
        'token_hash' => hash('sha256', 'invalid'),
        'scanned_by' => $staff->id,
        'result' => 'invalid',
        'message' => 'Ticket not recognised',
        'scanned_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.staff-admins.show', $staff))
        ->assertOk()
        ->assertSee('Alex Staff')
        ->assertSee('Scan summary')
        ->assertSee('Scan history')
        ->assertSee('space-y-3 self-start xl:sticky xl:top-24', false)
        ->assertSee('xl:min-w-full', false)
        ->assertSee('STAFF-TICKET-01')
        ->assertSee('Movie Guest')
        ->assertSee($showtime->movie->title)
        ->assertSee('Admitted')
        ->assertSee('Unknown ticket')
        ->assertSee('Ticket not recognised')
        ->assertSee('Back to staff & admins');

    expect($response->viewData('admittedScans'))->toBe(1)
        ->and($response->viewData('scans')->total())->toBe(2)
        ->and($response->viewData('staff')->ticket_scans_count)->toBe(2);

    $this->get(route('admin.staff-admins.show', $admin))->assertNotFound();
});

test('staff scan history filters by result and sorts by scan time', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create();
    $showtime = Showtime::factory()->create();

    $olderScan = TicketScan::forceCreate([
        'token_hash' => hash('sha256', 'older'),
        'scanned_by' => $staff->id,
        'showtime_id' => $showtime->id,
        'result' => 'invalid',
        'scanned_at' => now()->subDay(),
    ]);
    $newerScan = TicketScan::forceCreate([
        'token_hash' => hash('sha256', 'newer'),
        'scanned_by' => $staff->id,
        'showtime_id' => $showtime->id,
        'result' => 'admitted',
        'scanned_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.staff-admins.show', [
            'staff' => $staff,
            'result' => 'invalid',
            'sort' => 'oldest',
        ]))
        ->assertOk()
        ->assertSee('Oldest first')
        ->assertSee('Invalid');

    expect($response->viewData('scans')->total())->toBe(1)
        ->and($response->viewData('scans')->first()->result->value)->toBe('invalid');

    expect(
        $this->get(route('admin.staff-admins.show', $staff))
            ->assertOk()
            ->viewData('scans')
            ->first()
            ->getKey(),
    )->toBe($newerScan->getKey())
        ->and($olderScan->exists)->toBeTrue();
});
