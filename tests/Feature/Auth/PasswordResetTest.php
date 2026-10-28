<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response
        ->assertOk()
        ->assertSee('Forgot your password?')
        ->assertSee('We’ll send you a secure link to choose a new password.')
        ->assertSee('class="auth-form"', false)
        ->assertSee('Send reset link')
        ->assertSee(route('login'), false);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $mail = $notification->toMail($user);
        $html = view($mail->view['html'], $mail->data())->render();

        return $mail->subject === 'Reset your CineBook password'
            && $mail->view['html'] === 'emails.auth.reset-password'
            && $mail->view['text'] === 'emails.auth.reset-password-text'
            && str_contains($html, $user->name)
            && str_contains($html, 'Reset my password');
    });
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response
            ->assertOk()
            ->assertSee('Choose a new password')
            ->assertSee('auth-layout auth-layout--compact', false)
            ->assertSee('Almost back to booking')
            ->assertSee('Confirm new password')
            ->assertSee('name="token"', false)
            ->assertSee('Reset password')
            ->assertSee(route('password.request'), false);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});
