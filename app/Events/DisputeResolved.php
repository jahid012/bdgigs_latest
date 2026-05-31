<?php

namespace App\Events;

use App\Models\Admin;
use App\Models\Dispute;
use App\Models\User;

class DisputeResolved
{
    public function __construct(public Dispute $dispute, public User|Admin $admin)
    {
    }
}
