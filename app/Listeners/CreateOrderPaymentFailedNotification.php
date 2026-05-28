<?php

namespace App\Listeners;

use App\Events\OrderPaymentFailed;
use App\Support\MarketplaceNotifier;

class CreateOrderPaymentFailedNotification
{
    public function handle(OrderPaymentFailed $event): void
    {
        $order = $event->order->fresh(['buyer']);

        if (! $order->buyer) {
            return;
        }

        app(MarketplaceNotifier::class)->notify(
            $order->buyer,
            'payment_failed',
            'Payment failed',
            'Payment for order #'.$order->code.' could not be completed. '.$event->reason,
            '/dashboard/payments',
            ['preferenceKey' => 'payments', 'orderId' => $order->code],
        );
    }
}
