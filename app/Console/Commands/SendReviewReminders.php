<?php

namespace App\Console\Commands;

use App\Services\OrderReminderService;
use Illuminate\Console\Command;

class SendReviewReminders extends Command
{
    protected $signature = 'reviews:send-deadline-reminders';

    protected $description = 'Send idempotent reminders before mutual review windows close.';

    public function handle(OrderReminderService $reminders): int
    {
        $count = $reminders->sendReviewReminders();
        $this->info("Review reminders sent: {$count}");

        return self::SUCCESS;
    }
}
