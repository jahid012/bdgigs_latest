<?php

namespace App\Events;

use App\Models\User;

class CheckoutAbandonmentReminderDue
{
    public function __construct(public User $user, public array $payload = [])
    {
    }
}
