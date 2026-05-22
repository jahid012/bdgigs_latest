<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceGigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $seller = $this->seller;
        $savedByCurrentUser = $request->user()
            && $this->relationLoaded('savedByUsers')
            && $this->savedByUsers->contains($request->user());

        return [
            'id' => $this->slug,
            'title' => $this->title,
            'seller' => $seller?->name ?: $this->seller_name,
            'sellerUserId' => $this->seller_id,
            'sellerUsername' => $seller?->username,
            'sellerProfilePath' => $seller?->username ? '/users/'.$seller->username : null,
            'avatar' => $seller?->avatar ?: $this->seller_avatar,
            'level' => $this->seller_level,
            'badge' => $this->badge,
            'image' => $this->image,
            'galleryImages' => $this->gallery_images ?: array_values(array_filter([$this->image])),
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
            'packages' => $this->packages ?: [],
            'extras' => $this->extras ?: [],
            'requirements' => $this->requirements ?: [],
            'metadata' => $this->metadata ?: [],
            'saved' => $savedByCurrentUser,
        ];
    }
}
