<?php

namespace App\Events;

use App\Models\Gig;
use App\Models\User;

class GigPaused
{
    public function __construct(public Gig $gig, public ?User $actor = null, public ?string $reason = null)
    {
    }
}
