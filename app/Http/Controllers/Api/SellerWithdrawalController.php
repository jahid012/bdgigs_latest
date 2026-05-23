<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWithdrawalRequest;
use App\Http\Resources\WithdrawalRequestResource;
use App\Models\SellerPayoutMethod;
use App\Models\WithdrawalRequest;
use App\Services\SellerWithdrawalRequestService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SellerWithdrawalController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return WithdrawalRequestResource::collection(
            request()->user()->withdrawalRequests()->latest()->get()
        );
    }

    public function store(
        StoreWithdrawalRequest $request,
        SellerWithdrawalRequestService $requests
    ): WithdrawalRequestResource {
        $payload = $request->validated();
        $method = SellerPayoutMethod::query()
            ->whereKey($payload['payoutMethodId'])
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($method, 422, 'Choose one of your payout methods.');

        $withdrawal = $requests->create(
            $request->user(),
            $method,
            (int) round(((float) $payload['amount']) * 100),
            $payload['note'] ?? null,
        );

        return WithdrawalRequestResource::make($withdrawal);
    }

    public function cancel(
        WithdrawalRequest $withdrawal,
        SellerWithdrawalRequestService $requests
    ): WithdrawalRequestResource {
        return WithdrawalRequestResource::make(
            $requests->cancel($withdrawal, request()->user())
        );
    }
}
