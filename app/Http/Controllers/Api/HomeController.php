<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HomePageDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function bootstrap(Request $request, HomePageDataService $home): JsonResponse
    {
        return response()->json([
            'data' => $home->bootstrap($request),
        ]);
    }
}
