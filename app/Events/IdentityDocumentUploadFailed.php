<?php

namespace App\Events;

use App\Models\User;

class IdentityDocumentUploadFailed
{
    public function __construct(public User $user, public string $reason)
    {
    }
}
