<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SellerApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerApplicationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('sellerStatusEvents.actor');

        return response()->json([
            'data' => [
                'status' => $user->seller_status ?: 'not_applied',
                'reason' => $user->seller_status_reason,
                'reviewedAt' => $user->seller_status_reviewed_at?->toISOString(),
                'canSubmit' => in_array($user->seller_status ?: 'not_applied', ['not_applied', 'rejected'], true),
                'history' => $user->sellerStatusEvents
                    ->sortByDesc('created_at')
                    ->map(fn ($event) => [
                        'from' => $event->from_status,
                        'to' => $event->to_status,
                        'reason' => $event->reason,
                        'actorName' => $event->actor?->name,
                        'createdAt' => $event->created_at?->format('M j, Y g:i A'),
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function store(Request $request, SellerApplicationService $applications): JsonResponse
    {
        $payload = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $seller = $applications->submit($request->user(), [
            ...$payload,
            'source' => 'seller_dashboard',
        ]);

        return response()->json([
            'data' => [
                'status' => $seller->seller_status,
                'reason' => $seller->seller_status_reason,
                'reviewedAt' => $seller->seller_status_reviewed_at?->toISOString(),
                'canSubmit' => false,
            ],
        ], 201);
    }
}
