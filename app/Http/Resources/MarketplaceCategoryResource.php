<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'image' => $this->image,
            'path' => $this->path(),
            'sortOrder' => $this->sort_order,
            'children' => MarketplaceCategoryChildResource::collection(
                $this->whenLoaded('children', collect())
            ),
        ];
    }
}
