<?php

namespace App\Services;

use App\Events\UnreadMessageReminder;
use App\Models\Message;
use App\Models\User;

class MessageAutomationService
{
    public function sendUnreadReminders(int $delayMinutes = 15): int
    {
        $sent = 0;

        Message::query()
            ->with(['conversation.participants.user', 'attachments', 'customOffer', 'recipient'])
            ->whereNotNull('recipient_id')
            ->whereNull('read_at')
            ->whereNull('email_reminder_sent_at')
            ->where('sent_at', '<=', now()->subMinutes($delayMinutes))
            ->chunkById(100, function ($messages) use (&$sent) {
                foreach ($messages as $message) {
                    $recipient = $message->recipient;

                    if (! $recipient || $this->isRecipientActiveInConversation($message, $recipient)) {
                        continue;
                    }

                    event(new UnreadMessageReminder($message, $recipient));
                    $sent++;
                }
            });

        return $sent;
    }

    public function isRecipientActiveInConversation(Message $message, User $recipient): bool
    {
        $message->loadMissing('conversation.participants');
        $participant = $message->conversation?->participants->firstWhere('user_id', $recipient->id);

        if ($recipient->last_seen_at?->greaterThan(now()->subMinutes(2))) {
            return true;
        }

        if ($participant?->last_seen_at?->greaterThan(now()->subMinutes(2))) {
            return true;
        }

        if ($participant?->last_typing_at?->greaterThan(now()->subMinutes(2))) {
            return true;
        }

        return (bool) $participant?->last_read_at?->greaterThanOrEqualTo($message->sent_at);
    }
}
