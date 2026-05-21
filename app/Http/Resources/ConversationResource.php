<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isSeller = $request->query('role') === 'seller' || $this->seller_id === $request->user()?->id;
        $name = $isSeller ? $this->buyer_name : $this->seller_name;
        $lastMessage = $this->messages->last();

        return [
            'id' => $this->public_id,
            'initials' => initials($name),
            'name' => $name,
            'role' => $isSeller ? 'Buyer' : ($this->metadata['sellerRole'] ?? 'Seller'),
            'service' => $this->subject,
            'status' => $this->status,
            'statusClass' => $this->status_class,
            'time' => $lastMessage?->sent_at?->diffForHumans(short: true) ?? $this->updated_at->diffForHumans(short: true),
            'unread' => $isSeller ? $this->seller_unread_count : $this->buyer_unread_count,
            'priority' => $this->priority,
            'preview' => Str::limit($lastMessage?->body ?? '', 90),
            'messages' => MessageResource::collection($this->messages),
        ];
    }
}

function initials(string $name): string
{
    return collect(explode(' ', trim($name)))
        ->filter()
        ->map(fn (string $part) => Str::substr($part, 0, 1))
        ->take(2)
        ->implode('');
}
