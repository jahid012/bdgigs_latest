<?php

namespace App\Events;

use App\Models\Gig;
use App\Models\User;

class GigReactivated
{
    public function __construct(public Gig $gig, public ?User $actor = null)
    {
    }
}
