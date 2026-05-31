<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WebhookStatusResource;
use App\Models\User;
use Illuminate\Http\Request;
use Pusher\Pusher;

class BroadcastWebhookController extends Controller
{
    public function handle(Request $request): WebhookStatusResource
    {
        $pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            [
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'useTLS' => config('broadcasting.connections.pusher.options.useTLS', true),
                'host' => config('broadcasting.connections.pusher.options.host') ?: null,
                'scheme' => config('broadcasting.connections.pusher.options.scheme') ?: null,
                'port' => config('broadcasting.connections.pusher.options.port') ?: null,
            ],
        );

        $headers = [
            'X-Pusher-Key' => $request->header('X-Pusher-Key', ''),
            'X-Pusher-Signature' => $request->header('X-Pusher-Signature', ''),
        ];

        $payload = $request->getContent();
        $webhook = $pusher->webhook($headers, $payload);

        foreach ($webhook->get_events() as $event) {
            if (($event->name ?? '') !== 'member_removed') {
                continue;
            }

            $userId = $event->user_id ?? $event->user_info->id ?? null;

            if (! $userId) {
                continue;
            }

            $user = User::find($userId);
            if ($user) {
                $user->last_seen_at = now();
                $user->save();
            }
        }

        return WebhookStatusResource::make(['status' => 'ok']);
    }
}
