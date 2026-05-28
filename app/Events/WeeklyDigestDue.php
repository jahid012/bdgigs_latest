<?php

namespace App\Events;

use App\Models\User;

class WeeklyDigestDue
{
    public function __construct(public User $user, public array $payload = [])
    {
    }
}
