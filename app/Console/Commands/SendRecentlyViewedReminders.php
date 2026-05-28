<?php

namespace App\Console\Commands;

use App\Events\RecentlyViewedReminderDue;
use App\Models\User;
use App\Models\VisitorPageView;
use Illuminate\Console\Command;

class SendRecentlyViewedReminders extends Command
{
    protected $signature = 'marketing:send-recently-viewed-reminders';

    protected $description = 'Send opted-in reminders to users who recently viewed gigs but did not order.';

    public function handle(): int
    {
        $sent = 0;
        $userIds = VisitorPageView::human()
            ->whereNotNull('user_id')
            ->where('path', 'like', '/gigs/%')
            ->where('visited_at', '<=', now()->subHours(12))
            ->where('visited_at', '>=', now()->subDays(7))
            ->distinct()
            ->pluck('user_id');

        User::query()
            ->whereIn('id', $userIds)
            ->whereNull('marketing_unsubscribed_at')
            ->chunkById(100, function ($users) use (&$sent) {
                foreach ($users as $user) {
                    event(new RecentlyViewedReminderDue($user, [
                        'action_url' => '/search/gigs',
                        'notification_detail' => 'Services you viewed recently are still available.',
                    ]));
                    $sent++;
                }
            });

        $this->info("Recently viewed reminders queued: {$sent}");

        return self::SUCCESS;
    }
}
