<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class SellerServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'category' => $this->category_label,
            'rating' => number_format((float) $this->rating, 1),
            'price' => '$'.number_format($this->price_cents / 100, 0),
            'image' => $this->image,
            'tag' => $this->tag,
            'delivery' => $this->delivery_days.' '.($this->delivery_days === 1 ? 'day' : 'days'),
            'orders' => $this->orders_label,
            'conversion' => $this->conversion_label,
            'status' => $this->status,
            'statusClass' => $this->status_class,
            'statusKey' => $this->statusKey(),
            'previewPath' => '/gigs/'.$this->slug,
            'packages' => $this->packages ?? [],
            'extras' => $this->extras ?? [],
            'requirements' => $this->requirements ?? [],
            'galleryImages' => $this->gallery_images ?? [$this->image],
        ];
    }

    private function statusKey(): string
    {
        return match (Str::lower((string) $this->status)) {
            'published', 'live' => 'live',
            'paused' => 'paused',
            'draft' => 'draft',
            'pending', 'review', 'needs edit', 'rejected' => 'review',
            default => Str::slug((string) $this->status),
        };
    }
}
