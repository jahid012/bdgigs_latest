<?php

namespace App\Services;

use App\Models\Gig;
use App\Models\User;
use Illuminate\Support\Str;

class SellerServicePayloadBuilder
{
    public function attributes(array $payload, User $seller, ?Gig $existing = null): array
    {
        $title = trim($payload['title'] ?? $existing?->title ?? 'Untitled Gig');
        $category = trim($payload['category'] ?? $existing?->category_label ?? 'Programming & Tech');
        $tags = $payload['tags'] ?? $existing?->service_options ?? [];
        $packages = $payload['packages'] ?? $existing?->packages ?? [];
        $mediaImages = collect($payload['media'] ?? [])
            ->filter(fn ($item) => ($item['type'] ?? 'image') === 'image')
            ->pluck('url')
            ->filter()
            ->values()
            ->all();
        $galleryImages = $payload['galleryImages'] ?? [];

        if (! $galleryImages) {
            $galleryImages = $mediaImages ?: ($existing?->gallery_images ?? []);
        }

        $basePackage = $packages[0] ?? [];
        $metadata = $existing?->metadata ?? [];

        return [
            'seller_id' => $seller->id,
            'slug' => $existing?->slug ?? $this->uniqueSlug($title),
            'title' => $title,
            'seller_name' => $seller->name,
            'seller_avatar' => $seller->avatar ?: $existing?->seller_avatar,
            'seller_level' => $existing?->seller_level ?? 'Level 2',
            'badge' => $existing?->badge,
            'image' => $galleryImages[0] ?? $existing?->image ?? '/assets/img/gig_images/1.png',
            'category_id' => Str::slug($category),
            'category_label' => $category,
            'price_cents' => $this->moneyToCents($basePackage['price'] ?? ($existing ? $existing->price_cents / 100 : 0)),
            'rating' => $existing?->rating ?? 0,
            'reviews' => $existing?->reviews ?? 0,
            'delivery_days' => $this->deliveryDays($basePackage['delivery'] ?? $existing?->delivery_days ?? 3),
            'seller_details' => $existing?->seller_details ?? ['level-2', 'english', 'online'],
            'service_options' => collect($tags)->map(fn ($tag) => Str::slug((string) $tag))->filter()->values()->all(),
            'pro' => $existing?->pro ?? false,
            'instant' => $existing?->instant ?? false,
            'consultation' => $existing?->consultation ?? false,
            'featured' => $existing?->featured ?? false,
            'search_text' => strtolower($title.' '.$category.' '.implode(' ', $tags)),
            'tag' => $tags[0] ?? $existing?->tag ?? 'New Gig',
            'orders_label' => $existing?->orders_label ?? '0 active',
            'conversion_label' => $existing?->conversion_label ?? 'New listing',
            'status' => $existing?->status ?? 'draft',
            'status_class' => $existing?->status_class ?? 'status-progress',
            'packages' => $packages,
            'extras' => $payload['extras'] ?? $existing?->extras ?? [],
            'requirements' => $payload['requirements'] ?? $existing?->requirements ?? [],
            'gallery_images' => $galleryImages ?: [$existing?->image ?? '/assets/img/gig_images/1.png'],
            'metadata' => [
                ...$metadata,
                'subcategory' => $payload['subcategory'] ?? $existing?->metadata['subcategory'] ?? null,
                'description' => $payload['description'] ?? $existing?->metadata['description'] ?? null,
                'faqs' => $payload['faqs'] ?? $existing?->metadata['faqs'] ?? [],
                'relatedTags' => array_values(array_filter($tags)),
            ],
        ];
    }

    private function uniqueSlug(string $title): string
    {
        $slug = Str::slug($title) ?: 'seller-gig';

        if (! Gig::where('slug', $slug)->exists()) {
            return $slug;
        }

        return $slug.'-'.now()->timestamp;
    }

    private function moneyToCents(string|int|float $value): int
    {
        return (int) round((float) preg_replace('/[^0-9.]/', '', (string) $value) * 100);
    }

    private function deliveryDays(string|int $value): int
    {
        if (is_numeric($value)) {
            return max(1, (int) $value);
        }

        preg_match('/(\d+)/', $value, $matches);

        return max(1, (int) ($matches[1] ?? 3));
    }
}
