<?php

use App\Enums\Role;
use App\Enums\SeatType;
use App\Models\AuditLog;
use App\Models\BookingItem;
use App\Models\Cinema;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\User;

test('an administrator can manage cinema details and cannot remove a cinema that has screens', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.cinema.index'))
        ->assertOk()
        ->assertDontSee('ValueError')
        ->assertDontSee('accessible" is not a valid backing value')
        ->assertSee('Cinema & Screens')
        ->assertSee('Your cinemas')
        ->assertSee('Add a cinema')
        ->assertDontSee('>Back</', false);

    $this->post(route('admin.cinema.store'), [
        'name' => '  Downtown Cinema  ',
        'address' => '12 Main Street',
        'phone' => '555-0100',
    ])->assertRedirect(route('admin.cinema.index'))
        ->assertSessionHas('status');

    $cinema = Cinema::query()->where('name', 'Downtown Cinema')->firstOrFail();
    expect($cinema->address)->toBe('12 Main Street');

    $this->put(route('admin.cinema.update', $cinema), [
        'name' => 'Uptown Cinema',
        'address' => '14 Main Street',
        'phone' => '555-0120',
    ])->assertRedirect(route('admin.cinema.index'))
        ->assertSessionHas('status');
    expect($cinema->fresh()->name)->toBe('Uptown Cinema');

    $this->get(route('admin.cinema.index', ['delete' => $cinema->id]))
        ->assertOk()
        ->assertSee('Delete confirmation')
        ->assertSee('Confirm deletion');

    $this->delete(route('admin.cinema.destroy', $cinema))
        ->assertRedirect(route('admin.cinema.index'))
        ->assertSessionHas('status');
    $this->assertDatabaseMissing('cinemas', ['id' => $cinema->id]);
    expect(AuditLog::query()->whereIn('action', ['cinema.created', 'cinema.updated', 'cinema.deleted'])->count())->toBe(3);
});

test('cinemas with screens are protected from deletion', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $screen = Screen::factory()->create(['name' => 'Auditorium One']);

    $this->actingAs($admin)
        ->get(route('admin.cinema.index', ['delete' => $screen->cinema_id]))
        ->assertOk()
        ->assertSee('Remove its screens before deleting the location');

    $this->delete(route('admin.cinema.destroy', $screen->cinema))
        ->assertRedirect(route('admin.cinema.index', ['delete' => $screen->cinema_id]))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('cinemas', ['id' => $screen->cinema_id]);
});

test('cinema and screen validation errors are shown beside the matching fields', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $cinema = Cinema::factory()->create();

    $this->actingAs($admin)
        ->from(route('admin.cinema.index'))
        ->post(route('admin.cinema.store'), [
            'name' => str_repeat('C', 121),
        ]);

    $this->get(route('admin.cinema.index'))
        ->assertOk()
        ->assertSee('The cinema name may not be longer than 120 characters.')
        ->assertSee('aria-describedby="cinema-name-error"', false)
        ->assertSee('novalidate', false);

    $this->from(route('admin.screens.create'))
        ->post(route('admin.screens.store'), [
            'cinema_id' => $cinema->id,
            'name' => 'Invalid Screen',
            'rows_count' => 27,
            'seats_per_row' => 0,
        ]);

    $this->get(route('admin.screens.create'))
        ->assertOk()
        ->assertSee('A screen may have at most 26 rows (A–Z).')
        ->assertSee('Each row must have at least one seat.')
        ->assertSee('aria-describedby="screen-rows-error"', false)
        ->assertSee('novalidate', false);

    $screen = Screen::factory()->create(['rows_count' => 1, 'seats_per_row' => 1]);
    $seat = Seat::factory()->create([
        'screen_id' => $screen->id,
        'label' => 'A1',
        'row_label' => 'A',
        'seat_number' => 1,
    ]);

    $this->post(route('admin.screens.seats.generate', $screen), [
        'premium_rows' => 'A',
    ]);

    $this->get(route('admin.screens.show', $screen))
        ->assertOk()
        ->assertSee('Choose premium rows from the list.')
        ->assertSee('aria-describedby="premium-rows-error"', false)
        ->assertSee('novalidate', false);

    $this->put(route('admin.screens.seats.update', [$screen, $seat]), [
        'type' => 'invalid',
        'is_disabled' => '0',
    ]);

    $this->get(route('admin.screens.show', $screen))
        ->assertOk()
        ->assertSee('Choose a valid seat type.')
        ->assertSee('aria-describedby="seat-type-error"', false)
        ->assertSee('open: true');
});

