<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $seller = $this->seller;
        $media = $this->relationLoaded('media') && $this->media->isNotEmpty()
            ? $this->media
            : collect();
        $primaryImage = $media->first(fn ($item) => $item->type === 'image' && $item->is_primary)
            ?: $media->firstWhere('type', 'image');

        return [
            'id' => $this->slug,
            'title' => $this->title,
            'seller' => $seller?->name ?: $this->seller_name,
            'sellerUsername' => $seller?->username,
            'sellerProfilePath' => $seller?->username ? '/users/'.$seller->username : null,
            'avatar' => $seller?->avatar ?: $this->seller_avatar,
            'rating' => number_format((float) $this->rating, 1),
            'price' => '$'.number_format($this->price_cents / 100, 0),
            'image' => $primaryImage?->url ?: $this->image,
            'tag' => $this->tag ?: $this->category_label,
            'delivery' => $this->delivery_days.' '.($this->delivery_days === 1 ? 'day' : 'days'),
            'savedAt' => $this->pivot?->created_at?->diffForHumans() ?: 'Saved',
        ];
    }
}
