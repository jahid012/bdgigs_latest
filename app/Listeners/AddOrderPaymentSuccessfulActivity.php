<?php

namespace App\Listeners;

use App\Events\OrderPaymentSuccessful;

class AddOrderPaymentSuccessfulActivity
{
    public function handle(OrderPaymentSuccessful $event): void
    {
        $order = $event->order->fresh();

        if ($order->activities()->where('type', 'payment_successful')->exists()) {
            return;
        }

        $order->activities()->create([
            'actor_id' => $order->buyer_id,
            'type' => 'payment_successful',
            'title' => 'Payment successful',
            'detail' => 'Payment was confirmed for order #'.$order->code.'.',
            'metadata' => [
                'transaction_id' => $order->transaction_id,
                'payment_method' => $order->payment_method,
            ],
        ]);
    }
}
