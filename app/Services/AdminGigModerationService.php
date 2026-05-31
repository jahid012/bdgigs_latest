<?php

namespace App\Services;

use App\Events\GigApproved;
use App\Events\GigPaused;
use App\Events\GigReactivated;
use App\Events\GigRejected;
use App\Models\Admin;
use App\Models\Gig;

class AdminGigModerationService
{
    public function updateStatus(Gig $gig, string $action, ?Admin $admin = null, ?string $reason = null): Gig
    {
        $status = match ($action) {
            'publish', 'approve' => 'approved',
            'pause' => 'paused',
            'deactivate' => 'deactivated',
            'reactivate' => 'approved',
            'reject', 'request_edits' => 'rejected',
        };
        $previous = $gig->status;

        $gig->forceFill([
            'status' => $status,
            'status_class' => $this->statusClass($status),
            'moderated_by' => null,
            'moderated_by_admin_id' => $admin?->id,
            'moderated_at' => now(),
            'moderation_reason' => $reason,
            'paused_at' => $status === 'paused' ? now() : ($status === 'approved' ? null : $gig->paused_at),
            'deactivated_at' => $status === 'deactivated' ? now() : ($status === 'approved' ? null : $gig->deactivated_at),
        ])->save();

        $gig->moderationEvents()->create([
            'actor_admin_id' => $admin?->id,
            'event_type' => 'gig_'.$status,
            'from_status' => $previous,
            'to_status' => $status,
            'reason' => $reason,
            'metadata' => ['action' => $action],
        ]);

        $fresh = $gig->refresh()->load('seller');
        match ($status) {
            'approved' => $action === 'reactivate'
                ? event(new GigReactivated($fresh, $admin))
                : event(new GigApproved($fresh, $admin)),
            'paused', 'deactivated' => event(new GigPaused($fresh, $admin, $reason)),
            'rejected' => event(new GigRejected($fresh, $admin, $reason)),
            default => null,
        };

        return $gig->refresh();
    }

    public function toggleFeatured(Gig $gig, ?Admin $admin = null): Gig
    {
        return $this->setFeatured($gig, ! $gig->featured, $admin);
    }

    public function setFeatured(Gig $gig, bool $featured, ?Admin $admin = null): Gig
    {
        $previous = $gig->featured;

        $gig->forceFill([
            'featured' => $featured,
        ])->save();

        if ($previous !== $featured) {
            $gig->moderationEvents()->create([
                'actor_admin_id' => $admin?->id,
                'event_type' => $featured ? 'gig_featured' : 'gig_unfeatured',
                'from_status' => $gig->status,
                'to_status' => $gig->status,
                'metadata' => [
                    'action' => $featured ? 'feature' : 'unfeature',
                    'previous_featured' => $previous,
                ],
            ]);
        }

        return $gig->refresh();
    }

    private function statusClass(string $status): string
    {
        return match (strtolower($status)) {
            'published', 'live', 'approved' => 'status-completed',
            'rejected', 'deactivated' => 'status-cancelled',
            'pending_review' => 'status-progress',
            default => 'status-delivered',
        };
    }
}