test('an administrator can create a screen, generate seats, and edit seat settings', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $cinema = Cinema::factory()->create(['name' => 'Riverside Cinema']);

    $this->actingAs($admin)
        ->get(route('admin.screens.create'))
        ->assertOk()
        ->assertSee('Add a screen')
        ->assertSee('Riverside Cinema')
        ->assertSee('Cancel')
        ->assertDontSee('>Back</', false);

    $this->post(route('admin.screens.store'), [
        'cinema_id' => $cinema->id,
        'name' => 'Screen 1',
        'rows_count' => 3,
        'seats_per_row' => 4,
        'is_active' => '1',
    ])->assertRedirect();

    $screen = Screen::query()->where('cinema_id', $cinema->id)->where('name', 'Screen 1')->firstOrFail();

    $this->post(route('admin.screens.seats.generate', $screen), [
        'premium_rows' => ['B'],
    ])->assertRedirect(route('admin.screens.show', $screen))
        ->assertSessionHas('status');

    expect($screen->seats()->count())->toBe(12)
        ->and($screen->seats()->where('type', SeatType::Premium->value)->count())->toBe(4);

    $seat = $screen->seats()->where('label', 'B2')->firstOrFail();
    $this->put(route('admin.screens.seats.update', [$screen, $seat]), [
        'type' => SeatType::Accessible->value,
        'is_disabled' => '0',
    ])->assertRedirect(route('admin.screens.show', $screen))
        ->assertSessionHas('status');

    expect($seat->fresh()->type)->toBe(SeatType::Accessible)
        ->and((new BookingItem(['seat_type' => SeatType::Accessible->value]))->seat_type)->toBe(SeatType::Accessible);

    $this->get(route('admin.screens.show', $screen))
        ->assertOk()
        ->assertSee('Seat layout')
        ->assertSee('Riverside Cinema')
        ->assertSee('B2 · Accessible')
        ->assertSee('Back to screens');

    $this->get(route('admin.screens.index'))
        ->assertOk()
        ->assertSee('Screen 1')
        ->assertSee('Riverside Cinema')
        ->assertSee('Manage seats')
        ->assertSee('Back to cinemas')
        ->assertDontSee('>Back</', false);

    $this->put(route('admin.screens.update', $screen), [
        'cinema_id' => $cinema->id,
        'name' => 'Screen One',
        'rows_count' => 3,
        'seats_per_row' => 4,
        'is_active' => '1',
    ])->assertRedirect(route('admin.screens.index'))
        ->assertSessionHas('status');

    expect($screen->fresh()->name)->toBe('Screen One');

    $previousSeatIds = $screen->seats()->pluck('id')->all();
    $this->post(route('admin.screens.seats.generate', $screen), [
        'premium_rows' => ['C'],
    ])->assertRedirect(route('admin.screens.show', $screen));
    expect(Seat::query()->whereIn('id', $previousSeatIds)->exists())->toBeFalse();
    $gridAudit = AuditLog::query()->where('action', 'seat.grid_regenerated')->latest('id')->firstOrFail();
    expect(collect($gridAudit->before['seats'])->pluck('id')->all())->toBe($previousSeatIds)
        ->and(collect($gridAudit->after['seats'])->pluck('id')->intersect($previousSeatIds))->toBeEmpty();

    $this->delete(route('admin.screens.destroy', $screen))
        ->assertRedirect(route('admin.screens.index'))
        ->assertSessionHas('status');
    $this->assertDatabaseMissing('screens', ['id' => $screen->id]);

    expect(AuditLog::query()->whereIn('action', [
        'screen.created',
        'screen.updated',
        'seat.grid_regenerated',
        'seat.updated',
        'screen.deleted',
    ])->count())->toBe(6)
        ->and(AuditLog::query()->where('action', 'seat.grid_regenerated')->count())->toBe(2);
});

test('screens with showtimes cannot be deleted or have seats regenerated', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $screen = Screen::factory()->create(['rows_count' => 2, 'seats_per_row' => 2]);
    $seat = Seat::factory()->create(['screen_id' => $screen->id, 'label' => 'A1', 'row_label' => 'A', 'seat_number' => 1]);
    Showtime::factory()->create(['screen_id' => $screen->id]);

    $this->actingAs($admin)
        ->post(route('admin.screens.seats.generate', $screen), [])
        ->assertRedirect(route('admin.screens.show', $screen))
        ->assertSessionHas('error');

    $this->delete(route('admin.screens.destroy', $screen))
        ->assertRedirect(route('admin.screens.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('screens', ['id' => $screen->id]);
    $this->assertDatabaseHas('seats', ['id' => $seat->id]);
});
