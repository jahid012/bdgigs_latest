<?php

namespace App\Console\Commands;

use App\Services\OrderReminderService;
use Illuminate\Console\Command;

class SendOrderDeadlineReminders extends Command
{
    protected $signature = 'orders:send-deadline-reminders';

    protected $description = 'Send idempotent 24 hour and 6 hour order deadline reminders.';

    public function handle(OrderReminderService $reminders): int
    {
        $count = $reminders->sendDeadlineReminders();
        $this->info("Deadline reminders sent: {$count}");

        return self::SUCCESS;
    }
}
