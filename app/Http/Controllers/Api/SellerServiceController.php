<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SellerServiceResource;
use App\Models\Gig;
use App\Support\MarketplaceNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class SellerServiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return SellerServiceResource::collection(
            Gig::query()
                ->where('seller_id', $request->user()->id)
                ->latest()
                ->get()
        );
    }

    public function store(Request $request, MarketplaceNotifier $notifier): SellerServiceResource
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:120'],
            'subcategory' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'array'],
            'packages' => ['nullable', 'array'],
            'extras' => ['nullable', 'array'],
            'requirements' => ['nullable', 'array'],
            'galleryImages' => ['nullable', 'array'],
        ]);

        $gig = Gig::create($this->attributesFromPayload($payload, $request));

        $notifier->notify(
            $request->user(),
            'Gig update',
            'Gig draft saved',
            "{$gig->title} is now available in your seller services.",
            "/dashboard/seller/services/{$gig->slug}/edit",
        );

        return SellerServiceResource::make($gig);
    }

    public function show(Request $request, Gig $gig): SellerServiceResource
    {
        $this->authorizeSeller($request, $gig);

        return SellerServiceResource::make($gig);
    }

    public function update(Request $request, Gig $gig, MarketplaceNotifier $notifier): SellerServiceResource
    {
        $this->authorizeSeller($request, $gig);

        $payload = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:120'],
            'subcategory' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'array'],
            'packages' => ['nullable', 'array'],
            'extras' => ['nullable', 'array'],
            'requirements' => ['nullable', 'array'],
            'galleryImages' => ['nullable', 'array'],
        ]);

        $gig->update($this->attributesFromPayload($payload, $request, $gig));

        $notifier->notify(
            $request->user(),
            'Gig update',
            'Gig updated',
            "{$gig->title} changes were saved.",
            "/dashboard/seller/services/{$gig->slug}/edit",
        );

        return SellerServiceResource::make($gig->refresh());
    }

    private function authorizeSeller(Request $request, Gig $gig): void
    {
        abort_unless($gig->seller_id === $request->user()->id, 403);
    }

    private function attributesFromPayload(array $payload, Request $request, ?Gig $existing = null): array
    {
        $title = trim($payload['title'] ?? $existing?->title ?? 'Untitled Gig');
        $category = trim($payload['category'] ?? $existing?->category_label ?? 'Programming & Tech');
        $tags = $payload['tags'] ?? $existing?->service_options ?? [];
        $packages = $payload['packages'] ?? $existing?->packages ?? [];
        $galleryImages = $payload['galleryImages'] ?? $existing?->gallery_images ?? [];
        $basePackage = $packages[0] ?? [];

        return [
            'seller_id' => $request->user()->id,
            'slug' => $existing?->slug ?? $this->uniqueSlug($title),
            'title' => $title,
            'seller_name' => $request->user()->name,
            'seller_avatar' => $existing?->seller_avatar,
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
            'status' => $existing?->status ?? 'Live',
            'status_class' => $existing?->status_class ?? 'status-completed',
            'packages' => $packages,
            'extras' => $payload['extras'] ?? $existing?->extras ?? [],
            'requirements' => $payload['requirements'] ?? $existing?->requirements ?? [],
            'gallery_images' => $galleryImages ?: [$existing?->image ?? '/assets/img/gig_images/1.png'],
            'metadata' => [
                'subcategory' => $payload['subcategory'] ?? $existing?->metadata['subcategory'] ?? null,
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
