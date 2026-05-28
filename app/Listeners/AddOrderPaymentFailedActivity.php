<?php

namespace App\Listeners;

use App\Events\OrderPaymentFailed;

class AddOrderPaymentFailedActivity
{
    public function handle(OrderPaymentFailed $event): void
    {
        $event->order->activities()->create([
            'actor_id' => $event->order->buyer_id,
            'type' => 'payment_failed',
            'title' => 'Payment failed',
            'detail' => $event->reason ?: 'Payment could not be completed.',
        ]);
    }
}
