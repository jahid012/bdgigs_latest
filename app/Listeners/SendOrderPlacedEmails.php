<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Services\EmailService;

class SendOrderPlacedEmails
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->fresh(['buyer', 'seller']);
        $emails = app(EmailService::class);

        if ($order->buyer) {
            $emails->queueTemplateEmail('order_placed_successfully', $order->buyer, $this->payload($order, '/dashboard/orders/'.$order->code));
        }

        if ($order->seller) {
            $emails->queueTemplateEmail('new_order_created', $order->seller, $this->payload($order, '/dashboard/seller/orders/'.$order->code));
        }
    }

    private function payload($order, string $actionUrl): array
    {
        return [
            'order_id' => $order->code,
            'order_title' => $order->service,
            'order_amount' => '$'.number_format($order->price_cents / 100, 2),
            'action_url' => $actionUrl,
            'order_url' => $actionUrl,
            'notification_detail' => 'Order #'.$order->code.' is now in your marketplace dashboard.',
        ];
    }
}
