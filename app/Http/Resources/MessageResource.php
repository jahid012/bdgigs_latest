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
            'conversationId' => $this->conversation?->public_id,
            'senderId' => $this->sender_id,
            'recipientId' => $this->recipient_id,
            'clientId' => $this->client_id,
            'from' => $this->sender_name,
            'text' => $this->body,
            'time' => $this->sent_at?->format('g:i A') ?? $this->created_at->format('g:i A'),
            'sentAt' => $this->sent_at?->toISOString(),
            'readAt' => $this->read_at?->toISOString(),
            'own' => $this->sender_id === $request->user()?->id,
            'saved' => $this->relationLoaded('savedByUsers')
                ? $this->savedByUsers->contains('id', $request->user()?->id)
                : false,
            'attachments' => MessageAttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
