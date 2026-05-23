<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SavedServiceResource;
use App\Models\Gig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SavedServiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return SavedServiceResource::collection(
            $request->user()
                ->savedServices()
                ->with(['seller', 'media'])
                ->latest('saved_services.created_at')
                ->get()
        );
    }

    public function store(Request $request, Gig $gig): SavedServiceResource
    {
        $request->user()->savedServices()->syncWithoutDetaching([$gig->id]);

        return SavedServiceResource::make($gig->load(['seller', 'media']));
    }

    public function destroy(Request $request, Gig $gig): Response
    {
        $request->user()->savedServices()->detach($gig->id);

        return response()->noContent();
    }
}
