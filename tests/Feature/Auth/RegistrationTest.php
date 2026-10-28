<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
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
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();
    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user): bool {
        $mail = $notification->toMail($user);
        $html = view($mail->view['html'], $mail->data())->render();

        return $mail->subject === 'Welcome to CineBook — verify your email'
            && $mail->view['html'] === 'emails.auth.verify-email'
            && $mail->view['text'] === 'emails.auth.verify-email-text'
            && str_contains($html, 'Test User')
            && str_contains($html, 'Verify my email');
    });
});
