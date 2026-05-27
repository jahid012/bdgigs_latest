<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isBuyer = $request->user() && (int) $this->buyer_id === (int) $request->user()->id;
        $isSeller = $request->user() && (int) $this->seller_id === (int) $request->user()->id;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price_cents / 100,
            'priceFormatted' => '$'.number_format($this->price_cents / 100, 0),
            'currency' => $this->currency,
            'deliveryDays' => $this->delivery_days,
            'revisions' => $this->revisions,
            'terms' => $this->terms,
            'status' => $this->status,
            'statusLabel' => str($this->status)->headline()->toString(),
            'expiresAt' => $this->expires_at?->toISOString(),
            'expiresLabel' => $this->expires_at?->format('M j, Y'),
            'paidAt' => $this->paid_at?->toISOString(),
            'orderCode' => $this->order?->code,
            'orderPath' => $this->order
                ? ($isSeller ? '/dashboard/seller/orders/'.$this->order->code : '/dashboard/orders/'.$this->order->code)
                : null,
            'gig' => $this->gig ? [
                'id' => $this->gig->slug,
                'title' => $this->gig->title,
                'image' => $this->gig->image,
            ] : null,
            'canPay' => $isBuyer && $this->isPayable(),
            'canDecline' => $isBuyer && $this->status === 'pending',
            'canCancel' => $isSeller && $this->status === 'pending',
        ];
    }
}
