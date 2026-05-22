<?php

namespace App\Services;

use App\Models\Gig;

class SellerGigLifecycleService
{
    public function activate(Gig $gig): Gig
    {
        $gig->forceFill([
            'status' => 'Live',
            'status_class' => 'status-completed',
        ])->save();

        return $gig->refresh();
    }

    public function pause(Gig $gig): Gig
    {
        $gig->forceFill([
            'status' => 'Paused',
            'status_class' => 'status-delivered',
        ])->save();

        return $gig->refresh();
    }

    public function delete(Gig $gig): void
    {
        $gig->delete();
    }
}
