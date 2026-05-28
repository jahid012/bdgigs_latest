<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModerationReport;
use App\Services\ModerationReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModerationReportController extends Controller
{
    public function store(Request $request, ModerationReportService $reports): JsonResponse
    {
        $payload = $request->validate([
            'type' => ['required', 'string', Rule::in(ModerationReport::TYPES)],
            'targetId' => ['required'],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $report = $reports->create(
            $request->user(),
            $payload['type'],
            $payload['targetId'],
            $payload['reason'],
            $payload['description'] ?? null,
        );

        return response()->json([
            'data' => [
                'code' => $report->code,
                'status' => $report->status,
            ],
        ], 201);
    }
}
