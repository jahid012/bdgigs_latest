<?php

namespace App\Listeners;

use App\Events\AccountDeactivated;
use App\Events\AccountReactivated;
use App\Events\AccountSuspended;
use App\Services\EmailService;

class SendAccountStatusEmail
{
    public function handle(AccountSuspended|AccountReactivated|AccountDeactivated $event): void
    {
        $template = match (true) {
            $event instanceof AccountSuspended => 'account_suspended',
            $event instanceof AccountReactivated => 'account_reactivated',
            default => 'account_deactivated',
        };

        app(EmailService::class)->queueTemplateEmail($template, $event->user, [
            'action_url' => $event instanceof AccountReactivated ? '/dashboard' : '/dashboard/settings',
            'notification_detail' => $event->reason
                ? 'Reason: '.$event->reason
                : 'No additional reason was recorded for this account status change.',
        ], [
            'force' => true,
        ]);
    }
}
