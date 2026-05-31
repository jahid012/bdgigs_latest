<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationPreferencesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'preferences' => $this->preferences ?: [],
            'realtimeEnabled' => $this->realtime_enabled,
            'soundEnabled' => $this->sound_enabled,
        ];
    }
}
