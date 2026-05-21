<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isSeller = $request->query('role') === 'seller';

        return [
            'id' => '#'.$this->code,
            'service' => $this->service,
            'seller' => $this->seller_name,
            'buyer' => $this->buyer_name,
            'status' => $this->status,
            'statusClass' => $this->status_class,
            'dueDate' => $this->due_date?->format('M j, Y'),
            'price' => '$'.number_format($this->price_cents / 100, 0),
            'earnings' => '$'.number_format($this->earnings_cents / 100, 0),
            'role' => $isSeller ? 'seller' : 'buyer',
        ];
    }
}
