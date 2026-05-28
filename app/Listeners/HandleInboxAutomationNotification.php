<?php

namespace App\Listeners;

use App\Events\AdminSupportMessageReceived;
use App\Events\CustomOfferMessageReceived;
use App\Events\MessageAttachmentReceived;
use App\Events\UnreadMessageReminder;
use App\Models\Message;
use App\Models\User;
use App\Services\EmailService;
use App\Support\MarketplaceNotifier;
use Illuminate\Support\Str;

class HandleInboxAutomationNotification
{
    public function __construct(
        private readonly MarketplaceNotifier $notifier,
        private readonly EmailService $emails,
    ) {
    }

    public function handle(object $event): void
    {
        if ($event instanceof MessageAttachmentReceived) {
            $this->send($event->message, $event->recipient, 'attachment_received_in_conversation', 'New attachment received', 'A file was shared in '.$event->message->conversation?->subject.'.');
        }

        if ($event instanceof CustomOfferMessageReceived) {
            $this->send($event->message, $event->recipient, 'custom_offer_message_received', 'Custom offer message received', 'A custom offer card was sent in '.$event->message->conversation?->subject.'.', [
                'custom_offer_title' => $event->offer->title,
                'custom_offer_price' => '$'.number_format($event->offer->price_cents / 100, 2),
            ]);
        }

        if ($event instanceof AdminSupportMessageReceived) {
            $this->send($event->message, $event->recipient, 'admin_support_message_received', 'Support sent a message', 'A support message was added to '.$event->message->conversation?->subject.'.');
        }

        if ($event instanceof UnreadMessageReminder) {
            $this->send($event->message, $event->recipient, 'unread_message_reminder', 'Unread message reminder', Str::limit((string) $event->message->body, 180));
            $event->message->conversation?->participants()
                ->where('user_id', $event->recipient->id)
                ->update(['last_email_reminded_at' => now()]);
            $event->message->forceFill(['email_reminder_sent_at' => now()])->save();
        }
    }

    private function send(Message $message, User $recipient, string $template, string $title, string $detail, array $extra = []): void
    {
        $message->loadMissing('conversation', 'sender');
        $url = '/dashboard/messages?conversation='.$message->conversation?->public_id;

        $this->notifier->notify(
            $recipient,
            $template,
            $title,
            $detail,
            $url,
            ['preferenceKey' => 'inboxMessages', 'conversationId' => $message->conversation?->public_id],
        );

        $this->emails->queueTemplateEmail($template, $recipient, [
            'sender_name' => $message->sender?->name ?: $message->sender_name,
            'conversation_subject' => $message->conversation?->subject,
            'conversation_url' => $url,
            'action_url' => $url,
            'notification_title' => $title,
            'notification_detail' => $detail,
            ...$extra,
        ]);
    }
}
