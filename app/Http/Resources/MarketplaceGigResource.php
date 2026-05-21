<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceGigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'seller' => $this->seller_name,
            'sellerUserId' => $this->seller_id,
            'avatar' => $this->seller_avatar,
            'level' => $this->seller_level,
            'badge' => $this->badge,
            'image' => $this->image,
            'categoryId' => $this->category_id,
            'categoryLabel' => $this->category_label,
            'price' => $this->price_cents / 100,
            'rating' => (float) $this->rating,
            'reviews' => $this->reviews,
            'deliveryDays' => $this->delivery_days,
            'sellerLevel' => $this->metadata['sellerLevel'] ?? $this->seller_level,
            'sellerDetails' => $this->seller_details ?? [],
            'serviceOptions' => $this->service_options ?? [],
            'pro' => $this->pro,
            'instant' => $this->instant,
            'consultation' => $this->consultation,
            'featured' => $this->featured,
            'searchText' => $this->search_text,
        ];
    }
}
