<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreManualCheckoutRequest;
use App\Http\Resources\ManualPaymentMethodResource;
use App\Http\Resources\OrderDetailResource;
use App\Models\Gig;
use App\Models\ManualPaymentMethod;
use App\Services\ManualOrderCheckoutService;
use App\Services\OrderPaymentLifecycleService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

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

    public function wallet(
        Request $request,
        Gig $gig,
        OrderPaymentLifecycleService $payments
    ): OrderDetailResource {
        $payload = $request->validate([
            'packageId' => ['required', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $order = $payments->createWalletOrder($request->user(), $gig->loadMissing('seller'), $payload);

        return OrderDetailResource::make($order->loadMissing(['buyer', 'seller', 'gig', 'activities', 'invoice']));
    }
}
