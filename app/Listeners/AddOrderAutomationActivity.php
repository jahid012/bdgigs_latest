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
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;

class AddOrderAutomationActivity
{
    public function handle(object $event): void
    {
        $payload = $this->payload($event);

        if (! $payload) {
            return;
        }

        [$order, $type, $title, $detail, $actorId, $metadata, $actorAdminId] = array_pad($payload, 7, null);

        $order->activities()->create([
            'actor_id' => $actorId,
            'actor_admin_id' => $actorAdminId,
            'type' => $type,
            'title' => $title,
            'detail' => $detail,
            'metadata' => $metadata,
        ]);
    }

    private function payload(object $event): ?array
    {
        if ($event instanceof OrderRequirementsPendingReminder) {
            return [
                $event->order,
                'requirements_reminder_sent',
                'Requirements reminder sent',
                'A reminder was sent to the buyer to submit required order requirements.',
                null,
                ['reminder_key' => $event->reminderKey],
            ];
        }

        if ($event instanceof SellerStartedWorking) {
            return [
                $event->order,
                'seller_started_working',
                'Seller started working',
                $event->seller->name.' started work on this order.',
                $event->seller->id,
                [],
            ];
        }

        if ($event instanceof OrderDeadlineReminder) {
            return [
                $event->order,
                'deadline_reminder_sent',
                'Deadline reminder sent',
                'A '.$event->label.' delivery deadline reminder was sent.',
                null,
                ['reminder_key' => $event->reminderKey],
            ];
        }

        if ($event instanceof OrderOverdueAlert) {
            return [
                $event->order,
                'order_marked_overdue',
                'Order marked overdue',
                'The delivery deadline passed and the order was marked overdue.',
                null,
                [],
            ];
        }

        if ($event instanceof RevisionDelivered) {
            return [
                $event->order,
                'revision_delivered',
                'Revision delivered',
                $event->order->seller_name.' submitted a revision delivery.',
                $event->order->seller_id,
                ['delivery_id' => $event->delivery['id'] ?? null],
            ];
        }

        if ($event instanceof OrderCancellationRequested) {
            $cancellation = $event->cancellation->loadMissing('order', 'requester');

            return [
                $cancellation->order,
                'cancellation_requested',
                'Cancellation requested',
                ($cancellation->requester?->name ?: 'A participant').' requested order cancellation.',
                $cancellation->requester_id,
                ['cancellation_id' => $cancellation->id],
            ];
        }

        if ($event instanceof OrderCancellationAccepted) {
            $cancellation = $event->cancellation->loadMissing('order', 'responder');

            return [
                $cancellation->order,
                'cancellation_accepted',
                'Cancellation accepted',
                ($cancellation->responder?->name ?: 'A participant').' accepted the cancellation request.',
                $cancellation->responder_id,
                ['cancellation_id' => $cancellation->id],
            ];
        }

        if ($event instanceof OrderCancellationRejected) {
            $cancellation = $event->cancellation->loadMissing('order', 'responder');

            return [
                $cancellation->order,
                'cancellation_rejected',
                'Cancellation rejected',
                ($cancellation->responder?->name ?: 'A participant').' rejected the cancellation request.',
                $cancellation->responder_id,
                ['cancellation_id' => $cancellation->id],
            ];
        }

        if ($event instanceof OrderCancelled) {
            $actor = $event->actor;

            return [
                $event->order instanceof Order ? $event->order : $event->order->fresh(),
                'order_cancelled',
                'Order cancelled',
                $event->reason ?: 'The order was cancelled and refund rules were applied.',
                $actor instanceof User ? $actor->id : null,
                [],
                $actor instanceof Admin ? $actor->id : null,
            ];
        }

        return null;
    }
}
