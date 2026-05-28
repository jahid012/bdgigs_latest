<?php

namespace App\Events;

use App\Models\User;

class RecentlyViewedReminderDue
{
    public function __construct(public User $user, public array $payload = [])
    {
    }
}
