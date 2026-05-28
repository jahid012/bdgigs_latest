<?php

namespace App\Console\Commands;

use App\Events\GigPerformanceSummary;
use App\Models\User;
use Illuminate\Console\Command;

class SendGigPerformanceSummary extends Command
{
    protected $signature = 'gigs:send-performance-summary';

    protected $description = 'Send seller gig performance summaries without duplicating the current weekly campaign.';

    public function handle(): int
    {
        $sent = 0;

        User::query()
            ->where('seller_status', 'approved')
            ->whereHas('gigs')
            ->withCount('gigs')
            ->chunkById(100, function ($sellers) use (&$sent) {
                foreach ($sellers as $seller) {
                    event(new GigPerformanceSummary($seller, [
                        'notification_detail' => 'You have '.$seller->gigs_count.' services in your seller catalog.',
                        'action_url' => '/dashboard/seller/services',
                    ]));
                    $sent++;
                }
            });

        $this->info("Gig performance summaries queued: {$sent}");

        return self::SUCCESS;
    }
}
