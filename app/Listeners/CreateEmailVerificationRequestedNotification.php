<?php

namespace App\Listeners;

use App\Events\EmailVerificationRequested;
use App\Support\MarketplaceNotifier;

class CreateEmailVerificationRequestedNotification
{
    public function handle(EmailVerificationRequested $event): void
    {
        app(MarketplaceNotifier::class)->notify(
            $event->user,
            'email_verification_requested',
            'Verification email sent',
            'A secure verification link was sent to your email address.',
            '/verify-email',
            ['preferenceKey' => 'accountUpdates'],
        );
    }
}
