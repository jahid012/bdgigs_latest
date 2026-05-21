<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UnreadMessageReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Message $message,
        public User $recipient,
    ) {
        $this->message->loadMissing('conversation');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Unread message about '.$this->message->conversation->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.unread-message-reminder',
        );
    }
}
