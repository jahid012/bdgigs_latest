<?php

namespace App\Events;

use App\Models\User;

class SavedGigReminderDue
{
    public function __construct(public User $user, public array $payload = [])
    {
    }
}
