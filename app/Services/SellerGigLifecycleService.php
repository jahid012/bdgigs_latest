<?php

namespace App\Services;

use App\Events\GigPaused;
use App\Events\GigReactivated;
use App\Events\GigSubmittedForReview;
use App\Models\Gig;
use App\Models\User;

class SellerGigLifecycleService
{
    public function activate(Gig $gig, ?User $actor = null): Gig
    {
        abort_unless(in_array($gig->status, ['approved', 'paused'], true), 422, 'This gig must be approved before it can be activated.');

        $previous = $gig->status;
        $gig->forceFill([
            'status' => 'approved',
            'status_class' => 'status-completed',
            'paused_at' => null,
        ])->save();

        $this->record($gig, $actor, 'gig_reactivated', $previous, 'approved', 'Seller reactivated the gig.');
        event(new GigReactivated($gig->refresh()->load('seller'), $actor));

        return $gig->refresh();
    }

    public function submitForReview(Gig $gig, ?User $actor = null): Gig
    {
        $previous = $gig->status;
        $gig->forceFill([
            'status' => 'pending_review',
            'status_class' => 'status-progress',
            'submitted_for_review_at' => now(),
            'moderation_reason' => null,
        ])->save();

        $this->record($gig, $actor, 'gig_submitted_for_review', $previous, 'pending_review', 'Seller submitted the gig for moderation.');
        event(new GigSubmittedForReview($gig->refresh()->load('seller')));

        return $gig->refresh();
    }

    public function pause(Gig $gig, ?User $actor = null, ?string $reason = null): Gig
    {
        $previous = $gig->status;
        $gig->forceFill([
            'status' => 'paused',
            'status_class' => 'status-delivered',
            'paused_at' => now(),
            'moderation_reason' => $reason,
        ])->save();

        $this->record($gig, $actor, 'gig_paused', $previous, 'paused', $reason ?: 'Seller paused the gig.');
        event(new GigPaused($gig->refresh()->load('seller'), $actor, $reason));

        return $gig->refresh();
    }

    public function delete(Gig $gig): void
    {
        $gig->delete();
    }

    private function record(Gig $gig, ?User $actor, string $type, ?string $from, string $to, ?string $reason = null): void
    {
        $gig->moderationEvents()->create([
            'actor_id' => $actor?->id,
            'event_type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
        ]);
    }
}
