<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('administrators are redirected from the customer dashboard to the admin panel', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

test('staff accounts are redirected to the staff dashboard', function () {
    $staff = User::factory()->create(['role' => Role::Staff]);

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertRedirect(route('staff.today'));
});

test('customers can access the customer dashboard', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Browse movies')
        ->assertSee('My bookings');

    expect($customer->fresh()->role)->toBe(Role::Customer);
});

test('the demo accounts are seeded with their expected roles', function () {
    $this->seed();

    $admin = User::where('email', 'admin@cinebook.test')->firstOrFail();
    $customer = User::where('email', 'customer@cinebook.test')->firstOrFail();
    expect($admin->role)->toBe(Role::Admin)
        ->and(Hash::check('password', $admin->password))->toBeTrue()
        ->and($customer->role)->toBe(Role::Customer)
        ->and(User::where('role', Role::Staff)->exists())->toBeTrue();
});

test('the demo admin sees the admin dashboard after signing in', function () {
    $this->seed(AdminSeeder::class);

    $this->followingRedirects()
        ->post('/login', [
            'email' => 'admin@cinebook.test',
            'password' => 'password',
        ])
        ->assertOk()
        ->assertSee('Coming soon')
        ->assertSee('Admin navigation');
});
