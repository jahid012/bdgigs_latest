<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWalletDepositRequest;
use App\Http\Requests\Api\UpdateBillingProfileRequest;
use App\Http\Resources\BillingProfileResource;
use App\Http\Resources\FinanceSummaryResource;
use App\Http\Resources\WalletDepositResource;
use App\Services\FinanceSummaryService;
use App\Services\UserWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function show(Request $request): BillingProfileResource
    {
        $profile = $request->user()->billingProfile()->firstOrCreate([], [
            'full_name' => $request->user()->name,
            'country' => $request->user()->country,
        ]);

        return BillingProfileResource::make($profile->loadMissing('user'));
    }

    public function update(UpdateBillingProfileRequest $request): BillingProfileResource
    {
        $payload = $request->validated();

        $profile = $request->user()->billingProfile()->updateOrCreate([], [
            'full_name' => $payload['fullName'] ?? null,
            'company' => $payload['company'] ?? null,
            'country' => $payload['country'] ?? null,
            'state' => $payload['state'] ?? null,
            'address' => $payload['address'] ?? null,
            'city' => $payload['city'] ?? null,
            'postal_code' => $payload['postalCode'] ?? null,
            'tax_id' => $payload['taxId'] ?? null,
        ]);

        return BillingProfileResource::make($profile->loadMissing('user'));
    }

    public function buyerSummary(Request $request, FinanceSummaryService $finance): FinanceSummaryResource
    {
        return FinanceSummaryResource::make($finance->buyer($request->user()));
    }

    public function addBalance(
        StoreWalletDepositRequest $request,
        UserWalletService $wallets,
        FinanceSummaryService $finance
    ): JsonResponse {
        $payload = $request->validated();

        $transaction = $wallets->deposit(
            $request->user(),
            (int) round(((float) $payload['amount']) * 100),
            $payload['method'] ?? 'manual_card',
            $payload['note'] ?? null,
        );

        return WalletDepositResource::make([
            'transaction' => $transaction,
            'summary' => $finance->buyer($request->user()),
        ])
            ->response()
            ->setStatusCode(201);
    }

    public function sellerEarnings(Request $request, FinanceSummaryService $finance): FinanceSummaryResource
    {
        return FinanceSummaryResource::make($finance->seller($request->user()));
    }
}
