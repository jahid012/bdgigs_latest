<?php

namespace App\Events;

use App\Models\IdentityVerificationSubmission;

class IdentityVerificationSubmitted
{
    public function __construct(public IdentityVerificationSubmission $submission)
    {
    }
}
