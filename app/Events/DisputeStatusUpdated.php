<?php

namespace App\Events;

use App\Models\Dispute;
use App\Models\User;

class DisputeStatusUpdated
{
    public function __construct(public Dispute $dispute, public User $actor, public string $previousStatus)
    {
    }
}
