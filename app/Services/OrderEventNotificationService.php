<?php

namespace App\Services;

use App\Models\User;
use App\Support\MarketplaceNotifier;
use App\Services\NotificationPreferenceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderEventNotificationService
{
    public function __construct(
        private readonly MarketplaceNotifier $notifier,
        private readonly NotificationPreferenceService $preferences,
    ) {
    }

    public function send(
        User $user,
        string $type,
        string $title,
        string $detail,
        ?string $actionUrl = null,
        array $metadata = [],
        ?string $emailSubject = null
    ): void {
        $metadata = ['preferenceKey' => 'orderUpdates', ...$metadata];

        $this->notifier->notify(
            $user,
            $type,
            $title,
            $detail,
            $actionUrl,
            $metadata,
        );

        if ($this->preferences->allowsEmail($user, $metadata['preferenceKey'])) {
            $this->sendEmail($user, $emailSubject ?: $title, $detail, $actionUrl);
        }
    }

    private function sendEmail(User $user, string $subject, string $detail, ?string $actionUrl): void
    {
        if (! $user->email) {
            return;
        }

        try {
            Mail::raw($this->emailBody($user, $detail, $actionUrl), function ($message) use ($user, $subject) {
                $message->to($user->email, $user->name)->subject($subject);
            });
        } catch (\Throwable $exception) {
            Log::warning('Order notification email could not be sent.', [
                'user_id' => $user->id,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function emailBody(User $user, string $detail, ?string $actionUrl): string
    {
        $lines = [
            'Hi '.$user->name.',',
            '',
            $detail,
        ];

        if ($actionUrl) {
            $lines[] = '';
            $lines[] = 'Open this in BDGigs: '.url($actionUrl);
        }

        $lines[] = '';
        $lines[] = 'BDGigs';

        return implode("\n", $lines);
    }
}
