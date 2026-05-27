<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MessageSaveController extends Controller
{
    public function index(Request $request, Conversation $conversation): AnonymousResourceCollection
    {
        $this->authorizeParticipant($request, $conversation);

        return MessageResource::collection(
            $request->user()
                ->savedMessages()
                ->where('conversation_id', $conversation->id)
                ->with(['conversation', 'attachments', 'customOffer.gig', 'customOffer.order', 'savedByUsers'])
                ->oldest('sent_at')
                ->get()
        );
    }

    public function store(Request $request, Message $message): MessageResource
    {
        $this->authorizeParticipant($request, $message->conversation);

        $request->user()->savedMessages()->syncWithoutDetaching([$message->id]);

        return MessageResource::make($message->load(['conversation', 'attachments', 'customOffer.gig', 'customOffer.order', 'savedByUsers']));
    }

    public function destroy(Request $request, Message $message): Response
    {
        $this->authorizeParticipant($request, $message->conversation);

        $request->user()->savedMessages()->detach($message->id);

        return response()->noContent();
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless(
            $conversation->participants()->where('user_id', $request->user()->id)->exists(),
            403,
        );
    }
}
