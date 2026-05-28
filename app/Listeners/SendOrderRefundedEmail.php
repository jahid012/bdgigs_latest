<?php

namespace App\Listeners;

use App\Events\OrderRefunded;
use App\Services\EmailService;

class SendOrderRefundedEmail
{
    public function handle(OrderRefunded $event): void
    {
        $order = $event->order->fresh(['buyer', 'seller']);
        $emails = app(EmailService::class);

        if ($order->buyer) {
            $emails->queueTemplateEmail('order_refunded', $order->buyer, [
                'order_id' => $order->code,
                'order_title' => $order->service,
                'order_amount' => '$'.number_format($event->amountCents / 100, 2),
                'transaction_id' => $event->transaction->code,
                'action_url' => '/dashboard/payments',
                'notification_detail' => $event->reason ?: 'Your refund has been credited to your BDGigs wallet.',
            ]);
        }

        if ($order->seller) {
            $emails->queueTemplateEmail('refund_issued_from_dispute', $order->seller, [
                'order_id' => $order->code,
                'order_title' => $order->service,
                'order_amount' => '$'.number_format($event->amountCents / 100, 2),
                'action_url' => '/dashboard/seller/earnings',
                'notification_detail' => $event->reason ?: 'A refund was issued and pending earnings were adjusted.',
            ]);
        }
    }
}
