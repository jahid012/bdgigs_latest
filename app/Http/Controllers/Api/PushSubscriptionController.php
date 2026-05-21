<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): array
    {
        $payload = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['nullable', 'string', 'max:40'],
            'metadata' => ['nullable', 'array'],
        ]);

        $subscription = $request->user()->pushSubscriptions()->updateOrCreate(
            ['token' => $payload['token']],
            [
                'platform' => $payload['platform'] ?? 'web',
                'metadata' => $payload['metadata'] ?? [],
                'last_seen_at' => now(),
                'revoked_at' => null,
            ],
        );

        return [
            'data' => [
                'id' => $subscription->id,
                'platform' => $subscription->platform,
            ],
        ];
    }

    public function destroy(Request $request, string $token): array
    {
        $request->user()
            ->pushSubscriptions()
            ->where('token', $token)
            ->update(['revoked_at' => now()]);

        return ['data' => ['revoked' => true]];
    }
}
