<?php

namespace App\Events;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReviewDeadlineReminder
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public User $recipient,
        public string $reminderKey,
    ) {
    }
}
