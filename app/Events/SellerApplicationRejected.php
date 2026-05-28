<?php

namespace App\Events;

use App\Models\User;

class SellerApplicationRejected
{
    public function __construct(public User $seller, public ?User $admin = null, public ?string $reason = null)
    {
    }
}
