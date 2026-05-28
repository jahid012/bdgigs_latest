<?php

namespace App\Events;

use App\Models\IdentityVerificationSubmission;
use App\Models\User;

class IdentityAdditionalDocumentRequested
{
    public function __construct(public IdentityVerificationSubmission $submission, public ?User $admin = null, public ?string $note = null)
    {
    }
}
