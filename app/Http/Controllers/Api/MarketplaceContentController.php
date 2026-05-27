<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreatorMarketplaceItem;
use App\Models\Gig;
use App\Models\MarketplaceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceContentController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = MarketplaceCategory::query()
            ->active()
            ->megaMenu()
            ->whereNull('parent_id')
            ->with(['children' => fn ($children) => $children->active()->megaMenu()->ordered()])
            ->ordered()
            ->get()
            ->map(fn (MarketplaceCategory $category) => $this->categoryRow($category))
            ->values();

        return response()->json(['data' => $categories]);
    }

    public function creatorMarketplace(): JsonResponse
    {
        $items = CreatorMarketplaceItem::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (CreatorMarketplaceItem $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'image' => $item->image,
                'icon' => $item->icon,
                'linkUrl' => $item->link_url ?: '/search/gigs?query='.urlencode($item->title).'&source=creator-card',
                'sortOrder' => $item->sort_order,
                'color' => ($item->metadata ?? [])['color'] ?? null,
            ])
            ->values();

        return response()->json(['data' => $items]);
    }

    public function searchSuggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $gigs = Gig::query()
            ->whereIn('status', ['Live', 'Published'])
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
            ->whereIn('status', ['Live', 'Published'])
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

        return response()->json([
            'data' => $gigs
                ->concat($categories)
                ->concat($keywords)
                ->unique('path')
                ->take(8)
                ->values(),
        ]);
    }

    private function categoryRow(MarketplaceCategory $category): array
    {
        return [
            'id' => $category->id,
            'label' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'icon' => $category->icon,
            'image' => $category->image,
            'path' => $category->path(),
            'sortOrder' => $category->sort_order,
            'children' => $category->children
                ->map(fn (MarketplaceCategory $child) => [
                    'id' => $child->id,
                    'label' => $child->name,
                    'slug' => $child->slug,
                    'description' => $child->description,
                    'icon' => $child->icon,
                    'image' => $child->image,
                    'path' => $child->path(),
                    'sortOrder' => $child->sort_order,
                ])
                ->values()
                ->all(),
        ];
    }
}
