<?php

use App\Models\User;

dataset('staff routes', [
    'today' => ['staff.today'],
    'scan' => ['staff.scan'],
    'roster' => ['staff.roster'],
    'lookup' => ['staff.lookup'],
]);

test('a guest is sent to the login page', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with('staff routes');

test('a guest cannot submit a ticket token', function () {
    $this->post(route('staff.scan.store'), ['token' => 'DEMO-ADMIT-G7'])->assertRedirect(route('login'));
});

test('a customer gets a 403 on every staff page', function (string $route) {
    $this->actingAs(User::factory()->customer()->make())
        ->get(route($route))
        ->assertForbidden();
})->with('staff routes');

test('a customer cannot submit a ticket token', function () {
    $this->actingAs(User::factory()->customer()->make())
        ->post(route('staff.scan.store'), ['token' => 'DEMO-ADMIT-G7'])
        ->assertForbidden();
});

test('staff can open every staff page', function (string $route) {
    $this->actingAs(User::factory()->staff()->make())
        ->get(route($route))
        ->assertOk();
})->with('staff routes');

test('an admin can open every staff page', function (string $route) {
    $this->actingAs(User::factory()->admin()->make())
        ->get(route($route))
        ->assertOk();
})->with('staff routes');

test('the staff header shows the name of the logged-in user', function () {
    $this->actingAs(User::factory()->staff()->make(['name' => 'Jamie Okafor']))
        ->get(route('staff.today'))
        ->assertSee('Jamie Okafor')
        ->assertDontSee('Sam Rivera');
});

test('staff are sent to today\'s shows after logging in', function () {
    $staff = User::factory()->staff()->create();

    $this->post('/login', ['email' => $staff->email, 'password' => 'password'])
        ->assertRedirect(route('staff.today'));

    $this->assertAuthenticatedAs($staff);
});

test('staff always land on today\'s shows, even with another page remembered', function () {
    $staff = User::factory()->staff()->create();

    $this->withSession(['url.intended' => url('/profile')])
        ->post('/login', ['email' => $staff->email, 'password' => 'password'])
        ->assertRedirect(route('staff.today'));
});

test('a customer still goes to the dashboard after logging in', function () {
    $customer = User::factory()->customer()->create();

    $this->post('/login', ['email' => $customer->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
});
