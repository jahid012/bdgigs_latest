<?php

namespace App\Support;

use App\Events\NotificationCreated;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\NotificationPreferenceService;

class MarketplaceNotifier
{
    public function __construct(private readonly NotificationPreferenceService $preferences)
    {
    }

    public function notify(
        User $user,
        string $type,
        string $title,
        string $detail,
        ?string $actionUrl = null,
        array $metadata = []
    ): UserNotification {
        $metadata = [
            ...$metadata,
            'preferenceKey' => $metadata['preferenceKey']
                ?? $this->preferences->preferenceKeyFor($type),
        ];
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'detail' => $detail,
            'action_url' => $actionUrl,
            'metadata' => $metadata,
        ]);

        if ($this->preferences->allowsRealtime($user, $notification)) {
            event(new NotificationCreated($notification));
        }

        return $notification;
    }
}
