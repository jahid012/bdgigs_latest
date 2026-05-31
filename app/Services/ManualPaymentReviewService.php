<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Admin;
use App\Models\ManualPaymentSubmission;
use Illuminate\Support\Facades\DB;

class ManualPaymentReviewService
{
    public function __construct(private readonly OrderPaymentLifecycleService $payments)
    {
    }

    public function review(ManualPaymentSubmission $submission, Admin $reviewer, string $decision, ?string $note): ManualPaymentSubmission
    {
        return DB::transaction(function () use ($submission, $reviewer, $decision, $note) {
            $approved = $decision === 'approve';
            $order = $submission->order;

            $submission->forceFill([
                'status' => $approved ? 'approved' : 'rejected',
                'review_note' => $note,
                'reviewed_by' => null,
                'reviewed_by_admin_id' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $approved
                ? $this->payments->markSuccessful($order, 'manual_payment', $submission->reference, $reviewer)
                : $this->payments->markFailed($order, $note ?: 'Admin rejected the submitted payment reference.', 'manual_payment', $reviewer);

            collect([$order->buyer_id, $order->seller_id])
                ->filter()
                ->unique()
                ->each(fn (int $recipientId) => event(new OrderStatusUpdated(
                    $order->fresh(['buyer', 'seller']),
                    $recipientId,
                )));

            return $submission->fresh(['order', 'buyer', 'method', 'reviewer', 'adminReviewer']);
        });
    }
}
