<?php

namespace App\Services;

use App\Http\Resources\IdentityVerificationResource;
use App\Http\Resources\NotificationPreferencesResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserSettingsSnapshotService
{
    public function snapshot(Request $request): array
    {
        $user = $request->user();
        $preferences = $user->notificationPreference()->firstOrCreate([]);
        $identity = $user->identityVerificationSubmissions()->latest()->first();

        return [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'country' => $user->country,
                'visibility' => $user->last_seen_at?->greaterThan(now()->subSeconds(90)) ? 'Online' : 'Offline',
                'verificationStatus' => $user->verification_status,
                'emailVerified' => $user->hasVerifiedEmail(),
                'emailVerifiedAt' => $user->email_verified_at?->toISOString(),
                'accountStatus' => $user->suspended_at
                    ? 'suspended'
                    : ($user->deactivated_at ? 'deactivated' : 'active'),
                'sellerStatus' => $user->seller_status ?: 'not_applied',
                'marketingUnsubscribed' => (bool) $user->marketing_unsubscribed_at,
                'twoFactorEnabled' => filled($user->two_factor_secret),
            ],
            'notifications' => NotificationPreferencesResource::make($preferences)->resolve($request),
            'sessions' => $this->sessions($request, $user),
            'identity' => $identity
                ? IdentityVerificationResource::make($identity)->resolve($request)
                : null,
        ];
    }

    private function sessions(Request $request, User $user): array
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->latest('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($session) => [
                'id' => $session->id,
                'ipAddress' => $session->ip_address,
                'userAgent' => $session->user_agent,
                'lastActivity' => now()->setTimestamp($session->last_activity)->diffForHumans(),
                'current' => $session->id === $request->session()->getId(),
            ])
            ->values()
            ->all();
    }
}
