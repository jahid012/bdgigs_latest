<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return NotificationResource::collection(
            UserNotification::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->limit(20)
                ->get()
        );
    }

    public function markRead(Request $request, UserNotification $notification): NotificationResource
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return NotificationResource::make($notification);
    }

    public function markAllRead(Request $request): AnonymousResourceCollection
    {
        UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->index($request);
    }
}
