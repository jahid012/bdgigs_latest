<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from' => $this->sender_name,
            'text' => $this->body,
            'time' => $this->sent_at?->format('g:i A') ?? $this->created_at->format('g:i A'),
            'own' => $this->sender_id === $request->user()?->id,
        ];
    }
}
