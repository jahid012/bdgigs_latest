<?php

namespace App\Services;

use App\Models\EmailPreferenceToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailPreferenceTokenService
{
    public function issue(User $user, ?string $emailType = 'marketing', ?\DateTimeInterface $expiresAt = null): string
    {
        $plainToken = Str::random(64);

        $user->emailPreferenceTokens()->create([
            'token_hash' => hash('sha256', $plainToken),
            'email_type' => $emailType,
            'expires_at' => $expiresAt ?: now()->addDays(30),
        ]);

        return $plainToken;
    }

    public function findValid(string $token): ?EmailPreferenceToken
    {
        $record = EmailPreferenceToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->first();

        if (! $record || ($record->expires_at && $record->expires_at->isPast())) {
            return null;
        }

        return $record;
    }

    public function updatePreference(string $token, ?string $emailType, bool $enabled): ?User
    {
        $record = $this->findValid($token);

        if (! $record?->user) {
            return null;
        }

        return DB::transaction(function () use ($record, $emailType, $enabled) {
            $type = $emailType ?: $record->email_type ?: 'marketing';
            $user = $record->user;

            $user->emailPreferences()->updateOrCreate(
                ['email_type' => $type],
                ['is_enabled' => $enabled],
            );

            if ($type === 'marketing') {
                $user->forceFill([
                    'marketing_unsubscribed_at' => $enabled ? null : now(),
                ])->save();
            }

            $record->forceFill(['used_at' => now()])->save();

            return $user->fresh(['emailPreferences']);
        });
    }

    public function unsubscribeAllMarketing(string $token): ?User
    {
        return $this->updatePreference($token, 'marketing', false);
    }
}
