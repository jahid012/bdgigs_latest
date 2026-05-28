<?php

namespace App\Listeners;

use App\Events\EmailVerificationRequested;
use App\Services\EmailService;
use App\Services\EmailVerificationService;

class SendEmailVerificationEmail
{
    public function handle(EmailVerificationRequested $event): void
    {
        app(EmailService::class)->queueTemplateEmail('email_verification', $event->user, [
            'action_url' => app(EmailVerificationService::class)->verificationUrl($event->user),
            'notification_detail' => 'Use this secure link to verify your email address. The link expires in 60 minutes.',
        ], [
            'force' => true,
        ]);
    }
}
