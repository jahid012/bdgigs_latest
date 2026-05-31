<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreModerationReportRequest;
use App\Http\Resources\ModerationReportResource;
use App\Services\ModerationReportService;
use Illuminate\Http\JsonResponse;

class ModerationReportController extends Controller
{
    public function store(StoreModerationReportRequest $request, ModerationReportService $reports): JsonResponse
    {
        $payload = $request->validated();

        $report = $reports->create(
            $request->user(),
            $payload['type'],
            $payload['targetId'],
            $payload['reason'],
            $payload['description'] ?? null,
        );

        return ModerationReportResource::make($report)
            ->response()
            ->setStatusCode(201);
    }
}
