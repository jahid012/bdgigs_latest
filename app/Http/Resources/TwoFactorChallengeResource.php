<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TwoFactorChallengeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'twoFactorRequired' => true,
            'message' => $this->resource['message'] ?? 'Use the two factor login challenge to continue.',
        ];
    }
}
