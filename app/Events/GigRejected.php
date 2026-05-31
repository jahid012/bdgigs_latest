<?php

namespace App\Events;

use App\Models\Admin;
use App\Models\Gig;
use App\Models\User;

class GigRejected
{
    public function __construct(public Gig $gig, public User|Admin|null $admin = null, public ?string $reason = null)
    {
    }
}
