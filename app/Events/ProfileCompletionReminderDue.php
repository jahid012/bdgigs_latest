<?php

namespace App\Events;

use App\Models\User;

class ProfileCompletionReminderDue
{
    public function __construct(public User $user, public array $missing = [])
    {
    }
}
