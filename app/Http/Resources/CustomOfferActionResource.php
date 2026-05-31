<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomOfferActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $offer = $this->resource['offer'];
        $conversation = $this->resource['conversation'];
        $order = $this->resource['order'] ?? null;

        $payload = [
            'offer' => CustomOfferResource::make($offer->loadMissing(['gig', 'order'])),
            'conversation' => ConversationResource::make($conversation),
        ];

        if ($order) {
            $payload['order'] = [
                'code' => $order->code,
                'path' => '/dashboard/orders/'.$order->code,
            ];
        }

        return $payload;
    }
}
