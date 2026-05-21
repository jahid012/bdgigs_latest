<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OfflinePushNotifier
{
    public function notifyOfflineUser(User $user, string $title, string $body, array $data = []): bool
    {
        if ($this->isOnline($user)) {
            return false;
        }

        $subscriptions = $user->pushSubscriptions()
            ->whereNull('revoked_at')
            ->get();

        if ($subscriptions->isEmpty()) {
            return false;
        }

        $sent = false;

        foreach ($subscriptions as $subscription) {
            $sent = $this->sendToSubscription($subscription, $title, $body, $data) || $sent;
        }

        return $sent;
    }

    public function isOnline(User $user): bool
    {
        if (! $user->last_seen_at) {
            return false;
        }

        return $user->last_seen_at->greaterThan(now()->subSeconds((int) config('services.firebase.offline_after', 90)));
    }

    private function sendToSubscription(PushSubscription $subscription, string $title, string $body, array $data): bool
    {
        $projectId = config('services.firebase.project_id');
        $token = $this->accessToken();

        if (! $projectId || ! $token) {
            Log::info('Firebase push skipped because Firebase credentials are not configured.', [
                'user_id' => $subscription->user_id,
            ]);

            return false;
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $subscription->token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => collect($data)
                        ->map(fn ($value) => (string) $value)
                        ->all(),
                    'webpush' => [
                        'fcm_options' => [
                            'link' => $data['url'] ?? '/dashboard/messages',
                        ],
                    ],
                ],
            ]);

        if ($response->successful()) {
            $subscription->forceFill(['last_used_at' => now()])->save();

            return true;
        }

        if (Str::contains($response->body(), ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
            $subscription->forceFill(['revoked_at' => now()])->save();
        }

        Log::warning('Firebase push delivery failed.', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    private function accessToken(): ?string
    {
        return Cache::remember('firebase.messaging.access_token', now()->addMinutes(50), function () {
            $credentials = $this->credentials();

            if (! $credentials) {
                return null;
            }

            $now = time();
            $header = $this->base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ]));
            $claims = $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            $unsignedJwt = "{$header}.{$claims}";

            openssl_sign($unsignedJwt, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

            $response = Http::asForm()->post($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsignedJwt.'.'.$this->base64UrlEncode($signature),
            ]);

            return $response->json('access_token');
        });
    }

    private function credentials(): ?array
    {
        $raw = config('services.firebase.credentials_json');
        $path = config('services.firebase.credentials');

        if ($raw) {
            return $this->normalizeCredentials(json_decode($raw, true));
        }

        if ($path && is_file($path)) {
            return $this->normalizeCredentials(json_decode((string) file_get_contents($path), true));
        }

        return null;
    }

    private function normalizeCredentials(?array $credentials): ?array
    {
        if (! $credentials) {
            return null;
        }

        if (! empty($credentials['private_key'])) {
            $credentials['private_key'] = str_replace('\\n', "\n", $credentials['private_key']);
        }

        return $credentials;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
