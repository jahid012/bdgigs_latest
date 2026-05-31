<?php

namespace App\Listeners;

use App\Events\OrderRefunded;
use App\Models\Admin;
use App\Models\User;

class AddOrderRefundedActivity
{
    public function handle(OrderRefunded $event): void
    {
        $event->order->activities()->create([
            'actor_id' => $event->actor instanceof User ? $event->actor->id : null,
            'actor_admin_id' => $event->actor instanceof Admin ? $event->actor->id : null,
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
