<?php

namespace App\Console\Commands;

use App\Events\CheckoutAbandonmentReminderDue;
use App\Models\Order;
use Illuminate\Console\Command;

class SendCheckoutAbandonmentEmails extends Command
{
    protected $signature = 'marketing:send-checkout-abandonment';

    protected $description = 'Queue checkout abandonment reminders for unpaid orders.';

    public function handle(): int
    {
        $sent = 0;

        Order::query()
            ->with('buyer')
            ->whereIn('payment_status', ['pending', 'failed'])
            ->where('created_at', '<=', now()->subHours(2))
            ->where('created_at', '>=', now()->subDays(7))
            ->chunkById(100, function ($orders) use (&$sent) {
                foreach ($orders as $order) {
                    if (! $order->buyer || $order->buyer->marketing_unsubscribed_at) {
                        continue;
                    }

                    event(new CheckoutAbandonmentReminderDue($order->buyer, [
                        'order_id' => $order->code,
                        'order_title' => $order->service,
                        'action_url' => '/dashboard/payments',
                        'notification_detail' => 'Complete checkout for order #'.$order->code.'.',
                    ]));
                    $sent++;
                }
            });

        $this->info("Checkout abandonment emails queued: {$sent}");

        return self::SUCCESS;
    }
}
