<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TrackPageViewRequest;
use App\Services\VisitorAnalyticsService;
use Illuminate\Http\JsonResponse;

class PageViewController extends Controller
{
    public function store(TrackPageViewRequest $request, VisitorAnalyticsService $analytics): JsonResponse
    {
        $pageView = $analytics->record($request, $request->validated());

        return response()->json([
            'data' => [
                'tracked' => (bool) $pageView,
            ],
        ]);
    }
}
