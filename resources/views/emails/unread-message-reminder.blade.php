<!doctype html>
<html>
    <body style="font-family: Arial, sans-serif; color: #222326; line-height: 1.5;">
        <h1 style="font-size: 20px;">You have an unread message</h1>
        <p>Hi {{ $recipient->name }},</p>
        <p>
            {{ $threadMessage->sender_name }} sent you a message about
            <strong>{{ $threadMessage->conversation->subject }}</strong>.
        </p>
        <p style="padding: 12px; border-left: 3px solid #1dbf73; background: #f5f7f6;">
            {{ $threadMessage->body }}
        </p>
        <p>
            <a href="{{ url('/dashboard/messages?conversation='.$threadMessage->conversation->public_id) }}">
                Open conversation
            </a>
        </p>
    </body>
</html>
