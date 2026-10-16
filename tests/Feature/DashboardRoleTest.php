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

test('staff members see the staff dashboard', function () {
    $staff = User::factory()->create(['role' => Role::Staff]);

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Welcome to Staff Dashboard')
        ->assertDontSee('Welcome to Admin Page');
});

test('customers keep the default dashboard', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee("You're logged in!");

    expect($customer->fresh()->role)->toBe(Role::Customer);
});

test('the demo accounts are seeded with their expected roles', function () {
    $this->seed();

    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $staff = User::where('email', 'staff@example.com')->firstOrFail();

    expect($admin->role)->toBe(Role::Admin)
        ->and($staff->role)->toBe(Role::Staff)
        ->and(Hash::check('password', $admin->password))->toBeTrue()
        ->and(Hash::check('password', $staff->password))->toBeTrue();
});

test('the demo admin sees the admin dashboard after signing in', function () {
    $this->seed(AdminSeeder::class);

    $this->followingRedirects()
        ->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])
        ->assertOk()
        ->assertSee("Today's bookings")
        ->assertSee('Revenue today')
        ->assertSee('Top movies')
        ->assertDontSee('Coming soon');
});

test('the demo staff member sees the staff dashboard after signing in', function () {
    $this->seed(StaffSeeder::class);

    $this->followingRedirects()
        ->post('/login', [
            'email' => 'staff@example.com',
            'password' => 'password',
        ])
        ->assertOk()
        ->assertSee('Welcome to Staff Dashboard');
});
