<?php

namespace App\Events;

use App\Models\Admin;
use App\Models\User;

class SellerApplicationApproved
{
    public function __construct(public User $seller, public User|Admin|null $admin = null)
    {
    }
}
