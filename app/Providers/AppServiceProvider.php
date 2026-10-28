<?php

namespace App\Providers;

use App\Models\Cinema;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function (User $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Welcome to CineBook — verify your email')
                ->view('emails.auth.verify-email', [
                    'user' => $notifiable,
                    'url' => $url,
                ])
                ->text('emails.auth.verify-email-text', [
                    'user' => $notifiable,
                    'url' => $url,
                ]);
        });

        ResetPassword::toMailUsing(function (User $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $expires = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');
            $data = [
                'user' => $notifiable,
                'url' => $url,
                'expires' => $expires,
            ];

            return (new MailMessage)
                ->subject('Reset your CineBook password')
                ->view('emails.auth.reset-password', $data)
                ->text('emails.auth.reset-password-text', $data);
        });

        View::composer([
            'dashboard',
            'layouts.cinema',
            'layouts.guest',
            'movies.catalog',
            'movies.index',
            'profile.edit',
        ], function (ViewInstance $view): void {
            $view->with('cinema', Cinema::query()->first());
        });

        Schema::defaultStringLength(191);
    }
}
