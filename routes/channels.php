<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});

Broadcast::channel('presence.online', function (User $user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
