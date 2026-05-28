<?php

namespace App\Listeners;

use App\Events\PasswordChanged;
use App\Services\EmailService;

class SendPasswordChangedEmail
{
    public function handle(PasswordChanged $event): void
    {
        app(EmailService::class)->queueTemplateEmail('password_changed', $event->user, [
            'action_url' => '/dashboard/settings/account-security',
            'notification_detail' => $event->source === 'reset'
                ? 'Your password was reset successfully.'
                : 'Your password was changed from account security settings.',
        ], [
            'force' => true,
        ]);
    }
}
