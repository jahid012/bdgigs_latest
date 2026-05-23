<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GigMediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SellerServiceMediaController extends Controller
{
    public function store(Request $request, GigMediaUploadService $uploads): JsonResponse
    {
        $payload = $request->validate([
            'type' => ['nullable', 'string', Rule::in(['image', 'video', 'document'])],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm,pdf', 'max:51200'],
        ]);

        return response()->json([
            'data' => $uploads->store(
                $payload['file'],
                $request->user()->id,
                $payload['type'] ?? null,
            ),
        ], 201);
    }
}
