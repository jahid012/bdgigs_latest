<?php

namespace App\Console\Commands;

use App\Services\OrderReminderService;
use Illuminate\Console\Command;

class ExpireReviewPeriods extends Command
{
    protected $signature = 'reviews:expire-periods';

    protected $description = 'Close completed order review windows after the configured review period.';

    public function handle(OrderReminderService $reminders): int
    {
        $count = $reminders->expireReviewPeriods();
        $this->info("Review periods expired: {$count}");

        return self::SUCCESS;
    }
}
