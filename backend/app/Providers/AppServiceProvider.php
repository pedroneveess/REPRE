<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token): string {
            return rtrim((string) config('app.url'), '/') . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($user->getEmailForPasswordReset());
        });
    }
}
