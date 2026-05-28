<?php

namespace App\Listeners;

use App\Events\NewDeviceLoginDetected;
use App\Services\EmailService;

class SendLoginSecurityAlert
{
    public function handle(NewDeviceLoginDetected $event): void
    {
        app(EmailService::class)->queueTemplateEmail('login_alert', $event->user, [
            'action_url' => '/forgot-password',
            'notification_detail' => 'Login time: '.$event->context['login_time']
                .'. IP address: '.$event->context['ip_address']
                .'. Browser/device: '.$event->context['browser'].' on '.$event->context['device']
                .'. Approximate location: '.$event->context['location']
                .'. If this was not you, reset your password immediately.',
        ], [
            'force' => true,
        ]);
    }
}
