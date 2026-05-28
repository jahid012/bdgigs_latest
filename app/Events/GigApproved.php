<?php

namespace App\Events;

use App\Models\Gig;
use App\Models\User;

class GigApproved
{
    public function __construct(public Gig $gig, public ?User $admin = null)
    {
    }
}
