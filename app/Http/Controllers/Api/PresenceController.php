<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePresenceRequest;
use App\Http\Resources\PresenceResource;
use App\Services\PresenceService;

class PresenceController extends Controller
{
    public function join(StorePresenceRequest $request, PresenceService $presence): PresenceResource
    {
        return PresenceResource::make($presence->join(
            $request->user(),
            $request->validated('token'),
        ));
    }

}
