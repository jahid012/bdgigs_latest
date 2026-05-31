<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreCreatorMarketplaceItemRequest;
use App\Models\CreatorMarketplaceItem;
use Illuminate\Http\Request;

class CreatorMarketplaceItemController extends AdminController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $itemsQuery = CreatorMarketplaceItem::query()->ordered();

        if ($search !== '') {
            $itemsQuery->where(function ($query) use ($search) {
                $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $this->panelView('admin.pages.creator-marketplace-items', [
            'pageTitle' => 'Creator Marketplace',
            'pageEyebrow' => 'Home content',
            'pageDescription' => 'Manage the slideable creator marketplace cards shown on the homepage.',
            'searchPlaceholder' => 'Search creator cards',
            'items' => $itemsQuery->get(),
            'stats' => [
                ['label' => 'Items', 'value' => number_format(CreatorMarketplaceItem::count()), 'meta' => 'Total cards'],
                ['label' => 'Active', 'value' => number_format(CreatorMarketplaceItem::active()->count()), 'meta' => 'Frontend'],
                ['label' => 'Inactive', 'value' => number_format(CreatorMarketplaceItem::where('active', false)->count()), 'meta' => 'Hidden'],
                ['label' => 'With images', 'value' => number_format(CreatorMarketplaceItem::whereNotNull('image')->count()), 'meta' => 'Visual cards'],
            ],
            'searchQuery' => $search,
        ]);
    }

    public function store(StoreCreatorMarketplaceItemRequest $request)
    {
        CreatorMarketplaceItem::create($request->payload());

        return back()->withNotify('success', 'Creator card was created.', 'Content saved');
    }

    public function update(StoreCreatorMarketplaceItemRequest $request, CreatorMarketplaceItem $item)
    {
        $item->update($request->payload());

        return back()->withNotify('success', 'Creator card was updated.', 'Content saved');
    }

    public function destroy(CreatorMarketplaceItem $item)
    {
        abort_unless(auth('admin')->user()?->can('content.manage'), 403);

        $item->delete();

        return back()->withNotify('success', 'Creator card was deleted.', 'Content removed');
    }
}
