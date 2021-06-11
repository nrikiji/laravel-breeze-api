<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // URL for email address verification
        VerifyEmail::createUrlUsing(function ($notifiable) {
            // Generate signed URLs to request via API
            $endpoint = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
            );

            // Generate URLs to include in emails.
            $parsed = parse_url($endpoint);

            $url = "https://example.com" . $parsed["path"] . "?" . $parsed["query"];

            return $url;
        });

        // URL for password reset
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return 'https://example.com/reset-password?token=' . $token;
        });
    }
}
