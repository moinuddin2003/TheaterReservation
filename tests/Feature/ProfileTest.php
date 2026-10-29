<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('profile page is displayed', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('customers can access their customer profile', function () {
    $customer = User::factory()->create(['role' => Role::Customer]);

    $this->actingAs($customer)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Your account')
        ->assertSee($customer->name);
});

test('staff can access their profile from the account menu', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Your account')
        ->assertSee('account-layout', false)
        ->assertSee('Profile details')
        ->assertSee('Save password')
        ->assertSee('Staff account')
        ->assertDontSee('My bookings');
});

test('the staff name opens the profile and logout account menu', function () {
    $staff = User::factory()->staff()->create(['name' => 'Jamie Okafor']);

    $this->actingAs($staff)
        ->get(route('staff.today'))
        ->assertOk()
        ->assertSee('Jamie Okafor')
        ->assertSee('site-account-menu__trigger', false)
        ->assertSee(route('profile.edit'), false)
        ->assertSee(route('logout'), false);
});

test('staff can update their profile information', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->patch(route('profile.update'), [
            'name' => 'Jamie Okafor',
            'email' => 'jamie.okafor@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $staff->refresh();

    $this->assertSame('Jamie Okafor', $staff->name);
    $this->assertSame('jamie.okafor@example.com', $staff->email);
    $this->assertNull($staff->email_verified_at);
});

test('staff can update their password', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->from(route('profile.edit'))
        ->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $this->assertTrue(Hash::check('new-password', $staff->refresh()->password));
});
