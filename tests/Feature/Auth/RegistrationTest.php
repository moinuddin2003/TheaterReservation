<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSee('auth-layout auth-layout--compact', false);
});

test('new users can register', function () {
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();
    expect($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertNothingSent();

    $this->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Continue without verifying')
        ->assertSee('Send verification email');

    $this->post(route('verification.continue'))
        ->assertRedirect(route('dashboard', absolute: false));
});

test('continuing without verification returns to the intended destination', function () {
    Notification::fake();

    $this->withSession(['url.intended' => route('movies.index')])
        ->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('verification.notice'));

    $this->post(route('verification.continue'))
        ->assertRedirect(route('movies.index'));
});
