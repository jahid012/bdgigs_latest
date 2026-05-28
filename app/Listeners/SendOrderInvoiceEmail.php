<?php

namespace App\Listeners;

use App\Events\OrderInvoiceGenerated;
use App\Services\EmailService;
use App\Services\OrderInvoiceService;

class SendOrderInvoiceEmail
{
    public function handle(OrderInvoiceGenerated $event): void
    {
        $invoice = $event->invoice->fresh(['order.buyer']);
        $buyer = $invoice->order?->buyer;

        if (! $buyer) {
            return;
        }

        app(EmailService::class)->queueTemplateEmail('invoice_receipt_email', $buyer, [
            ...app(OrderInvoiceService::class)->payload($invoice),
            'order_id' => $invoice->order?->code,
            'order_title' => $invoice->order?->service,
            'order_amount' => '$'.number_format($invoice->amount_cents / 100, 2),
            'transaction_id' => $invoice->transaction_id,
            'action_url' => '/dashboard/orders/'.$invoice->order?->code,
            'notification_detail' => 'Your receipt '.$invoice->code.' is ready for order #'.$invoice->order?->code.'.',
        ]);
    }
}
