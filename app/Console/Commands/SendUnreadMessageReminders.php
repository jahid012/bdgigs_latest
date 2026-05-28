<?php

namespace App\Console\Commands;

use App\Services\MessageAutomationService;
use Illuminate\Console\Command;

class SendUnreadMessageReminders extends Command
{
    protected $signature = 'messages:send-unread-reminders {--minutes=15}';

    protected $description = 'Send delayed unread message reminders for inactive conversation recipients.';

    public function handle(MessageAutomationService $messages): int
    {
        $count = $messages->sendUnreadReminders((int) $this->option('minutes'));
        $this->info("Unread message reminders sent: {$count}");

        return self::SUCCESS;
    }
}
