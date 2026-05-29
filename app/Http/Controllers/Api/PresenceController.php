<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function join(Request $request): array
    {
        $payload = $request->validate([
            'token' => ['nullable', 'string', 'max:4096'],
        ]);

        $request->user()->forceFill(['last_seen_at' => now()])->save();

        if (! empty($payload['token'])) {
            $request->user()
                ->pushSubscriptions()
                ->where('token', $payload['token'])
                ->whereNull('revoked_at')
                ->update([
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return ['data' => ['online' => true]];
    }

    public function heartbeat(Request $request): array
    {
        return $this->join($request);
    }
}
