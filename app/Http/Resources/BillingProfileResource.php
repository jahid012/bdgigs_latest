<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'fullName' => $this->full_name ?: $this->user?->name,
            'company' => $this->company ?: '',
            'country' => $this->country ?: ($this->user?->country ?: ''),
            'state' => $this->state ?: '',
            'address' => $this->address ?: '',
            'city' => $this->city ?: '',
            'postalCode' => $this->postal_code ?: '',
            'taxId' => $this->tax_id ?: '',
        ];
    }
}
