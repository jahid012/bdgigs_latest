<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;

class PushSubscriptionService
{
    public function store(User $user, array $payload): PushSubscription
    {
        return $user->pushSubscriptions()->updateOrCreate(
            ['token' => $payload['token']],
            [
                'platform' => $payload['platform'] ?? 'web',
                'metadata' => $payload['metadata'] ?? [],
                'last_seen_at' => now(),
                'revoked_at' => null,
            ],
        );
    }

    public function revoke(User $user, string $token): void
    {
        $user->pushSubscriptions()
            ->where('token', $token)
            ->update(['revoked_at' => now()]);
    }
}
