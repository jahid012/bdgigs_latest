<?php

namespace App\Console\Commands;

use App\Events\ProfileCompletionReminderDue;
use App\Models\User;
use App\Services\ProfileCompletionService;
use Illuminate\Console\Command;

class SendProfileCompletionReminders extends Command
{
    protected $signature = 'users:send-profile-completion-reminders {--days=1}';

    protected $description = 'Send idempotent reminders to users with incomplete marketplace profiles.';

    public function handle(ProfileCompletionService $profiles): int
    {
        $sent = 0;
        $registeredBefore = now()->subDays((int) $this->option('days'));

        User::query()
            ->with(['buyerProfile', 'sellerProfile'])
            ->where('created_at', '<=', $registeredBefore)
            ->whereNull('deactivated_at')
            ->whereNull('suspended_at')
            ->where(function ($users) {
                $users
                    ->whereNull('profile_completion_reminded_at')
                    ->orWhere('profile_completion_reminded_at', '<=', now()->subDays(7));
            })
            ->chunkById(100, function ($users) use ($profiles, &$sent) {
                foreach ($users as $user) {
                    $missing = $profiles->missingFields($user);

                    if ($missing === []) {
                        continue;
                    }

                    event(new ProfileCompletionReminderDue($user, $missing));
                    $sent++;
                }
            });

        $this->info("Profile completion reminders queued: {$sent}");

        return self::SUCCESS;
    }
}
