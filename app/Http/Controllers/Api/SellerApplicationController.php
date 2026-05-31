<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSellerApplicationRequest;
use App\Http\Resources\SellerApplicationResource;
use App\Services\SellerApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerApplicationController extends Controller
{
    public function show(Request $request): SellerApplicationResource
    {
        return SellerApplicationResource::make($request->user()->load(['sellerStatusEvents.actor', 'sellerStatusEvents.adminActor']));
    }

    public function store(StoreSellerApplicationRequest $request, SellerApplicationService $applications): JsonResponse
    {
        $seller = $applications->submit($request->user(), [
            ...$request->validated(),
            'source' => 'seller_dashboard',
        ]);

        return SellerApplicationResource::make($seller)
            ->response()
            ->setStatusCode(201);
    }
}
