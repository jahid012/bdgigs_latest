<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\URL;

class EmailVerificationService
{
    public function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );
    }
}
