<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class SellerServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $media = $this->mediaPayload();
        $images = collect($media)->where('type', 'image')->pluck('url')->filter()->values()->all();
        $primaryImage = collect($media)->first(fn ($item) => $item['type'] === 'image' && $item['primary'])
            ?: collect($media)->firstWhere('type', 'image');

        return [
            'id' => $this->slug,
            'title' => $this->title,
            'category' => $this->category_label,
            'rating' => number_format((float) $this->rating, 1),
            'price' => '$'.number_format($this->price_cents / 100, 0),
            'image' => $primaryImage['url'] ?? $this->image,
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
            'description' => $this->metadata['description'] ?? '',
            'faqs' => $this->metadata['faqs'] ?? [],
            'galleryImages' => $images ?: ($this->gallery_images ?? [$this->image]),
            'media' => $media,
            'videos' => collect($media)->where('type', 'video')->values()->all(),
            'documents' => collect($media)->where('type', 'document')->values()->all(),
        ];
    }

    private function mediaPayload(): array
    {
        if ($this->relationLoaded('media') && $this->media->isNotEmpty()) {
            return $this->media
                ->map(fn ($media) => [
                    'id' => $media->id,
                    'type' => $media->type,
                    'url' => $media->url,
                    'thumbnailUrl' => $media->thumbnail_url ?: $media->url,
                    'altText' => $media->alt_text,
                    'sortOrder' => $media->sort_order,
                    'primary' => $media->is_primary,
                    'status' => $media->status,
                    'metadata' => $media->metadata ?: [],
                ])
                ->values()
                ->all();
        }

        return collect($this->gallery_images ?? array_values(array_filter([$this->image])))
            ->filter()
            ->unique()
            ->values()
            ->map(fn (string $image, int $index) => [
                'id' => null,
                'type' => 'image',
                'url' => $image,
                'thumbnailUrl' => $image,
                'altText' => $this->title.' preview '.($index + 1),
                'sortOrder' => $index,
                'primary' => $index === 0,
                'status' => 'active',
                'metadata' => [],
            ])
            ->all();
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
