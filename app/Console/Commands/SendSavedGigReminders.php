<?php

namespace App\Console\Commands;

use App\Events\SavedGigReminderDue;
use App\Models\User;
use Illuminate\Console\Command;

class SendSavedGigReminders extends Command
{
    protected $signature = 'marketing:send-saved-gig-reminders';

    protected $description = 'Queue saved gig reminders for opted-in users.';

    public function handle(): int
    {
        $sent = 0;

        User::query()
            ->whereNull('marketing_unsubscribed_at')
            ->whereHas('savedServices')
            ->chunkById(100, function ($users) use (&$sent) {
                foreach ($users as $user) {
                    event(new SavedGigReminderDue($user, [
                        'action_url' => '/dashboard/saved-services',
                        'notification_detail' => 'Your saved services are waiting in your shortlist.',
                    ]));
                    $sent++;
                }
            });

        $this->info("Saved gig reminders queued: {$sent}");

        return self::SUCCESS;
    }
}
