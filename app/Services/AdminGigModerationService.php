<?php

namespace App\Services;

use App\Models\Gig;

class AdminGigModerationService
{
    public function updateStatus(Gig $gig, string $action): Gig
    {
        $status = match ($action) {
            'publish' => 'Published',
            'pause' => 'Paused',
            'reject' => 'Rejected',
            'request_edits' => 'Needs edit',
        };

        $gig->forceFill([
            'status' => $status,
            'status_class' => $this->statusClass($status),
        ])->save();

        return $gig->refresh();
    }

    public function toggleFeatured(Gig $gig): Gig
    {
        $gig->forceFill([
            'featured' => ! $gig->featured,
        ])->save();

        return $gig->refresh();
    }

    private function statusClass(string $status): string
    {
        return match (strtolower($status)) {
            'published', 'live' => 'status-completed',
            'rejected' => 'status-cancelled',
            default => 'status-delivered',
        };
    }
}
