<?php

namespace App\Events;

use App\Models\Admin;
use App\Models\Dispute;
use App\Models\User;

class DisputeEvidenceRequested
{
    public function __construct(public Dispute $dispute, public User|Admin $admin, public ?User $recipient, public string $note)
    {
    }
}
