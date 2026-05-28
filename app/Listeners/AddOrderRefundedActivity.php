<?php

namespace App\Listeners;

use App\Events\OrderRefunded;

class AddOrderRefundedActivity
{
    public function handle(OrderRefunded $event): void
    {
        $event->order->activities()->create([
            'actor_id' => $event->actor?->id,
            'type' => 'order_refunded',
            'title' => 'Order refunded',
            'detail' => 'Refunded $'.number_format($event->amountCents / 100, 2).'. '.($event->reason ?: ''),
            'metadata' => [
                'transaction_id' => $event->transaction->code,
                'amount_cents' => $event->amountCents,
            ],
        ]);
    }
}
