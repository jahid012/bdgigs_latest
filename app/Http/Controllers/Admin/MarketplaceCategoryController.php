<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreMarketplaceCategoryRequest;
use App\Models\MarketplaceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarketplaceCategoryController extends AdminController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $categoriesQuery = MarketplaceCategory::query()
            ->with(['parent', 'children' => fn ($children) => $children->ordered()])
            ->whereNull('parent_id')
            ->ordered();

        if ($search !== '') {
            $categoriesQuery->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('children', fn ($children) => $children
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%"));
            });
        }

        return $this->panelView('admin.pages.marketplace-categories', [
            'pageTitle' => 'Marketplace Categories',
            'pageEyebrow' => 'Catalog navigation',
            'pageDescription' => 'Manage the category tree used by the public marketplace mega menu and search suggestions.',
            'searchPlaceholder' => 'Search categories',
            'categories' => $categoriesQuery->get(),
            'parentOptions' => MarketplaceCategory::query()->whereNull('parent_id')->ordered()->get(),
            'stats' => [
                ['label' => 'Categories', 'value' => number_format(MarketplaceCategory::whereNull('parent_id')->count()), 'meta' => 'Top level'],
                ['label' => 'Subcategories', 'value' => number_format(MarketplaceCategory::whereNotNull('parent_id')->count()), 'meta' => 'Nested'],
                ['label' => 'Mega menu', 'value' => number_format(MarketplaceCategory::megaMenu()->count()), 'meta' => 'Visible'],
                ['label' => 'Inactive', 'value' => number_format(MarketplaceCategory::where('active', false)->count()), 'meta' => 'Hidden'],
            ],
            'searchQuery' => $search,
        ]);
    }

    public function store(StoreMarketplaceCategoryRequest $request)
    {
        MarketplaceCategory::create($this->payload($request));

        return back()->withNotify('success', 'Category was created.', 'Category saved');
    }

    public function update(StoreMarketplaceCategoryRequest $request, MarketplaceCategory $category)
    {
        $category->update($this->payload($request, $category));

        return back()->withNotify('success', 'Category was updated.', 'Category saved');
    }

    public function destroy(MarketplaceCategory $category)
    {
        abort_unless(auth('admin')->user()?->can('categories.manage'), 403);

        $category->children()->update(['parent_id' => null]);
        $category->delete();

        return back()->withNotify('success', 'Category was deleted.', 'Category removed');
    }

    private function payload(StoreMarketplaceCategoryRequest $request, ?MarketplaceCategory $category = null): array
    {
        $payload = $request->payload();
        $payload['slug'] = $this->uniqueSlug($payload['slug'] ?: Str::slug($payload['name']), $category?->id);
        $payload['sort_order'] = $payload['sort_order'] ?? 0;

        if ($category && (int) ($payload['parent_id'] ?? 0) === (int) $category->id) {
            $payload['parent_id'] = null;
        }

        return $payload;
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug ?: Str::random(8);
        $candidate = $base;
        $counter = 2;

        while (MarketplaceCategory::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
