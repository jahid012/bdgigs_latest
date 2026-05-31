<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TrackPageViewRequest;
use App\Http\Resources\ActionResource;
use App\Services\VisitorAnalyticsService;

class PageViewController extends Controller
{
    public function store(TrackPageViewRequest $request, VisitorAnalyticsService $analytics): ActionResource
    {
        $pageView = $analytics->record($request, $request->validated());

        return ActionResource::make(['tracked' => (bool) $pageView]);
    }
}
