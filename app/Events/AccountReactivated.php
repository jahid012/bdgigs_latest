<?php

namespace App\Events;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountReactivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public User|Admin|null $actor = null,
        public ?string $reason = null,
    ) {
    }
}
