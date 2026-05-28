<?php

namespace App\Listeners;

use App\Events\OrderCancellationAccepted;
use App\Events\OrderCancellationRejected;
use App\Events\OrderCancellationRequested;
use App\Events\OrderCancelled;
use App\Events\OrderDeadlineReminder;
use App\Events\OrderOverdueAlert;
use App\Events\OrderRequirementsPendingReminder;
use App\Events\RevisionDelivered;
use App\Events\SellerStartedWorking;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderEventNotificationService;

class HandleOrderAutomationNotification
{
    public function __construct(private readonly OrderEventNotificationService $events)
    {
    }

    public function handle(object $event): void
    {
        if ($event instanceof OrderRequirementsPendingReminder && $event->order->buyer) {
            $this->send($event->order, $event->order->buyer, 'requirements_pending', 'Requirements needed', 'Submit the buyer requirements so the seller can start order #'.$event->order->code.'.', 'requirements_pending');
        }

        if ($event instanceof SellerStartedWorking && $event->order->buyer) {
            $this->send($event->order, $event->order->buyer, 'seller_started_working', 'Seller started working', $event->seller->name.' started work on order #'.$event->order->code.'.', 'seller_started_working');
        }

        if ($event instanceof OrderDeadlineReminder) {
            $detail = 'Order #'.$event->order->code.' is due in '.$event->label.'.';
            $event->order->buyer && $this->send($event->order, $event->order->buyer, 'order_deadline_reminder', 'Order deadline approaching', $detail, 'order_deadline_reminder');
            $event->order->seller && $this->send($event->order, $event->order->seller, 'order_deadline_reminder', 'Order deadline approaching', $detail, 'order_deadline_reminder', true);
        }

        if ($event instanceof OrderOverdueAlert) {
            $detail = 'Order #'.$event->order->code.' has passed its delivery deadline.';
            $event->order->buyer && $this->send($event->order, $event->order->buyer, 'order_overdue_alert', 'Order overdue', $detail, 'order_overdue_alert');
            $event->order->seller && $this->send($event->order, $event->order->seller, 'order_overdue_alert', 'Order overdue', $detail, 'order_overdue_alert', true);
        }

        if ($event instanceof RevisionDelivered && $event->order->buyer) {
            $this->send($event->order, $event->order->buyer, 'revision_delivered', 'Revision delivered', 'A revision delivery is ready for order #'.$event->order->code.'.', 'revision_delivered');
        }

        if ($event instanceof OrderCancellationRequested) {
            $cancellation = $event->cancellation->loadMissing('order.buyer', 'order.seller', 'requester');
            $recipient = (int) $cancellation->requester_id === (int) $cancellation->order->buyer_id
                ? $cancellation->order->seller
                : $cancellation->order->buyer;

            $recipient && $this->send($cancellation->order, $recipient, 'order_cancellation_requested', 'Cancellation requested', ($cancellation->requester?->name ?: 'A participant').' requested cancellation for order #'.$cancellation->order->code.'.', 'order_cancellation_requested', (int) $recipient->id === (int) $cancellation->order->seller_id);
        }

        if ($event instanceof OrderCancellationAccepted) {
            $this->notifyCancellation($event->cancellation, 'order_cancellation_accepted', 'Cancellation accepted', 'The cancellation request was accepted.');
        }

        if ($event instanceof OrderCancellationRejected) {
            $this->notifyCancellation($event->cancellation, 'order_cancellation_rejected', 'Cancellation rejected', 'The cancellation request was rejected.');
        }

        if ($event instanceof OrderCancelled) {
            $event->order->buyer && $this->send($event->order, $event->order->buyer, 'order_cancelled', 'Order cancelled', 'Order #'.$event->order->code.' was cancelled.', 'order_cancelled');
            $event->order->seller && $this->send($event->order, $event->order->seller, 'order_cancelled', 'Order cancelled', 'Order #'.$event->order->code.' was cancelled.', 'order_cancelled', true);
        }
    }

    private function notifyCancellation($cancellation, string $type, string $title, string $detail): void
    {
        $cancellation->loadMissing('order.buyer', 'order.seller');
        $order = $cancellation->order;

        $order->buyer && $this->send($order, $order->buyer, $type, $title, $detail.' Order #'.$order->code.'.', $type);
        $order->seller && $this->send($order, $order->seller, $type, $title, $detail.' Order #'.$order->code.'.', $type, true);
    }

    private function send(Order $order, User $recipient, string $type, string $title, string $detail, string $template, bool $sellerPath = false): void
    {
        $this->events->send(
            $recipient,
            $type,
            $title,
            $detail,
            ($sellerPath ? '/dashboard/seller/orders/' : '/dashboard/orders/').$order->code,
            [
                'orderId' => $order->code,
                'order_title' => $order->service,
                'deadline' => $order->due_date?->format('M j, Y'),
                'emailTemplate' => $template,
            ],
        );
    }
}
