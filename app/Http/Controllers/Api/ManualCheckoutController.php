<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreManualCheckoutRequest;
use App\Http\Resources\ManualPaymentMethodResource;
use App\Http\Resources\OrderDetailResource;
use App\Models\Gig;
use App\Models\ManualPaymentMethod;
use App\Services\ManualOrderCheckoutService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ManualCheckoutController extends Controller
{
    public function methods(): AnonymousResourceCollection
    {
        return ManualPaymentMethodResource::collection(
            ManualPaymentMethod::query()
                ->where('active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
        );
    }

    public function store(
        StoreManualCheckoutRequest $request,
        Gig $gig,
        ManualOrderCheckoutService $checkout
    ): OrderDetailResource {
        $order = $checkout->create($request->user(), $gig->loadMissing('seller'), $request->validated());

        return OrderDetailResource::make($order);
    }
}
