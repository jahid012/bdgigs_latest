<?php

namespace App\Events;

use App\Models\IdentityVerificationSubmission;
use App\Models\User;

class IdentityVerificationRejected
{
    public function __construct(public IdentityVerificationSubmission $submission, public ?User $admin = null, public ?string $reason = null)
    {
    }
}
