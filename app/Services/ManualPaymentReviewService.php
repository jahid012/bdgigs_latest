<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\ManualPaymentSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ManualPaymentReviewService
{
    public function review(ManualPaymentSubmission $submission, User $reviewer, string $decision, ?string $note): ManualPaymentSubmission
    {
        return DB::transaction(function () use ($submission, $reviewer, $decision, $note) {
            $approved = $decision === 'approve';
            $order = $submission->order;

            $submission->forceFill([
                'status' => $approved ? 'approved' : 'rejected',
                'review_note' => $note,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $order->forceFill([
                'status' => $approved ? 'Pending Requirements' : 'Payment Rejected',
                'status_class' => $approved ? 'status-delivered' : 'status-progress',
            ])->save();

            $order->activities()->create([
                'actor_id' => $reviewer->id,
                'type' => 'manual_payment_review',
                'title' => $approved ? 'Manual payment approved' : 'Manual payment rejected',
                'detail' => $note ?: ($approved
                    ? 'Admin approved the submitted payment reference.'
                    : 'Admin rejected the submitted payment reference.'),
            ]);

            collect([$order->buyer_id, $order->seller_id])
                ->filter()
                ->unique()
                ->each(fn (int $recipientId) => event(new OrderStatusUpdated(
                    $order->fresh(['buyer', 'seller']),
                    $recipientId,
                )));

            return $submission->fresh(['order', 'buyer', 'method', 'reviewer']);
        });
    }
}
