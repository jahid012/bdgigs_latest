<?php

namespace App\Events;

use App\Models\Dispute;
use App\Models\User;

class DisputeRejected
{
    public function __construct(public Dispute $dispute, public User $admin)
    {
    }
}
