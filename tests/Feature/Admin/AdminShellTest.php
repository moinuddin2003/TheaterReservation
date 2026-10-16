<?php

use App\Enums\Role;
use App\Models\User;

dataset('admin panel routes', [
    ['admin.dashboard', 'Dashboard'],
    ['admin.movies.index', 'Movies'],
    ['admin.genres.index', 'Genres'],
    ['admin.cinema.index', 'Cinema & Screens'],
    ['admin.showtimes.index', 'Showtimes'],
    ['admin.bookings.index', 'Bookings'],
    ['admin.payments.index', 'Payments'],
    ['admin.refunds.index', 'Refunds'],
    ['admin.tickets.index', 'Tickets & Scans'],
    ['admin.users.index', 'Users'],
    ['admin.settings.edit', 'Settings'],
    ['admin.stripe-events.index', 'Stripe Events'],
    ['admin.audit-logs.index', 'Audit Logs'],
]);

test('an administrator can open every admin placeholder page', function (string $route, string $title) {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route($route))
        ->assertOk()
        ->assertSee($title)
        ->assertSee('CineBook');
})->with('admin panel routes');

test('customers and staff are forbidden from the admin panel', function (Role $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user);
    foreach ([
        'admin.dashboard',
        'admin.movies.index',
        'admin.genres.index',
        'admin.cinema.index',
        'admin.showtimes.index',
        'admin.bookings.index',
        'admin.payments.index',
        'admin.refunds.index',
        'admin.tickets.index',
        'admin.users.index',
        'admin.settings.edit',
        'admin.stripe-events.index',
        'admin.audit-logs.index',
    ] as $route) {
        $this->get(route($route))->assertForbidden();
    }
})->with([
    'customer' => [Role::Customer],
    'staff' => [Role::Staff],
]);

test('customers and staff cannot submit administrative mutations', function (Role $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->patch(route('admin.settings.update'), ['values' => ['cinema_name' => 'Unauthorized']])
        ->assertForbidden();

    $this->post(route('admin.users.staff.store'), [])
        ->assertForbidden();
})->with([
    'customer' => [Role::Customer],
    'staff' => [Role::Staff],
]);

test('guests are redirected to login instead of seeing the admin panel', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('logging in as an administrator opens the admin dashboard', function () {
    $admin = User::factory()->create([
        'role' => Role::Admin,
        'email_verified_at' => now(),
        'password' => 'password',
    ]);

    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));
});

test('a customer login keeps its existing dashboard redirect', function () {
    $customer = User::factory()->create([
        'role' => Role::Customer,
        'email_verified_at' => now(),
        'password' => 'password',
    ]);

    $this->post(route('login'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));
});

test('the admin sidebar marks the current page as active', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.movies.index'))
        ->assertOk()
        ->assertSee('aria-current="page"', false);
});
