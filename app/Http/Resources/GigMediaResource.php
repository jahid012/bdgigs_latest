<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GigMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'url' => $this->url,
            'thumbnailUrl' => $this->thumbnail_url ?: $this->url,
            'altText' => $this->alt_text,
            'sortOrder' => $this->sort_order,
            'primary' => $this->is_primary,
            'status' => $this->status,
            'metadata' => $this->metadata ?: [],
        ];
    }
}
