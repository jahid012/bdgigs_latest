<?php

namespace App\Listeners;

use App\Events\PasswordChanged;
use App\Support\MarketplaceNotifier;

class CreatePasswordChangedNotification
{
    public function handle(PasswordChanged $event): void
    {
        app(MarketplaceNotifier::class)->notify(
            $event->user,
            'password_changed',
            'Password changed',
            'Your account password was changed successfully.',
            '/dashboard/settings/account-security',
            ['preferenceKey' => 'accountUpdates'],
        );
    }
}
