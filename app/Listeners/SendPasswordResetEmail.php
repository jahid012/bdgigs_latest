<?php

namespace App\Listeners;

use App\Events\PasswordResetRequested;
use App\Services\EmailService;

class SendPasswordResetEmail
{
    public function handle(PasswordResetRequested $event): void
    {
        app(EmailService::class)->queueTemplateEmail('password_reset', $event->user, [
            'action_url' => $event->resetUrl,
            'notification_detail' => 'Use the secure reset link to choose a new password. Ignore this email if you did not request it.',
        ], [
            'force' => true,
        ]);
    }
}
