<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class SellerPayoutMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'typeLabel' => Str::of($this->type)->replace('_', ' ')->title()->toString(),
            'label' => $this->label,
            'accountHolder' => $this->account_holder,
            'accountNumber' => $this->account_number,
            'routingDetails' => $this->routing_details,
            'active' => $this->active,
            'lastUsedAt' => $this->last_used_at?->toISOString(),
        ];
    }
}
