<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomOfferGigOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'image' => $this->image,
            'price' => '$'.number_format($this->price_cents / 100, 0),
            'priceValue' => round($this->price_cents / 100, 2),
            'deliveryDays' => $this->delivery_days ?: 3,
        ];
    }
}
