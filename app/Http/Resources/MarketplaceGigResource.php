<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceGigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $seller = $this->seller;
        $sellerProfile = $seller?->sellerProfile;
        $media = $this->mediaPayload();
        $imageMedia = collect($media)->where('type', 'image')->values();
        $primaryMedia = $imageMedia->firstWhere('primary', true)
            ?: $imageMedia->first();
        $galleryImages = $imageMedia->pluck('url')->filter()->values()->all();
        $savedByCurrentUser = $request->user()
            && $this->relationLoaded('savedByUsers')
            && $this->savedByUsers->contains($request->user());
        $avatar = $seller?->avatar ?: $this->seller_avatar;
        $sellerName = $seller?->name ?: $this->seller_name;

        return [
            'id' => $this->slug,
            'title' => $this->title,
            'description' => $this->metadata['description'] ?? $this->descriptionFromMetadata(),
            'seller' => $sellerName,
            'sellerUserId' => $this->seller_id,
            'sellerUsername' => $seller?->username,
            'sellerProfilePath' => $seller?->username ? '/users/'.$seller->username : null,
            'sellerInitials' => initialsFromMarketplaceName($sellerName),
            'avatar' => $avatar,
            'level' => $this->seller_level,
            'badge' => $this->badge,
            'image' => $primaryMedia['url'] ?? $this->image,
            'imageAlt' => $primaryMedia['altText'] ?? $this->title.' preview',
            'galleryImages' => $galleryImages ?: array_values(array_filter([$this->image])),
            'media' => $media,
            'videos' => collect($media)->where('type', 'video')->values()->all(),
            'documents' => collect($media)->where('type', 'document')->values()->all(),
            'categoryId' => $this->category_id,
            'categoryLabel' => $this->category_label,
            'price' => $this->price_cents / 100,
            'priceFormatted' => '$'.number_format($this->price_cents / 100, 0),
            'rating' => (float) $this->rating,
            'reviews' => $this->reviews,
            'deliveryDays' => $this->delivery_days,
            'sellerLevel' => $this->metadata['sellerLevel'] ?? $this->seller_level,
            'sellerTitle' => $sellerProfile?->professional_title ?: '',
            'sellerBio' => $sellerProfile?->about ?: '',
            'sellerCountry' => $seller?->country ?: '',
            'sellerLanguages' => collect($sellerProfile?->languages ?: [])
                ->map(fn ($language) => is_array($language) ? ($language['language'] ?? null) : $language)
                ->filter()
                ->values()
                ->all(),
            'sellerMemberSince' => $seller?->created_at?->format('M Y'),
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
            'faqs' => $this->metadata['faqs'] ?? [],
            'relatedTags' => $this->metadata['relatedTags'] ?? array_values(array_filter($this->service_options ?? [])),
            'metadata' => $this->metadata ?: [],
            'saved' => $savedByCurrentUser,
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

        return collect($this->gallery_images ?: array_values(array_filter([$this->image])))
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

    private function descriptionFromMetadata(): string
    {
        $about = $this->metadata['about'] ?? null;

        if (is_array($about)) {
            return implode("\n\n", array_filter($about));
        }

        return is_string($about) ? $about : '';
    }
}

function initialsFromMarketplaceName(string $name): string
{
    return collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_substr($part, 0, 1))
        ->implode('');
}
