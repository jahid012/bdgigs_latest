<?php

namespace App\Listeners;

use App\Events\OrderPlaced;

class AddOrderPlacedActivity
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->fresh(['buyer']);

        if ($order->activities()->where('type', 'order_placed')->exists()) {
            return;
        }

        $order->activities()->create([
            'actor_id' => $order->buyer_id,
            'type' => 'order_placed',
            'title' => 'Order placed',
            'detail' => ($order->buyer?->name ?: 'Buyer').' placed order #'.$order->code.'.',
        ]);
    }
}
