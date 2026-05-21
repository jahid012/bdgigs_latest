<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Support\MarketplaceNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $role = $request->query('role') === 'seller' ? 'seller' : 'buyer';
        $column = $role === 'seller' ? 'seller_id' : 'buyer_id';

        return ConversationResource::collection(
            Conversation::query()
                ->with('messages')
                ->where($column, $request->user()->id)
                ->latest('updated_at')
                ->get()
        );
    }

    public function show(Request $request, Conversation $conversation): ConversationResource
    {
        $this->authorizeParticipant($request, $conversation);

        return ConversationResource::make($conversation->load('messages'));
    }

    public function storeMessage(
        Request $request,
        Conversation $conversation,
        MarketplaceNotifier $notifier
    ): MessageResource {
        $this->authorizeParticipant($request, $conversation);

        $payload = $request->validate([
            'text' => ['required', 'string', 'max:4000'],
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'sender_name' => $request->user()->name,
            'body' => $payload['text'],
            'sent_at' => now(),
        ]);

        $recipient = $conversation->buyer_id === $request->user()->id
            ? $conversation->seller
            : $conversation->buyer;

        if ($recipient) {
            $notifier->notify(
                $recipient,
                'Message',
                'New message',
                "{$request->user()->name} replied about {$conversation->subject}.",
                '/dashboard/messages',
                ['conversationId' => $conversation->public_id],
            );

            event(new MessageSent($message->load('conversation'), $recipient->id));
        }

        $conversation->touch();

        return MessageResource::make($message);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless(
            in_array($request->user()->id, [$conversation->buyer_id, $conversation->seller_id], true),
            403
        );
    }
}
