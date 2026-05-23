<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SellerServiceResource;
use App\Models\Gig;
use App\Services\GigMediaSyncService;
use App\Services\SellerGigLifecycleService;
use App\Support\MarketplaceNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SellerServiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return SellerServiceResource::collection(
            Gig::query()
                ->with('media')
                ->where('seller_id', $request->user()->id)
                ->latest()
                ->get()
        );
    }

    public function store(
        Request $request,
        MarketplaceNotifier $notifier,
        GigMediaSyncService $mediaSync
    ): SellerServiceResource
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:120'],
            'subcategory' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'array'],
            'packages' => ['nullable', 'array'],
            'extras' => ['nullable', 'array'],
            'requirements' => ['nullable', 'array'],
            'description' => ['nullable', 'string', 'max:10000'],
            'faqs' => ['nullable', 'array'],
            'galleryImages' => ['nullable', 'array'],
            'media' => ['nullable', 'array', 'max:12'],
            'media.*.type' => ['nullable', 'string', 'in:image,video,document'],
            'media.*.url' => ['required_with:media', 'string', 'max:1500000'],
            'media.*.thumbnailUrl' => ['nullable', 'string', 'max:1500000'],
            'media.*.altText' => ['nullable', 'string', 'max:255'],
            'media.*.primary' => ['nullable', 'boolean'],
            'media.*.metadata' => ['nullable', 'array'],
        ]);

        $gig = Gig::create($this->attributesFromPayload($payload, $request));
        $gig = $mediaSync->sync($gig, $payload['media'] ?? [], $payload['galleryImages'] ?? []);

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

        return SellerServiceResource::make($gig->load('media'));
    }

    public function update(
        Request $request,
        Gig $gig,
        MarketplaceNotifier $notifier,
        GigMediaSyncService $mediaSync
    ): SellerServiceResource
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
            'description' => ['nullable', 'string', 'max:10000'],
            'faqs' => ['nullable', 'array'],
            'galleryImages' => ['nullable', 'array'],
            'media' => ['nullable', 'array', 'max:12'],
            'media.*.type' => ['nullable', 'string', 'in:image,video,document'],
            'media.*.url' => ['required_with:media', 'string', 'max:1500000'],
            'media.*.thumbnailUrl' => ['nullable', 'string', 'max:1500000'],
            'media.*.altText' => ['nullable', 'string', 'max:255'],
            'media.*.primary' => ['nullable', 'boolean'],
            'media.*.metadata' => ['nullable', 'array'],
        ]);

        $gig->update($this->attributesFromPayload($payload, $request, $gig));
        $gig = $mediaSync->sync($gig, $payload['media'] ?? [], $payload['galleryImages'] ?? $gig->gallery_images ?? []);

        $notifier->notify(
            $request->user(),
            'Gig update',
            'Gig updated',
            "{$gig->title} changes were saved.",
            "/dashboard/seller/services/{$gig->slug}/edit",
        );

        return SellerServiceResource::make($gig->refresh()->load('media'));
    }

    public function updateStatus(
        Request $request,
        Gig $gig,
        MarketplaceNotifier $notifier,
        SellerGigLifecycleService $lifecycle
    ): SellerServiceResource {
        $this->authorizeSeller($request, $gig);

        $payload = $request->validate([
            'action' => ['required', 'string', 'in:activate,pause'],
        ]);

        $gig = $payload['action'] === 'activate'
            ? $lifecycle->activate($gig)
            : $lifecycle->pause($gig);

        $notifier->notify(
            $request->user(),
            'Gig update',
            $payload['action'] === 'activate' ? 'Gig activated' : 'Gig paused',
            "{$gig->title} is now {$gig->status}.",
            "/dashboard/seller/services/{$gig->slug}/edit",
        );

        return SellerServiceResource::make($gig->load('media'));
    }

    public function destroy(
        Request $request,
        Gig $gig,
        MarketplaceNotifier $notifier,
        SellerGigLifecycleService $lifecycle
    ): Response {
        $this->authorizeSeller($request, $gig);

        $title = $gig->title;
        $lifecycle->delete($gig);

        $notifier->notify(
            $request->user(),
            'Gig update',
            'Gig deleted',
            "{$title} was removed from your seller services.",
            '/dashboard/seller/services',
        );

        return response()->noContent();
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
            'seller_id' => $request->user()->id,
            'slug' => $existing?->slug ?? $this->uniqueSlug($title),
            'title' => $title,
            'seller_name' => $request->user()->name,
            'seller_avatar' => $request->user()->avatar ?: $existing?->seller_avatar,
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
