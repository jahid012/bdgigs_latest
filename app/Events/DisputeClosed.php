<?php

namespace App\Events;

use App\Models\Dispute;
use App\Models\User;

class DisputeClosed
{
    public function __construct(public Dispute $dispute, public User $admin)
    {
    }
}
