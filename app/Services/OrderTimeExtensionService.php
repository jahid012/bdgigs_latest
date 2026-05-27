<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTimeExtensionRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderTimeExtensionService
{
    public function __construct(private readonly OrderEventNotificationService $events)
    {
    }

    public function request(Order $order, User $seller, array $payload): OrderTimeExtensionRequest
    {
        if ((int) $order->seller_id !== (int) $seller->id) {
            throw new AuthorizationException('Only the seller can request more delivery time.');
        }

        if ($this->hasPendingRequest($order)) {
            throw ValidationException::withMessages([
                'order' => 'This order already has a pending time extension request.',
            ]);
        }

        $days = (int) $payload['days'];
        $currentDueDate = $order->due_date?->copy() ?? now();
        $requestedDueDate = $currentDueDate->copy()->addDays($days);

        return DB::transaction(function () use ($order, $seller, $payload, $days, $currentDueDate, $requestedDueDate) {
            $extension = $order->timeExtensionRequests()->create([
                'requested_by_id' => $seller->id,
                'days_requested' => $days,
                'original_due_date' => $order->due_date,
                'requested_due_date' => $requestedDueDate->toDateString(),
                'reason' => trim($payload['reason']),
                'status' => 'pending',
            ]);

            $order->activities()->create([
                'actor_id' => $seller->id,
                'type' => 'time_extension_requested',
                'title' => 'Time extension requested',
                'detail' => sprintf(
                    '%s requested %d extra day%s. New requested delivery date: %s. Reason: %s',
                    $seller->name,
                    $days,
                    $days === 1 ? '' : 's',
                    $requestedDueDate->format('M j, Y'),
                    trim($payload['reason']),
                ),
                'metadata' => [
                    'extension_id' => $extension->id,
                    'original_due_date' => $currentDueDate->toDateString(),
                    'requested_due_date' => $requestedDueDate->toDateString(),
                ],
            ]);

            if ($order->buyer) {
                $this->events->send(
                    $order->buyer,
                    'order_time_extension_requested',
                    'Time extension requested',
                    $seller->name.' requested more time for order #'.$order->code.'.',
                    '/dashboard/orders/'.$order->code,
                    ['orderId' => $order->code, 'extensionId' => $extension->id],
                );
            }

            return $extension->load(['requester', 'reviewer']);
        });
    }

    public function decide(
        Order $order,
        OrderTimeExtensionRequest $extension,
        User $actor,
        string $decision
    ): OrderTimeExtensionRequest {
        if ((int) $extension->order_id !== (int) $order->id) {
            throw ValidationException::withMessages([
                'extension' => 'This time extension request does not belong to the order.',
            ]);
        }

        if ($extension->status !== 'pending') {
            throw ValidationException::withMessages([
                'extension' => 'This time extension request has already been reviewed.',
            ]);
        }

        $isBuyer = (int) $order->buyer_id === (int) $actor->id;
        $isAdmin = $actor->can('orders.manage');

        if (! $isBuyer && ! $isAdmin) {
            throw new AuthorizationException('Only the buyer can review this time extension request.');
        }

        $status = str_starts_with($decision, 'accept') ? 'accepted' : 'rejected';

        return DB::transaction(function () use ($order, $extension, $actor, $status) {
            $extension->forceFill([
                'reviewed_by_id' => $actor->id,
                'status' => $status,
                'decided_at' => now(),
            ])->save();

            if ($status === 'accepted' && $extension->requested_due_date) {
                $order->forceFill([
                    'due_date' => $extension->requested_due_date->toDateString(),
                ])->save();
            }

            $order->activities()->create([
                'actor_id' => $actor->id,
                'type' => 'time_extension_'.$status,
                'title' => $status === 'accepted'
                    ? 'Time extension accepted'
                    : 'Time extension rejected',
                'detail' => $status === 'accepted'
                    ? sprintf(
                        '%s accepted the time extension. The delivery date is now %s.',
                        $actor->name,
                        $extension->requested_due_date?->format('M j, Y') ?? 'updated',
                    )
                    : $actor->name.' rejected the time extension request.',
                'metadata' => [
                    'extension_id' => $extension->id,
                    'requested_due_date' => $extension->requested_due_date?->toDateString(),
                ],
            ]);

            if ($order->seller) {
                $this->events->send(
                    $order->seller,
                    'order_time_extension_'.$status,
                    $status === 'accepted'
                        ? 'Time extension accepted'
                        : 'Time extension rejected',
                    'Order #'.$order->code.' time extension was '.$status.'.',
                    '/dashboard/seller/orders/'.$order->code,
                    ['orderId' => $order->code, 'extensionId' => $extension->id],
                );
            }

            return $extension->refresh()->load(['requester', 'reviewer']);
        });
    }

    private function hasPendingRequest(Order $order): bool
    {
        return $order->timeExtensionRequests()
            ->where('status', 'pending')
            ->exists();
    }
}
