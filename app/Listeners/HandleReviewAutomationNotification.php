<?php

namespace App\Listeners;

use App\Events\ReviewDeadlineReminder;
use App\Events\ReviewPeriodExpired;
use App\Events\ReviewsVisible;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderEventNotificationService;

class HandleReviewAutomationNotification
{
    public function __construct(private readonly OrderEventNotificationService $events)
    {
    }

    public function handle(object $event): void
    {
        if ($event instanceof ReviewDeadlineReminder) {
            $isSeller = (int) $event->recipient->id === (int) $event->order->seller_id;
            $this->send(
                $event->order,
                $event->recipient,
                $isSeller ? 'review_buyer_reminder' : 'review_deadline_reminder',
                $isSeller ? 'Review the buyer' : 'Review deadline approaching',
                'The review window for order #'.$event->order->code.' closes soon.',
                $isSeller,
            );

            $event->order->activities()->create([
                'type' => 'review_reminder_sent',
                'title' => 'Review reminder sent',
                'detail' => 'A review reminder was sent to '.$event->recipient->name.'.',
                'metadata' => ['reminder_key' => $event->reminderKey],
            ]);
        }

        if ($event instanceof ReviewPeriodExpired) {
            $order = $event->order;
            $order->buyer && $this->send($order, $order->buyer, 'review_period_expired', 'Review period expired', 'The review window for order #'.$order->code.' has closed.');
            $order->seller && $this->send($order, $order->seller, 'review_period_expired', 'Review period expired', 'The review window for order #'.$order->code.' has closed.', true);
            $order->activities()->create([
                'type' => 'review_period_expired',
                'title' => 'Review period expired',
                'detail' => 'The 15 day mutual review period has closed.',
            ]);
        }

        if ($event instanceof ReviewsVisible) {
            $order = $event->order;
            $order->buyer && $this->send($order, $order->buyer, 'reviews_are_now_visible', 'Reviews are now visible', 'Both reviews are visible for order #'.$order->code.'.');
            $order->seller && $this->send($order, $order->seller, 'reviews_are_now_visible', 'Reviews are now visible', 'Both reviews are visible for order #'.$order->code.'.', true);
            $order->activities()->create([
                'type' => 'reviews_visible',
                'title' => 'Reviews visible',
                'detail' => 'Both buyer and seller reviews are now visible.',
            ]);
        }
    }

    private function send(Order $order, User $recipient, string $template, string $title, string $detail, bool $sellerPath = false): void
    {
        $this->events->send(
            $recipient,
            $template,
            $title,
            $detail,
            ($sellerPath ? '/dashboard/seller/orders/' : '/dashboard/orders/').$order->code,
            [
                'preferenceKey' => 'ratingReminders',
                'orderId' => $order->code,
                'order_title' => $order->service,
                'review_deadline' => $order->review_period_expires_at?->format('M j, Y'),
                'emailTemplate' => $template,
            ],
        );
    }
}
