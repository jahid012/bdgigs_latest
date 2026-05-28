<?php

namespace App\Listeners;

use App\Events\OrderPaymentFailed;
use App\Services\EmailService;

class SendOrderPaymentFailedEmail
{
    public function handle(OrderPaymentFailed $event): void
    {
        $order = $event->order->fresh(['buyer']);

        if (! $order->buyer) {
            return;
        }

        app(EmailService::class)->queueTemplateEmail('payment_failed', $order->buyer, [
            'order_id' => $order->code,
            'order_title' => $order->service,
            'order_amount' => '$'.number_format($order->price_cents / 100, 2),
            'action_url' => '/dashboard/payments',
            'notification_detail' => $event->reason ?: 'Your order is still unpaid. Review your payment method and try again.',
        ]);
    }
}
