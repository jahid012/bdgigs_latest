<?php

namespace App\Events;

use App\Models\Gig;

class GigEdited
{
    public function __construct(public Gig $gig)
    {
    }
}
