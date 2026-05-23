<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class WithdrawalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $approved = $this->approved_amount_cents ?: $this->amount_cents;

        return [
            'id' => $this->code,
            'code' => $this->code,
            'amount' => '$'.number_format($this->amount_cents / 100, 0),
            'amountValue' => round($this->amount_cents / 100, 2),
            'approvedAmount' => '$'.number_format($approved / 100, 0),
            'currency' => $this->currency,
            'status' => Str::of($this->status)->replace('_', ' ')->title()->toString(),
            'statusKey' => $this->status,
            'sellerNote' => $this->seller_note,
            'reviewNote' => $this->review_note,
            'paymentReference' => $this->payment_reference,
            'payout' => $this->payout_snapshot,
            'canCancel' => in_array($this->status, ['pending', 'under_review'], true),
            'requestedAt' => $this->created_at?->toISOString(),
            'requestedDate' => $this->created_at?->format('M j, Y'),
            'reviewedAt' => $this->reviewed_at?->toISOString(),
            'paidAt' => $this->paid_at?->toISOString(),
        ];
    }
}
