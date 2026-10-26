<?php

use App\Models\Cinema;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('signed-in customers see the dashboard in the cinema layout', function () {
    Cinema::factory()->create(['name' => 'Downtown Cinema']);
    $user = User::factory()->create(['name' => 'Cinema Customer']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Welcome to Downtown Cinema, Cinema Customer')
        ->assertSee('<span>Downtown Cinema</span>', false)
        ->assertSee('Browse movies')
        ->assertSee('My bookings')
        ->assertSee('aria-haspopup="menu"', false)
        ->assertSee('Profile')
        ->assertSee('Log out')
        ->assertSee('cinema-app', false);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
