<?php

namespace App\Services;

use App\Http\Resources\AuthSessionResource;
use App\Http\Resources\CreatorMarketplaceItemResource;
use App\Http\Resources\MarketplaceCategoryResource;
use App\Http\Resources\MarketplaceGigResource;
use App\Models\User;
use Illuminate\Http\Request;

class HomePageDataService
{
    public function __construct(
        private readonly MarketplaceContentService $content,
        private readonly MarketplaceGigCatalogService $gigs,
    ) {
    }

    public function bootstrap(Request $request): array
    {
        $user = $this->currentUser($request);

        return [
            'session' => AuthSessionResource::make($user)->resolve($request),
            'marketplaceCategories' => MarketplaceCategoryResource::collection(
                $this->content->categories()
            )->resolve($request),
            'creatorMarketplace' => CreatorMarketplaceItemResource::collection(
                $this->content->creatorMarketplaceItems()
            )->resolve($request),
            'featuredGigs' => MarketplaceGigResource::collection(
                $this->gigs->homeGigs($user, $this->recentGigSlugs($request))
            )->resolve($request),
            'csrfToken' => csrf_token(),
        ];
    }

    private function currentUser(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }

    private function recentGigSlugs(Request $request): array
    {
        $recentGigs = $request->query('recentGigs', []);
        $recentGigs = is_array($recentGigs) ? $recentGigs : [$recentGigs];

        return collect($recentGigs)
            ->flatMap(fn ($slug) => is_string($slug) ? explode(',', $slug) : [])
            ->map(fn (string $slug) => trim($slug))
            ->filter()
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }
}
