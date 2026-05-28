<?php

namespace App\Events;

use App\Models\Gig;
use App\Models\User;

class GigRejected
{
    public function __construct(public Gig $gig, public ?User $admin = null, public ?string $reason = null)
    {
    }
}
