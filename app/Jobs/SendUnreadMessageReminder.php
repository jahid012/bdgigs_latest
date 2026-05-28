<?php

namespace App\Jobs;

use App\Events\UnreadMessageReminder;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageAutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendUnreadMessageReminder implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $messageId,
        public int $recipientId,
    ) {
    }

    public function handle(MessageAutomationService $automation): void
    {
        $message = Message::with('conversation.participants.user')->find($this->messageId);
        $recipient = User::find($this->recipientId);

        if (! $message || ! $recipient || ! $message->conversation) {
            return;
        }

        $participant = $message->conversation->participants->firstWhere('user_id', $recipient->id);

        if (! $participant || $participant->unread_count < 1 || $message->read_at) {
            return;
        }

        if ($message->sent_at && $message->sent_at->greaterThan(now()->subMinutes(15))) {
            return;
        }

        if ($participant->last_read_at && $participant->last_read_at->greaterThanOrEqualTo($message->sent_at)) {
            return;
        }

        if ($automation->isRecipientActiveInConversation($message, $recipient)) {
            return;
        }

        if ($participant->last_email_reminded_at && ! $participant->last_read_at) {
            return;
        }

        if (
            $participant->last_email_reminded_at &&
            $participant->last_read_at &&
            $participant->last_email_reminded_at->greaterThan($participant->last_read_at)
        ) {
            return;
        }

        event(new UnreadMessageReminder($message, $recipient));
    }
}
