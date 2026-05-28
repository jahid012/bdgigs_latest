<?php

namespace App\Console\Commands;

use App\Services\OrderReminderService;
use Illuminate\Console\Command;

class MarkOverdueOrders extends Command
{
    protected $signature = 'orders:mark-overdue';

    protected $description = 'Mark paid active orders overdue after their delivery deadline passes.';

    public function handle(OrderReminderService $reminders): int
    {
        $count = $reminders->markOverdue();
        $this->info("Orders marked overdue: {$count}");

        return self::SUCCESS;
    }
}
