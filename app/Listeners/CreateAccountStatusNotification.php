<?php

namespace App\Listeners;

use App\Events\AccountDeactivated;
use App\Events\AccountReactivated;
use App\Events\AccountSuspended;
use App\Support\MarketplaceNotifier;

class CreateAccountStatusNotification
{
    public function handle(AccountSuspended|AccountReactivated|AccountDeactivated $event): void
    {
        [$title, $detail] = match (true) {
            $event instanceof AccountSuspended => ['Account suspended', 'Your account has been suspended. '.$this->reason($event->reason)],
            $event instanceof AccountReactivated => ['Account reactivated', 'Your account is active again. '.$this->reason($event->reason)],
            default => ['Account deactivated', 'Your account has been deactivated. '.$this->reason($event->reason)],
        };

        app(MarketplaceNotifier::class)->notify(
            $event->user,
            'account_status',
            $title,
            trim($detail),
            '/dashboard/settings',
            ['preferenceKey' => 'accountUpdates'],
        );
    }

    private function reason(?string $reason): string
    {
        return $reason ? 'Reason: '.$reason : '';
    }
}
