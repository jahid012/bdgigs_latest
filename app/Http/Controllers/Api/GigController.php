<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceGigResource;
use App\Models\Gig;
use App\Models\User;
use App\Services\MarketplaceGigCatalogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GigController extends Controller
{
    public function __construct(private readonly MarketplaceGigCatalogService $catalog)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return MarketplaceGigResource::collection(
            $this->catalog->marketplaceGigs($this->currentUser($request))
        );
    }

    public function show(Request $request, Gig $gig): MarketplaceGigResource
    {
        abort_unless(
            $this->catalog->isVisible($gig)
                || $this->currentUser($request)?->id === $gig->seller_id,
            404
        );

        $gig->load(['seller.sellerProfile', 'media']);

        if ($user = $this->currentUser($request)) {
            $gig->load([
                'savedByUsers' => fn ($savedByUsers) => $savedByUsers->whereKey($user->id),
            ]);
        }

        return MarketplaceGigResource::make($gig);
    }

    private function currentUser(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }
}
