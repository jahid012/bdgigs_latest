<?php

namespace App\Events;

use App\Models\Admin;
use App\Models\Dispute;
use App\Models\User;

class DisputeAdminJoined
{
    public function __construct(public Dispute $dispute, public User|Admin $admin)
    {
    }
}
