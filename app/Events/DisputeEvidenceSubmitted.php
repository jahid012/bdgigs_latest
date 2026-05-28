<?php

namespace App\Events;

use App\Models\Dispute;
use App\Models\User;

class DisputeEvidenceSubmitted
{
    public function __construct(public Dispute $dispute, public User $actor, public array $evidence = [])
    {
    }
}
