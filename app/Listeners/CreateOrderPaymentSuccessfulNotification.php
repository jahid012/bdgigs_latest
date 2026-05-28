<?php

namespace App\Listeners;

use App\Events\OrderPaymentSuccessful;
use App\Support\MarketplaceNotifier;

class CreateOrderPaymentSuccessfulNotification
{
    public function handle(OrderPaymentSuccessful $event): void
    {
        $order = $event->order->fresh(['buyer', 'seller']);
        $notifier = app(MarketplaceNotifier::class);

        if ($order->buyer) {
            $notifier->notify(
                $order->buyer,
                'payment_successful',
                'Payment successful',
                'Payment for order #'.$order->code.' was processed successfully.',
                '/dashboard/orders/'.$order->code,
                ['preferenceKey' => 'payments', 'orderId' => $order->code],
            );
        }

        if ($order->seller) {
            $notifier->notify(
                $order->seller,
                'order_payment_successful',
                'Order payment confirmed',
                'Payment for order #'.$order->code.' is confirmed and pending earning is recorded.',
                '/dashboard/seller/orders/'.$order->code,
                ['preferenceKey' => 'orderUpdates', 'orderId' => $order->code],
            );
        }
    }
}
