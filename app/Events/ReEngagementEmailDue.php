<?php

namespace App\Events;

use App\Models\User;

class ReEngagementEmailDue
{
    public function __construct(public User $user, public array $payload = [])
    {
    }
}
