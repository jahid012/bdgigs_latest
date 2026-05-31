<?php

namespace App\Services;

use App\Models\User;

class PresenceService
{
    public function join(User $user, ?string $token = null): array
    {
        $user->forceFill(['last_seen_at' => now()])->save();

        if (filled($token)) {
            $user->pushSubscriptions()
                ->where('token', $token)
                ->whereNull('revoked_at')
                ->update([
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return ['online' => true];
    }
}
