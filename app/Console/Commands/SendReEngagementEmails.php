<?php

namespace App\Console\Commands;

use App\Events\ReEngagementEmailDue;
use App\Models\User;
use Illuminate\Console\Command;

class SendReEngagementEmails extends Command
{
    protected $signature = 'marketing:send-reengagement-emails';

    protected $description = 'Queue re-engagement emails for opted-in inactive users.';

    public function handle(): int
    {
        $sent = 0;

        User::query()
            ->whereNull('marketing_unsubscribed_at')
            ->whereNull('deactivated_at')
            ->whereNull('suspended_at')
            ->where(function ($users) {
                $users
                    ->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<=', now()->subDays(30));
            })
            ->chunkById(100, function ($users) use (&$sent) {
                foreach ($users as $user) {
                    event(new ReEngagementEmailDue($user, [
                        'action_url' => '/dashboard',
                        'notification_detail' => 'Your marketplace dashboard has new activity waiting.',
                    ]));
                    $sent++;
                }
            });

        $this->info("Re-engagement emails queued: {$sent}");

        return self::SUCCESS;
    }
}
