<?php

namespace App\Services;

use App\Models\User;

class ProfileCompletionService
{
    public function missingFields(User $user): array
    {
        $missing = [];

        if (blank($user->avatar)) {
            $missing[] = 'profile photo';
        }

        if (blank($user->country)) {
            $missing[] = 'country';
        }

        if ($user->profile_type === 'seller') {
            $profile = $user->sellerProfile;

            if (blank($profile?->professional_title)) {
                $missing[] = 'seller headline';
            }

            if (blank($profile?->about)) {
                $missing[] = 'seller bio';
            }

            if ($user->sellerPayoutMethods()->where('active', true)->doesntExist()) {
                $missing[] = 'payout method';
            }
        } else {
            $profile = $user->buyerProfile;

            if (blank($profile?->overview)) {
                $missing[] = 'buyer bio';
            }
        }

        return $missing;
    }

    public function isComplete(User $user): bool
    {
        return $this->missingFields($user) === [];
    }
}
