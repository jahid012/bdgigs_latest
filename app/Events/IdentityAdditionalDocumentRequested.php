<?php

namespace App\Events;

use App\Models\Admin;
use App\Models\IdentityVerificationSubmission;
use App\Models\User;

class IdentityAdditionalDocumentRequested
{
    public function __construct(public IdentityVerificationSubmission $submission, public User|Admin|null $admin = null, public ?string $note = null)
    {
    }
}
