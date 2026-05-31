<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class AdminOrderStatusService
{
    public function __construct(private readonly OrderEventNotificationService $events)
    {
    }

    public function update(Order $order, Admin $actor, string $status): Order
    {
        return DB::transaction(function () use ($order, $actor, $status) {
            $previousStatus = $order->status;

            $order->forceFill([
                'status' => $status,
                'status_class' => $this->statusClass($status),
            ])->save();

            $order->activities()->create([
                'actor_admin_id' => $actor->id,
                'type' => 'admin_status_update',
                'title' => 'Order status updated',
                'detail' => 'Admin changed status from '.$previousStatus.' to '.$status.'.',
            ]);

            collect([$order->buyer_id, $order->seller_id])
                ->filter()
                ->unique()
                ->each(fn (int $recipientId) => event(new OrderStatusUpdated(
                    $order->fresh(['buyer', 'seller']),
                    $recipientId,
                )));

            $this->notifyParticipants($order->fresh(['buyer', 'seller']), $status);

            return $order->fresh(['buyer', 'seller', 'gig', 'activities.actor', 'activities.adminActor']);
        });
    }

    private function statusClass(string $status): string
    {
        return match (strtolower($status)) {
            'delivered', 'completed' => 'status-completed',
            'cancelled', 'canceled' => 'status-cancelled',
            'revision', 'revision requested', 'pending', 'pending requirements' => 'status-delivered',
            default => 'status-progress',
        };
    }

    private function notifyParticipants(Order $order, string $status): void
    {
        $type = 'order_status_'.str($status)->slug('_')->toString();
        $title = 'Order '.$status;

        if ($order->buyer) {
            $this->events->send(
                $order->buyer,
                $type,
                $title,
                'Order #'.$order->code.' is now '.$status.'.',
                '/dashboard/orders/'.$order->code,
                ['orderId' => $order->code, 'status' => $status],
            );
        }

        if ($order->seller) {
            $this->events->send(
                $order->seller,
                $type,
                $title,
                'Order #'.$order->code.' is now '.$status.'.',
                '/dashboard/seller/orders/'.$order->code,
                ['orderId' => $order->code, 'status' => $status],
            );
        }
    }
}
