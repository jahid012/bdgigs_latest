<?php

namespace App\Listeners;

use App\Events\OrderRefunded;
use App\Support\MarketplaceNotifier;

class CreateOrderRefundedNotification
{
    public function handle(OrderRefunded $event): void
    {
        $order = $event->order->fresh(['buyer', 'seller']);
        $notifier = app(MarketplaceNotifier::class);

        if ($order->buyer) {
            $notifier->notify(
                $order->buyer,
                'order_refunded',
                'Order refunded',
                'A refund for order #'.$order->code.' was added to your wallet.',
                '/dashboard/payments',
                ['preferenceKey' => 'payments', 'orderId' => $order->code],
            );
        }

        if ($order->seller) {
            $notifier->notify(
                $order->seller,
                'order_refunded',
                'Order refunded',
                'Order #'.$order->code.' was refunded and pending earnings were adjusted.',
                '/dashboard/seller/earnings',
                ['preferenceKey' => 'payouts', 'orderId' => $order->code],
            );
        }
    }
}
