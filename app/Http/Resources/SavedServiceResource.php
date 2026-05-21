<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'seller' => $this->seller_name,
            'rating' => number_format((float) $this->rating, 1),
            'price' => '$'.number_format($this->price_cents / 100, 0),
            'image' => $this->image,
            'tag' => $this->tag ?: $this->category_label,
            'delivery' => $this->delivery_days.' '.($this->delivery_days === 1 ? 'day' : 'days'),
        ];
    }
}
