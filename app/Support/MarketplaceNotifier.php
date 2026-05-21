<?php

namespace App\Support;

use App\Events\NotificationCreated;
use App\Models\User;
use App\Models\UserNotification;

class MarketplaceNotifier
{
    public function notify(
        User $user,
        string $type,
        string $title,
        string $detail,
        ?string $actionUrl = null,
        array $metadata = []
    ): UserNotification {
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'detail' => $detail,
            'action_url' => $actionUrl,
            'metadata' => $metadata,
        ]);

        event(new NotificationCreated($notification));

        return $notification;
    }
}
