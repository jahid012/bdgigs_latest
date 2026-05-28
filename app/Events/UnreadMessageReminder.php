<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnreadMessageReminder
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Message $message,
        public User $recipient,
    ) {
    }
}
