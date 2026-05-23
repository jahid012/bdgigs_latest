<?php

namespace App\Services;

use App\Models\Gig;
use Illuminate\Support\Collection;

class GigMediaSyncService
{
    public function sync(Gig $gig, array $mediaPayload = [], array $legacyGalleryImages = []): Gig
    {
        $media = $this->normalizeMediaPayload($mediaPayload, $legacyGalleryImages, $gig);

        $gig->media()->delete();

        $media->each(fn (array $item) => $gig->media()->create($item));

        $imageUrls = $media
            ->where('type', 'image')
            ->pluck('url')
            ->values()
            ->all();
        $primaryImage = $media
            ->first(fn (array $item) => $item['type'] === 'image' && $item['is_primary'])
            ?: $media->firstWhere('type', 'image');

        $gig->forceFill([
            'image' => $primaryImage['url'] ?? $gig->image,
            'gallery_images' => $imageUrls ?: array_values(array_filter([$gig->image])),
        ])->save();

        return $gig->refresh()->load('media');
    }

    private function normalizeMediaPayload(array $mediaPayload, array $legacyGalleryImages, Gig $gig): Collection
    {
        $source = collect($mediaPayload);

        if ($source->isEmpty()) {
            $source = $this->mediaFromLegacyGallery($legacyGalleryImages, $gig);
        }

        if ($source->isEmpty() && $gig->image) {
            $source = collect([[
                'type' => 'image',
                'url' => $gig->image,
                'thumbnailUrl' => $gig->image,
                'altText' => $gig->title.' preview',
                'primary' => true,
            ]]);
        }

        return $source
            ->map(fn ($item, int $index) => $this->normalizeMediaItem($item, $index, $gig))
            ->filter()
            ->values()
            ->map(function (array $item, int $index): array {
                return [
                    ...$item,
                    'sort_order' => $index,
                    'is_primary' => $index === 0 || $item['is_primary'],
                ];
            });
    }

    private function mediaFromLegacyGallery(array $legacyGalleryImages, Gig $gig): Collection
    {
        return collect($legacyGalleryImages)
            ->when($gig->image, fn (Collection $images) => $images->prepend($gig->image))
            ->filter()
            ->unique()
            ->values()
            ->map(fn (string $image, int $index) => [
                'type' => 'image',
                'url' => $image,
                'thumbnailUrl' => $image,
                'altText' => $gig->title.' preview '.($index + 1),
                'primary' => $index === 0,
            ]);
    }

    private function normalizeMediaItem(mixed $item, int $index, Gig $gig): ?array
    {
        if (is_string($item)) {
            $item = ['url' => $item, 'type' => 'image'];
        }

        if (! is_array($item)) {
            return null;
        }

        $url = trim((string) ($item['url'] ?? ''));

        if ($url === '') {
            return null;
        }

        $type = in_array($item['type'] ?? 'image', ['image', 'video', 'document'], true)
            ? $item['type']
            : 'image';

        return [
            'type' => $type,
            'url' => $url,
            'thumbnail_url' => $item['thumbnailUrl'] ?? $item['thumbnail_url'] ?? ($type === 'image' ? $url : null),
            'alt_text' => $item['altText'] ?? $item['alt_text'] ?? $gig->title.' preview '.($index + 1),
            'is_primary' => (bool) ($item['primary'] ?? $item['isPrimary'] ?? $item['is_primary'] ?? false),
            'status' => $item['status'] ?? 'active',
            'metadata' => $item['metadata'] ?? [],
        ];
    }
}
