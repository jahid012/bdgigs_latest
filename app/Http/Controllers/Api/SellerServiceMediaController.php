<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSellerServiceMediaRequest;
use App\Http\Resources\SellerServiceMediaResource;
use App\Services\GigMediaUploadService;
use Illuminate\Http\JsonResponse;

class SellerServiceMediaController extends Controller
{
    public function store(StoreSellerServiceMediaRequest $request, GigMediaUploadService $uploads): JsonResponse
    {
        $payload = $request->validated();

        return SellerServiceMediaResource::make(
            $uploads->store(
                $payload['file'],
                $request->user()->id,
                $payload['type'] ?? null,
            )
        )
            ->response()
            ->setStatusCode(201);
    }
}
