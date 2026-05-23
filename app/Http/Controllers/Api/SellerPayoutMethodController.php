<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSellerPayoutMethodRequest;
use App\Http\Resources\SellerPayoutMethodResource;
use App\Models\SellerPayoutMethod;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SellerPayoutMethodController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SellerPayoutMethodResource::collection(
            request()->user()->sellerPayoutMethods()->latest()->get()
        );
    }

    public function store(StoreSellerPayoutMethodRequest $request): SellerPayoutMethodResource
    {
        $method = $request->user()->sellerPayoutMethods()->create(
            $this->attributes($request->validated())
        );

        return SellerPayoutMethodResource::make($method);
    }

    public function update(
        StoreSellerPayoutMethodRequest $request,
        SellerPayoutMethod $method
    ): SellerPayoutMethodResource {
        $this->authorizeOwner($method);

        $method->update($this->attributes($request->validated()));

        return SellerPayoutMethodResource::make($method->refresh());
    }

    private function authorizeOwner(SellerPayoutMethod $method): void
    {
        abort_unless($method->user_id === request()->user()->id, 403);
    }

    private function attributes(array $payload): array
    {
        return [
            'type' => $payload['type'],
            'label' => trim($payload['label']),
            'account_holder' => trim($payload['accountHolder']),
            'account_number' => trim($payload['accountNumber']),
            'routing_details' => $payload['routingDetails'] ?? null,
            'active' => true,
        ];
    }
}
