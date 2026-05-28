<?php

namespace App\Events;

use App\Models\Gig;

class GigCreated
{
    public function __construct(public Gig $gig)
    {
    }
}
