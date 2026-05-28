<?php

namespace App\Listeners;

use App\Events\PasswordResetRequested;
use App\Services\SuspiciousActivityService;
use App\Support\MarketplaceNotifier;
use Illuminate\Support\Facades\Cache;

class CreatePasswordResetRequestedNotification
{
    public function handle(PasswordResetRequested $event): void
    {
        $key = 'password-reset-requests:'.$event->user->id;
        $attempts = (int) Cache::increment($key);
        Cache::put($key, $attempts, now()->addHour());

        if ($attempts >= 4) {
            app(SuspiciousActivityService::class)->log(
                $event->user,
                'multiple_password_reset_requests',
                $attempts >= 8 ? 'critical' : 'high',
                'Multiple password reset requests were detected.',
                ['attempts' => $attempts],
            );
        }

        app(MarketplaceNotifier::class)->notify(
            $event->user,
            'password_reset_requested',
            'Password reset requested',
            'A password reset link was requested for your account.',
            '/dashboard/settings/account-security',
            ['preferenceKey' => 'accountUpdates'],
        );
    }
}
