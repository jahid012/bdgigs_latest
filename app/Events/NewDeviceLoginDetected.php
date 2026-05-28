<?php

namespace App\Events;

use App\Models\User;
use App\Models\UserLoginDevice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewDeviceLoginDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public UserLoginDevice $device,
        public array $context,
    ) {
    }
}
