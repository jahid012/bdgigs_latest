<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePushSubscriptionRequest;
use App\Http\Resources\ActionResource;
use App\Http\Resources\PushSubscriptionResource;
use App\Services\PushSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request, PushSubscriptionService $subscriptions): JsonResponse
    {
        return PushSubscriptionResource::make(
            $subscriptions->store($request->user(), $request->validated())
        )
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Request $request, string $token, PushSubscriptionService $subscriptions): ActionResource
    {
        $subscriptions->revoke($request->user(), $token);

        return ActionResource::make(['revoked' => true]);
    }
}
