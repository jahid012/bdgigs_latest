<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Support\MarketplaceNotifier;

class CreateOrderPlacedNotifications
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->fresh(['buyer', 'seller']);
        $notifier = app(MarketplaceNotifier::class);

        if ($order->buyer) {
            $notifier->notify(
                $order->buyer,
                'order_placed',
                'Order placed',
                'Order #'.$order->code.' was created and is waiting for payment confirmation.',
                '/dashboard/orders/'.$order->code,
                ['preferenceKey' => 'orderUpdates', 'orderId' => $order->code],
            );
        }

        if ($order->seller) {
            $notifier->notify(
                $order->seller,
                'new_order_created',
                'New order received',
                'Order #'.$order->code.' was placed for '.$order->service.'.',
                '/dashboard/seller/orders/'.$order->code,
                ['preferenceKey' => 'orderUpdates', 'orderId' => $order->code],
            );
        }
    }
}
