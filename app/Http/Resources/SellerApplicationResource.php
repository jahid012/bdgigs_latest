<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->seller_status ?: 'not_applied';

        return [
            'status' => $status,
            'reason' => $this->seller_status_reason,
            'reviewedAt' => $this->seller_status_reviewed_at?->toISOString(),
            'canSubmit' => in_array($status, ['not_applied', 'rejected'], true),
            'history' => $this->whenLoaded('sellerStatusEvents', fn () => $this->sellerStatusEvents
                ->sortByDesc('created_at')
                ->map(fn ($event) => [
                    'from' => $event->from_status,
                    'to' => $event->to_status,
                    'reason' => $event->reason,
                    'actorName' => $event->adminActor?->name ?? $event->actor?->name,
                    'createdAt' => $event->created_at?->format('M j, Y g:i A'),
                ])
                ->values()
                ->all()),
        ];
    }
}
