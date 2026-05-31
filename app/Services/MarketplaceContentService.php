<?php

namespace App\Services;

use App\Models\CreatorMarketplaceItem;
use App\Models\Gig;
use App\Models\MarketplaceCategory;
use Illuminate\Support\Collection;

class MarketplaceContentService
{
    public function categories(): Collection
    {
        return MarketplaceCategory::query()
            ->active()
            ->megaMenu()
            ->whereNull('parent_id')
            ->with(['children' => fn ($children) => $children->active()->megaMenu()->ordered()])
            ->ordered()
            ->get();
    }

    public function creatorMarketplaceItems(): Collection
    {
        return CreatorMarketplaceItem::query()
            ->active()
            ->ordered()
            ->get();
    }

    public function searchSuggestions(string $query): Collection
    {
        if (mb_strlen($query) < 2) {
            return collect();
        }

        $gigs = Gig::query()
            ->whereIn('status', ['Live', 'Published', 'approved'])
            ->where(function ($gigs) use ($query) {
                $gigs
                    ->where('title', 'like', "%{$query}%")
                    ->orWhere('category_label', 'like', "%{$query}%")
                    ->orWhere('search_text', 'like', "%{$query}%");
            })
            ->latest('featured')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Gig $gig) => [
                'id' => 'gig-'.$gig->id,
                'type' => 'Gig',
                'title' => $gig->title,
                'description' => $gig->category_label ?: 'Marketplace service',
                'path' => '/gigs/'.$gig->slug,
            ]);

        $categories = MarketplaceCategory::query()
            ->active()
            ->where(function ($categories) use ($query) {
                $categories
                    ->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->with('parent')
            ->ordered()
            ->take(4)
            ->get()
            ->map(fn (MarketplaceCategory $category) => [
                'id' => 'category-'.$category->id,
                'type' => $category->parent_id ? 'Subcategory' : 'Category',
                'title' => $category->name,
                'description' => $category->description ?: 'Browse related services',
                'path' => $category->path(),
            ]);

        $keywords = Gig::query()
            ->whereIn('status', ['Live', 'Published', 'approved'])
            ->where('category_label', 'like', "%{$query}%")
            ->select('category_label')
            ->distinct()
            ->take(3)
            ->pluck('category_label')
            ->filter()
            ->map(fn (string $keyword, int $index) => [
                'id' => 'keyword-'.$index.'-'.str($keyword)->slug()->toString(),
                'type' => 'Keyword',
                'title' => $keyword,
                'description' => 'Search marketplace services',
                'path' => '/search/gigs?query='.urlencode($keyword).'&source=suggestion',
            ]);

        return $gigs
            ->concat($categories)
            ->concat($keywords)
            ->unique('path')
            ->take(8)
            ->values();
    }
}
