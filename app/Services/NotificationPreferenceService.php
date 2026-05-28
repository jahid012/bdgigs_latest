<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use App\Support\EmailTemplateDefaults;
use Illuminate\Support\Str;

class NotificationPreferenceService
{
    private const TYPE_TO_PREFERENCE = [
        'message' => 'inboxMessages',
        'custom_offer' => 'customOffers',
        'dispute' => 'disputes',
        'resolution' => 'disputes',
        'review' => 'ratingReminders',
        'order' => 'orderUpdates',
        'payment' => 'payments',
        'wallet' => 'payments',
        'manual_payment_review' => 'orderUpdates',
        'withdrawal' => 'payouts',
        'gig update' => 'gigUpdates',
        'needs reply' => 'inboxMessages',
        'growth' => 'buyerBriefs',
    ];

    public function preferenceKeyFor(string $type): string
    {
        $normalized = Str::lower(trim($type));

        foreach (self::TYPE_TO_PREFERENCE as $signature => $preferenceKey) {
            if (str_contains($normalized, $signature)) {
                return $preferenceKey;
            }
        }

        return 'other';
    }

    public function allowsRealtime(User $user, UserNotification $notification): bool
    {
        $preference = $user->notificationPreference()->first();

        if ($preference && ! $preference->realtime_enabled) {
            return false;
        }

        $key = $notification->metadata['preferenceKey']
            ?? $this->preferenceKeyFor((string) $notification->type);
        $preferences = $preference?->preferences ?: [];

        if (array_key_exists($key, $preferences)) {
            return (bool) ($preferences[$key]['push'] ?? true);
        }

        if ($key === 'payouts' && ! array_key_exists('payouts', $preferences)) {
            return true;
        }

        if (array_key_exists('other', $preferences)) {
            return (bool) ($preferences['other']['push'] ?? true);
        }

        return true;
    }

    public function allowsEmail(User $user, string $preferenceKey): bool
    {
        $emailType = EmailTemplateDefaults::categoryForPreferenceKey($preferenceKey);
        $explicit = $user->emailPreferences()
            ->where('email_type', $emailType)
            ->first();

        if ($explicit) {
            return $explicit->is_enabled;
        }

        $preference = $user->notificationPreference()->first();
        $preferences = $preference?->preferences ?: [];

        if (array_key_exists($preferenceKey, $preferences)) {
            return (bool) ($preferences[$preferenceKey]['email'] ?? true);
        }

        if (array_key_exists('other', $preferences)) {
            return (bool) ($preferences['other']['email'] ?? true);
        }

        return true;
    }

    public function syncEmailPreferencesFromDashboard(User $user, array $preferences): void
    {
        foreach ($preferences as $preferenceKey => $channels) {
            if (! is_array($channels) || ! array_key_exists('email', $channels)) {
                continue;
            }

            $user->emailPreferences()->updateOrCreate(
                ['email_type' => $emailType = EmailTemplateDefaults::categoryForPreferenceKey((string) $preferenceKey)],
                ['is_enabled' => (bool) $channels['email']],
            );

            if ($emailType === 'marketing') {
                $user->forceFill([
                    'marketing_unsubscribed_at' => (bool) $channels['email'] ? null : now(),
                ])->save();
            }
        }
    }
}
