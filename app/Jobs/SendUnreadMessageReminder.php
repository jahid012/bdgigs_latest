<?php

namespace App\Jobs;

use App\Mail\UnreadMessageReminderMail;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendUnreadMessageReminder implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $messageId,
        public int $recipientId,
    ) {
    }

    public function handle(): void
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

        if ($participant->last_read_at && $participant->last_read_at->greaterThanOrEqualTo($message->sent_at)) {
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

        Mail::to($recipient)->send(new UnreadMessageReminderMail($message, $recipient));

        $participant->forceFill(['last_email_reminded_at' => now()])->save();
        $message->forceFill(['email_reminder_sent_at' => now()])->save();
    }
}
