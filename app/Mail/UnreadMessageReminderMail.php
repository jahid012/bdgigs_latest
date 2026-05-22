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
        public Message $threadMessage,
        public User $recipient,
    ) {
        $this->threadMessage->loadMissing('conversation');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Unread message about '.$this->threadMessage->conversation->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.unread-message-reminder',
        );
    }
}
