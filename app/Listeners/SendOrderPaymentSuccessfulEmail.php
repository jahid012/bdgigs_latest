<?php

namespace App\Listeners;

use App\Events\OrderPaymentSuccessful;
use App\Services\EmailService;

class SendOrderPaymentSuccessfulEmail
{
    public function handle(OrderPaymentSuccessful $event): void
    {
        $order = $event->order->fresh(['buyer', 'seller']);
        $emails = app(EmailService::class);

        if ($order->buyer) {
            $emails->queueTemplateEmail('payment_successful', $order->buyer, $this->payload($order, '/dashboard/orders/'.$order->code));
        }

        if ($order->seller) {
            $emails->queueTemplateEmail('earnings_added_or_pending', $order->seller, $this->payload($order, '/dashboard/seller/earnings'));
        }
    }

    private function payload($order, string $actionUrl): array
    {
        return [
            'order_id' => $order->code,
            'order_title' => $order->service,
            'order_amount' => '$'.number_format($order->price_cents / 100, 2),
            'transaction_id' => $order->transaction_id,
            'action_url' => $actionUrl,
            'order_url' => $actionUrl,
            'notification_detail' => 'Payment method: '.($order->payment_method ?: 'manual').'. Transaction: '.($order->transaction_id ?: 'pending reference').'.',
        ];
    }
}
