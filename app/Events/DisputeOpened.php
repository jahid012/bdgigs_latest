<?php

namespace App\Events;

use App\Models\Dispute;

class DisputeOpened
{
    public function __construct(public Dispute $dispute)
    {
    }
}
