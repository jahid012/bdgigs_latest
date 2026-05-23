<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminOrderStatusService
{
    public function update(Order $order, User $actor, string $status): Order
    {
        return DB::transaction(function () use ($order, $actor, $status) {
            $previousStatus = $order->status;

            $order->forceFill([
                'status' => $status,
                'status_class' => $this->statusClass($status),
            ])->save();

            $order->activities()->create([
                'actor_id' => $actor->id,
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

            return $order->fresh(['buyer', 'seller', 'gig', 'activities.actor']);
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
}
