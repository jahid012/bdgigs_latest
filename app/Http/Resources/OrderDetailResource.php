<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isSeller = $request->query('role') === 'seller';
        $counterparty = $isSeller ? $this->buyer : $this->seller;
        $counterpartyName = $isSeller ? $this->buyer_name : $this->seller_name;

        return [
            'id' => '#'.$this->code,
            'orderNumber' => $this->code,
            'serviceTitle' => $this->service,
            'serviceSummary' => $this->gig?->title ?: $this->service,
            'serviceImage' => $this->gig?->image,
            'orderedBy' => $this->buyer_name ?: 'Buyer',
            'dateOrdered' => $this->created_at?->format('M j, Y, g:i A'),
            'deliveryDate' => $this->due_date?->format('M j, Y'),
            'totalPrice' => '$'.number_format($this->price_cents / 100, 0),
            'earnings' => '$'.number_format($this->earnings_cents / 100, 0),
            'status' => $this->status,
            'statusClass' => $this->status_class,
            'counterpartyName' => $counterpartyName ?: 'Member',
            'counterpartyHandle' => $counterparty?->username ? '@'.$counterparty->username : '',
            'counterpartyInitials' => initialsFromOrderName($counterpartyName ?: 'Member'),
            'itemSummary' => $this->metadata['itemSummary'] ?? 'Marketplace order',
            'quantity' => (string) ($this->metadata['quantity'] ?? 1),
            'duration' => $this->metadata['duration'] ?? ($this->gig?->delivery_days ? $this->gig->delivery_days.' days' : ''),
            'revisions' => $this->metadata['revisions'] ?? '',
            'requirements' => $this->metadata['requirements'] ?? [],
            'activity' => $this->relationLoaded('activities') && $this->activities->isNotEmpty()
                ? $this->activities
                    ->sortByDesc('created_at')
                    ->map(fn ($activity) => [
                        'title' => $activity->title,
                        'detail' => $activity->detail,
                        'time' => $activity->created_at?->format('M j, Y g:i A'),
                    ])
                    ->values()
                    ->all()
                : ($this->metadata['activity'] ?? []),
            'paymentReviewStatus' => $this->manualPaymentSubmission?->status,
        ];
    }
}

function initialsFromOrderName(string $name): string
{
    return collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_substr($part, 0, 1))
        ->implode('');
}
