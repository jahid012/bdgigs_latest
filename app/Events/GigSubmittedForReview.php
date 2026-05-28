<?php

namespace App\Events;

use App\Models\Gig;

class GigSubmittedForReview
{
    public function __construct(public Gig $gig)
    {
    }
}
