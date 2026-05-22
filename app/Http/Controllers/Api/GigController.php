<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceGigResource;
use App\Models\Gig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GigController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return MarketplaceGigResource::collection(
            $this->marketplaceQuery($request)
                ->latest('featured')
                ->latest()
                ->get()
        );
    }

    public function show(Request $request, Gig $gig): MarketplaceGigResource
    {
        abort_unless(
            $this->isMarketplaceVisible($gig)
                || $request->user()?->id === $gig->seller_id,
            404
        );

        $gig->load('seller');

        if ($request->user()) {
            $gig->load([
                'savedByUsers' => fn ($savedByUsers) => $savedByUsers->whereKey($request->user()->id),
            ]);
        }

        return MarketplaceGigResource::make($gig);
    }

    private function marketplaceQuery(Request $request)
    {
        $query = Gig::query()
            ->with('seller')
            ->whereIn('status', ['Live', 'Published']);

        if ($request->user()) {
            $query->with([
                'savedByUsers' => fn ($savedByUsers) => $savedByUsers->whereKey($request->user()->id),
            ]);
        }

        return $query;
    }

    private function isMarketplaceVisible(Gig $gig): bool
    {
        return in_array($gig->status, ['Live', 'Published'], true);
    }
}
