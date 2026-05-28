<?php

namespace App\Console\Commands;

use App\Services\OrderReminderService;
use Illuminate\Console\Command;

class SendRequirementReminders extends Command
{
    protected $signature = 'orders:send-requirement-reminders {--hours=24}';

    protected $description = 'Send idempotent reminders for paid orders waiting on buyer requirements.';

    public function handle(OrderReminderService $reminders): int
    {
        $count = $reminders->sendRequirementReminders((int) $this->option('hours'));
        $this->info("Requirement reminders sent: {$count}");

        return self::SUCCESS;
    }
}
