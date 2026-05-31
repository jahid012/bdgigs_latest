<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreatorMarketplaceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'icon' => $this->icon,
            'linkUrl' => $this->link_url ?: '/search/gigs?query='.urlencode($this->title).'&source=creator-card',
            'sortOrder' => $this->sort_order,
            'color' => ($this->metadata ?? [])['color'] ?? null,
        ];
    }
}
