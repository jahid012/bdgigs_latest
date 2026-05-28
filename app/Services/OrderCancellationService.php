<?php

namespace App\Services;

use App\Events\OrderCancellationAccepted;
use App\Events\OrderCancellationRejected;
use App\Events\OrderCancellationRequested;
use App\Events\OrderCancelled;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCancellationService
{
    public function __construct(private readonly OrderPaymentLifecycleService $payments)
    {
    }

    public function request(Order $order, User $requester, string $reason): OrderCancellation
    {
        $this->authorizeParticipant($order, $requester);
        $this->ensureCancellable($order);

        if ($order->latestCancellation?->status === 'cancellation_requested') {
            throw ValidationException::withMessages([
                'reason' => 'A cancellation request is already pending.',
            ]);
        }

        return DB::transaction(function () use ($order, $requester, $reason) {
            $cancellation = $order->cancellations()->create([
                'requester_id' => $requester->id,
                'status' => 'cancellation_requested',
                'reason' => $reason,
                'refund_status' => 'pending',
                'requested_at' => now(),
            ]);

            $order->forceFill([
                'cancellation_status' => 'cancellation_requested',
            ])->save();

            DB::afterCommit(fn () => event(new OrderCancellationRequested($cancellation->fresh(['order.buyer', 'order.seller', 'requester']))));

            return $cancellation->fresh(['order', 'requester']);
        });
    }

    public function accept(Order $order, User $responder, ?string $note = null): OrderCancellation
    {
        $cancellation = $this->pendingCancellation($order);
        $this->authorizeResponder($order, $cancellation, $responder);

        $cancellation = DB::transaction(function () use ($order, $cancellation, $responder, $note) {
            $cancellation->forceFill([
                'responder_id' => $responder->id,
                'status' => 'cancelled',
                'response_note' => $note,
                'responded_at' => now(),
                'cancelled_at' => now(),
                'refund_status' => $order->payment_status === 'paid' ? 'pending' : 'processed',
            ])->save();

            $order->forceFill([
                'cancellation_status' => 'cancelled',
                'refund_status' => $cancellation->refund_status,
            ])->save();

            return $cancellation->fresh(['order.buyer', 'order.seller', 'requester', 'responder']);
        });

        try {
            if ($order->fresh()->payment_status === 'paid') {
                $this->payments->refund($order->fresh(['buyer', 'seller']), $responder, null, $note ?: $cancellation->reason);
            } else {
                $order->forceFill([
                    'status' => 'Cancelled',
                    'status_class' => 'status-cancelled',
                    'cancelled_at' => now(),
                    'refund_status' => 'processed',
                ])->save();
            }

            $cancellation->forceFill(['refund_status' => 'processed'])->save();
            event(new OrderCancellationAccepted($cancellation->fresh(['order.buyer', 'order.seller', 'requester', 'responder'])));
            event(new OrderCancelled($order->fresh(['buyer', 'seller']), $responder, $note ?: $cancellation->reason));
        } catch (\Throwable $exception) {
            $cancellation->forceFill(['refund_status' => 'failed'])->save();
            $order->forceFill(['refund_status' => 'failed'])->save();

            throw $exception;
        }

        return $cancellation->fresh(['order', 'requester', 'responder']);
    }

    public function reject(Order $order, User $responder, ?string $note = null): OrderCancellation
    {
        $cancellation = $this->pendingCancellation($order);
        $this->authorizeResponder($order, $cancellation, $responder);

        return DB::transaction(function () use ($order, $cancellation, $responder, $note) {
            $cancellation->forceFill([
                'responder_id' => $responder->id,
                'status' => 'cancellation_rejected',
                'response_note' => $note,
                'responded_at' => now(),
                'refund_status' => null,
            ])->save();

            $order->forceFill([
                'cancellation_status' => 'cancellation_rejected',
            ])->save();

            DB::afterCommit(fn () => event(new OrderCancellationRejected($cancellation->fresh(['order.buyer', 'order.seller', 'requester', 'responder']))));

            return $cancellation->fresh(['order', 'requester', 'responder']);
        });
    }

    public function adminCancel(Order $order, ?User $actor, string $reason): OrderCancellation
    {
        $this->ensureCancellable($order);

        $cancellation = $order->cancellations()->create([
            'requester_id' => $actor?->id,
            'responder_id' => $actor?->id,
            'status' => 'cancelled',
            'reason' => $reason,
            'refund_status' => $order->payment_status === 'paid' ? 'pending' : 'processed',
            'requested_at' => now(),
            'responded_at' => now(),
            'cancelled_at' => now(),
            'metadata' => ['source' => 'admin'],
        ]);

        if ($order->payment_status === 'paid') {
            $this->payments->refund($order->fresh(['buyer', 'seller']), $actor, null, $reason);
            $order->fresh()->forceFill([
                'cancellation_status' => 'cancelled',
                'refund_status' => 'processed',
            ])->save();
        } else {
            $order->forceFill([
                'status' => 'Cancelled',
                'status_class' => 'status-cancelled',
                'cancelled_at' => now(),
                'cancellation_status' => 'cancelled',
                'refund_status' => 'processed',
            ])->save();
        }

        $cancellation->forceFill(['refund_status' => 'processed'])->save();
        event(new OrderCancelled($order->fresh(['buyer', 'seller']), $actor, $reason));

        return $cancellation->fresh(['order', 'requester', 'responder']);
    }

    private function pendingCancellation(Order $order): OrderCancellation
    {
        $cancellation = $order->cancellations()
            ->where('status', 'cancellation_requested')
            ->latest()
            ->first();

        if (! $cancellation) {
            throw ValidationException::withMessages([
                'decision' => 'There is no pending cancellation request for this order.',
            ]);
        }

        return $cancellation;
    }

    private function authorizeParticipant(Order $order, User $user): void
    {
        if (in_array($user->id, [$order->buyer_id, $order->seller_id], true) || $user->can('orders.manage')) {
            return;
        }

        throw new AuthorizationException('You cannot manage cancellation for this order.');
    }

    private function authorizeResponder(Order $order, OrderCancellation $cancellation, User $user): void
    {
        if ($user->can('orders.manage')) {
            return;
        }

        $this->authorizeParticipant($order, $user);

        if ((int) $cancellation->requester_id === (int) $user->id) {
            throw ValidationException::withMessages([
                'decision' => 'The other party must respond to this cancellation request.',
            ]);
        }
    }

    private function ensureCancellable(Order $order): void
    {
        if (in_array(strtolower((string) $order->status), ['completed', 'cancelled', 'canceled'], true)) {
            throw ValidationException::withMessages([
                'reason' => 'This order is already closed.',
            ]);
        }
    }
}
