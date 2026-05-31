<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreatorMarketplaceItemResource;
use App\Http\Resources\MarketplaceCategoryResource;
use App\Http\Resources\SearchSuggestionResource;
use App\Services\MarketplaceContentService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class MarketplaceContentController extends Controller
{
    public function categories(MarketplaceContentService $content): AnonymousResourceCollection
    {
        return MarketplaceCategoryResource::collection($content->categories());
    }

    public function creatorMarketplace(MarketplaceContentService $content): AnonymousResourceCollection
    {
        return CreatorMarketplaceItemResource::collection($content->creatorMarketplaceItems());
    }

    public function searchSuggestions(Request $request, MarketplaceContentService $content): AnonymousResourceCollection
    {
        $query = trim((string) $request->query('q', ''));

        return SearchSuggestionResource::collection($content->searchSuggestions($query));
    }
}
