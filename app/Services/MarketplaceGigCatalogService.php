<?php

namespace App\Services;

use App\Models\Gig;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MarketplaceGigCatalogService
{
    private const VISIBLE_STATUSES = ['Live', 'Published', 'approved'];

    public function marketplaceGigs(?User $user = null): Collection
    {
        return $this->visibleQuery($user)
            ->latest('featured')
            ->latest()
            ->get();
    }

    public function homeGigs(?User $user = null, array $recentSlugs = [], int $limit = 8): Collection
    {
        $recentSlugs = collect($recentSlugs)
            ->filter()
            ->unique()
            ->take(12)
            ->values()
            ->all();

        $gigs = $this->visibleQuery($user)
            ->where(function (Builder $query) use ($recentSlugs) {
                $query->where('featured', true);

                if ($recentSlugs !== []) {
                    $query->orWhereIn('slug', $recentSlugs);
                }
            })
            ->latest('featured')
            ->latest()
            ->take($limit + count($recentSlugs))
            ->get();

        if ($gigs->isNotEmpty() || $recentSlugs !== []) {
            return $gigs;
        }

        return $this->visibleQuery($user)
            ->latest()
            ->take($limit)
            ->get();
    }

    public function visibleQuery(?User $user = null): Builder
    {
        $query = Gig::query()
            ->with(['seller.sellerProfile', 'media'])
            ->whereIn('status', self::VISIBLE_STATUSES);

        if ($user) {
            $query->with([
                'savedByUsers' => fn ($savedByUsers) => $savedByUsers->whereKey($user->id),
            ]);
        }

        return $query;
    }

    public function isVisible(Gig $gig): bool
    {
        return in_array($gig->status, self::VISIBLE_STATUSES, true);
    }
}
