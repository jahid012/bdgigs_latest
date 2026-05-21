<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceGigResource;
use App\Models\Gig;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GigController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MarketplaceGigResource::collection(
            Gig::query()->latest('featured')->latest()->get()
        );
    }

    public function show(Gig $gig): MarketplaceGigResource
    {
        return MarketplaceGigResource::make($gig);
    }
}
