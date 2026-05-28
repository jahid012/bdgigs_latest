<?php

namespace App\Listeners;

use App\Events\EmailVerified;
use App\Support\MarketplaceNotifier;

class CreateEmailVerifiedNotification
{
    public function handle(EmailVerified $event): void
    {
        app(MarketplaceNotifier::class)->notify(
            $event->user,
            'email_verified',
            'Email verified',
            'Your email address is verified. Marketplace checkout and account actions are available.',
            '/dashboard/settings/account-security',
            ['preferenceKey' => 'accountUpdates'],
        );
    }
}
