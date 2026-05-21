<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'detail' => $this->detail,
            'type' => $this->type,
            'time' => $this->created_at->diffForHumans(short: true),
            'actionUrl' => $this->action_url,
            'readAt' => $this->read_at?->toISOString(),
        ];
    }
}
