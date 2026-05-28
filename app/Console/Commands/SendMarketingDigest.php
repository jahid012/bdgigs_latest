<?php

namespace App\Console\Commands;

use App\Events\WeeklyDigestDue;
use App\Models\User;
use Illuminate\Console\Command;

class SendMarketingDigest extends Command
{
    protected $signature = 'marketing:send-weekly-digest';

    protected $description = 'Queue weekly marketplace digest emails for opted-in users.';

    public function handle(): int
    {
        $sent = 0;

        User::query()
            ->whereNull('marketing_unsubscribed_at')
            ->whereNull('deactivated_at')
            ->whereNull('suspended_at')
            ->chunkById(100, function ($users) use (&$sent) {
                foreach ($users as $user) {
                    event(new WeeklyDigestDue($user, [
                        'action_url' => '/dashboard',
                        'notification_detail' => 'Your weekly marketplace digest is ready.',
                    ]));
                    $sent++;
                }
            });

        $this->info("Weekly digests queued: {$sent}");

        return self::SUCCESS;
    }
}
