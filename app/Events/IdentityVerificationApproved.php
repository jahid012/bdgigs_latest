<?php

namespace App\Events;

use App\Models\IdentityVerificationSubmission;
use App\Models\User;

class IdentityVerificationApproved
{
    public function __construct(public IdentityVerificationSubmission $submission, public ?User $admin = null)
    {
    }
}
