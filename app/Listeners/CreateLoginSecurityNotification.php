<?php

namespace App\Listeners;

use App\Events\NewDeviceLoginDetected;
use App\Support\MarketplaceNotifier;

class CreateLoginSecurityNotification
{
    public function handle(NewDeviceLoginDetected $event): void
    {
        app(MarketplaceNotifier::class)->notify(
            $event->user,
            'security_login',
            'New login detected',
            'A login was detected from '.$event->context['browser'].' at '.$event->context['location'].'.',
            '/dashboard/settings/account-security',
            ['preferenceKey' => 'accountUpdates'],
        );
    }
}
