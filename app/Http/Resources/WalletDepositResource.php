<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletDepositResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $transaction = $this->resource['transaction'];

        return [
            'transaction' => [
                'id' => $transaction->code,
                'amount' => '$'.number_format($transaction->amount_cents / 100, 2),
                'status' => $transaction->status,
            ],
            'summary' => $this->resource['summary'],
        ];
    }
}
