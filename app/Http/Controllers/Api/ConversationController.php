<?php

namespace App\Http\Controllers\Api;

use App\Events\ConversationUpdated;
use App\Events\AdminSupportMessageReceived;
use App\Events\GigInquiryReceived;
use App\Events\MessageAttachmentReceived;
use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\UserTyping;
use App\Jobs\SendUnreadMessageReminder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreConversationMessageRequest;
use App\Http\Requests\Api\StoreConversationRequest;
use App\Http\Resources\ActionResource;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Gig;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Services\OfflinePushNotifier;
use App\Services\MessageAutomationService;
use App\Support\MarketplaceNotifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ConversationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filter = $request->query('filter', 'all');

        return ConversationResource::collection(
            Conversation::query()
                ->with([
                    'gig',
                    'messages.attachments',
                    'messages.customOffer.gig',
                    'messages.customOffer.order',
                    'messages.savedByUsers',
                    'participants.user',
                ])
                ->whereHas('participants', function ($query) use ($request, $filter) {
                    $query->where('user_id', $request->user()->id);

                    if ($filter === 'archived') {
                        $query->whereNotNull('archived_at');
                    } else {
                        $query->whereNull('archived_at');
                    }

                    if (in_array($filter, ['buying', 'selling', 'order'], true)) {
                        $query->where('context_role', $filter === 'order' ? 'order' : $filter);
                    }
                })
                ->latest('last_message_at')
                ->latest()
                ->get()
        );
    }

    public function store(
        StoreConversationRequest $request,
        MarketplaceNotifier $notifier,
        OfflinePushNotifier $pushNotifier
    ): ConversationResource {
        $payload = $request->validated();

        [$targetUser, $context] = $this->resolveConversationTarget($request->user(), $payload);

        abort_if($targetUser->id === $request->user()->id, 422, 'You cannot start a conversation with yourself.');

        $conversation = $this->findOrCreateConversation(
            $request->user(),
            $targetUser,
            $payload['contextType'],
            $payload['contextId'] ?? null,
            $context,
        );

        if (trim((string) ($payload['message'] ?? '')) !== '') {
            $this->createMessage(
                $request,
                $conversation,
                $payload['message'],
                $notifier,
                $pushNotifier,
                $payload['clientId'] ?? null,
            );
        }

        return ConversationResource::make($conversation->fresh([
            'gig',
            'messages.attachments',
            'messages.customOffer.gig',
            'messages.customOffer.order',
            'messages.savedByUsers',
            'participants.user',
        ]));
    }

    public function show(Request $request, Conversation $conversation): ConversationResource
    {
        $this->authorizeParticipant($request, $conversation);

        return ConversationResource::make($conversation->load([
            'gig',
            'messages.attachments',
            'messages.customOffer.gig',
            'messages.customOffer.order',
            'messages.savedByUsers',
            'participants.user',
        ]));
    }

    public function messages(Request $request, Conversation $conversation): AnonymousResourceCollection
    {
        $this->authorizeParticipant($request, $conversation);

        $limit = min(max((int) $request->query('limit', 50), 1), 100);
        $before = (int) $request->query('before', 0);
        $messages = $conversation->messages()
            ->with(['attachments', 'customOffer.gig', 'customOffer.order', 'savedByUsers'])
            ->when($before > 0, fn ($query) => $query->where('id', '<', $before))
            ->latest('id')
            ->take($limit)
            ->get()
            ->sortBy('id')
            ->values();

        return MessageResource::collection($messages);
    }

    public function storeMessage(
        StoreConversationMessageRequest $request,
        Conversation $conversation,
        MarketplaceNotifier $notifier,
        OfflinePushNotifier $pushNotifier,
        MessageAutomationService $messageAutomation
    ): MessageResource {
        $payload = $request->validated();

        $message = $this->createMessage(
            $request,
            $conversation,
            $payload['text'] ?? '',
            $notifier,
            $pushNotifier,
            $payload['clientId'] ?? null,
            $payload['attachments'] ?? [],
            $messageAutomation,
        );

        return MessageResource::make($message);
    }

    public function markRead(Request $request, Conversation $conversation): ConversationResource
    {
        $this->authorizeParticipant($request, $conversation);

        $participant = $conversation->participants()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $participant->forceFill([
            'unread_count' => 0,
            'last_read_at' => now(),
            'last_seen_at' => now(),
            'last_email_reminded_at' => null,
        ])->save();

        Message::where('conversation_id', $conversation->id)
            ->where('recipient_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $request->user()->forceFill(['last_seen_at' => now()])->save();

        $freshConversation = $conversation->fresh([
            'gig',
            'messages.attachments',
            'messages.customOffer.gig',
            'messages.customOffer.order',
            'messages.savedByUsers',
            'participants.user',
        ]);

        $freshConversation->participants
            ->where('user_id', '!=', $request->user()->id)
            ->each(function (ConversationParticipant $participant) use ($freshConversation, $request) {
                if (! $participant->user) {
                    return;
                }

                event(new MessageRead(
                    $freshConversation,
                    $request->user()->id,
                    $participant->user_id,
                    $this->conversationPayloadForUser($freshConversation, $participant->user),
                ));

                event(new ConversationUpdated(
                    $freshConversation,
                    $participant->user_id,
                    $this->conversationPayloadForUser($freshConversation, $participant->user),
                ));
            });

        $this->syncLegacyUnreadCounts($conversation);

        return ConversationResource::make($conversation->fresh([
            'gig',
            'messages.attachments',
            'messages.customOffer.gig',
            'messages.customOffer.order',
            'messages.savedByUsers',
            'participants.user',
        ]));
    }

    public function typing(Request $request, Conversation $conversation): ActionResource
    {
        $this->authorizeParticipant($request, $conversation);

        $participant = $conversation->participants()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $participant->forceFill([
            'last_typing_at' => now(),
            'last_seen_at' => now(),
        ])->save();

        $conversation->participants()
            ->where('user_id', '!=', $request->user()->id)
            ->pluck('user_id')
            ->each(fn (int $recipientId) => event(new UserTyping(
                $conversation,
                $request->user(),
                $recipientId,
            )));

        return ActionResource::make(['typing' => true]);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless(
            $conversation->participants()
                ->where('user_id', $request->user()->id)
                ->exists()
                || in_array($request->user()->id, [$conversation->buyer_id, $conversation->seller_id], true),
            403
        );
    }

    private function createMessage(
        Request $request,
        Conversation $conversation,
        string $body,
        MarketplaceNotifier $notifier,
        OfflinePushNotifier $pushNotifier,
        ?string $clientId = null,
        array $attachments = [],
        ?MessageAutomationService $messageAutomation = null,
    ): Message {
        $conversation->loadMissing('participants.user');
        $sender = $request->user();
        $recipientParticipants = $conversation->participants
            ->where('user_id', '!=', $sender->id)
            ->values();
        $primaryRecipient = $recipientParticipants->first()?->user;

        if ($clientId) {
            $existing = Message::where('conversation_id', $conversation->id)
                ->where('sender_id', $sender->id)
                ->where('client_id', $clientId)
                ->first();

            if ($existing) {
                return $existing->load(['conversation', 'attachments', 'customOffer.gig', 'customOffer.order']);
            }
        }

        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $primaryRecipient?->id,
            'sender_name' => $sender->name,
            'body' => trim($body) !== '' ? $body : 'Shared an attachment.',
            'client_id' => $clientId,
            'sent_at' => now(),
        ]);

        $storedAttachments = collect($attachments)
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file) => $this->storeMessageAttachment($conversation, $message, $file))
            ->values();

        $conversation->forceFill([
            'last_message_at' => $message->sent_at,
        ])->save();

        $conversation->participants()
            ->where('user_id', $sender->id)
            ->update([
                'last_read_at' => now(),
                'last_seen_at' => now(),
                'updated_at' => now(),
            ]);

        $sender->forceFill(['last_seen_at' => now()])->save();

        $recentMessageCount = Message::where('sender_id', $sender->id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentMessageCount > 25) {
            app(\App\Services\SuspiciousActivityService::class)->log(
                $sender,
                'rapid_message_volume',
                $recentMessageCount > 50 ? 'critical' : 'high',
                'Many messages were sent in a short period.',
                ['message_count' => $recentMessageCount, 'conversation_id' => $conversation->public_id],
                $request,
            );
        }

        foreach ($recipientParticipants as $participant) {
            $participant->increment('unread_count');

            $recipient = $participant->user;

            if (! $recipient) {
                continue;
            }

            $notifier->notify(
                $recipient,
                'Message',
                'New message',
                "{$sender->name} replied about {$conversation->subject}.",
                '/dashboard/messages?conversation='.$conversation->public_id,
                ['conversationId' => $conversation->public_id],
            );

            $pushNotifier->notifyOfflineUser(
                $recipient,
                'New message from '.$sender->name,
                Str::limit($body, 120),
                [
                    'conversationId' => $conversation->public_id,
                    'url' => '/dashboard/messages?conversation='.$conversation->public_id,
                ],
            );

            SendUnreadMessageReminder::dispatch($message->id, $recipient->id)
                ->delay(now()->addMinutes(15));

            $freshConversation = $conversation->fresh([
                'gig',
                'messages.attachments',
                'messages.customOffer.gig',
                'messages.customOffer.order',
                'participants.user',
            ]);

            event(new MessageSent(
                $message->load(['conversation', 'attachments', 'customOffer.gig', 'customOffer.order']),
                $recipient->id,
                $this->conversationPayloadForUser($freshConversation, $recipient),
            ));

            event(new ConversationUpdated(
                $freshConversation,
                $recipient->id,
                $this->conversationPayloadForUser($freshConversation, $recipient),
            ));

            $messageAutomation ??= app(MessageAutomationService::class);

            if (! $messageAutomation->isRecipientActiveInConversation($message, $recipient)) {
                if ($storedAttachments->isNotEmpty()) {
                    event(new MessageAttachmentReceived($message->fresh(['conversation', 'sender', 'attachments']), $recipient));
                }

                if ($sender->can('admin.access')) {
                    event(new AdminSupportMessageReceived($message->fresh(['conversation', 'sender']), $recipient));
                }
            }
        }

        if ($conversation->context_type === 'gig' && $conversation->gig && (int) $message->sender_id !== (int) $conversation->gig->seller_id) {
            event(new GigInquiryReceived($conversation->gig, $message->fresh(['conversation', 'sender'])));
        }

        $this->syncLegacyUnreadCounts($conversation);

        return $message->load(['conversation', 'attachments', 'customOffer.gig', 'customOffer.order']);
    }

    private function storeMessageAttachment(Conversation $conversation, Message $message, UploadedFile $file)
    {
        $directory = public_path('uploads/message-attachments/'.$conversation->public_id);
        File::ensureDirectoryExists($directory);

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $filename = Str::uuid()->toString().'.'.$extension;
        $file->move($directory, $filename);
        $path = 'uploads/message-attachments/'.$conversation->public_id.'/'.$filename;

        return $message->attachments()->create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'url' => '/'.$path,
        ]);
    }

    private function findOrCreateConversation(
        User $currentUser,
        User $targetUser,
        string $contextType,
        ?string $contextId,
        array $context,
    ): Conversation {
        $conversation = Conversation::query()
            ->where('context_type', $contextType)
            ->where('context_id', $contextId)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $currentUser->id))
            ->whereHas('participants', fn ($query) => $query->where('user_id', $targetUser->id))
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'public_id' => 'thread-'.Str::uuid(),
                'created_by_id' => $currentUser->id,
                'buyer_id' => $context['legacyBuyerId'] ?? $currentUser->id,
                'seller_id' => $context['legacySellerId'] ?? $targetUser->id,
                'gig_id' => $context['gigId'] ?? null,
                'context_type' => $contextType,
                'context_id' => $contextId,
                'subject' => $context['subject'],
                'buyer_name' => $context['buyerName'] ?? $currentUser->name,
                'seller_name' => $context['sellerName'] ?? $targetUser->name,
                'status' => $context['status'] ?? 'Open',
                'status_class' => $context['statusClass'] ?? 'status-progress',
                'priority' => null,
                'metadata' => $context['metadata'] ?? [],
                'last_message_at' => now(),
            ]);
        }

        foreach ([
            [$currentUser, $context['currentRole'] ?? 'member'],
            [$targetUser, $context['targetRole'] ?? 'member'],
        ] as [$user, $role]) {
            ConversationParticipant::updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                ],
                [
                    'context_role' => $role,
                ],
            );
        }

        return $conversation->fresh(['participants.user', 'messages.attachments', 'messages.customOffer.gig', 'messages.customOffer.order', 'gig']);
    }

    private function resolveConversationTarget(User $currentUser, array $payload): array
    {
        $contextType = $payload['contextType'];
        $contextId = $payload['contextId'] ?? null;

        if ($contextType === 'gig' && $contextId) {
            $gig = Gig::where('slug', $contextId)->orWhere('id', $contextId)->firstOrFail();
            $target = $payload['targetUserId'] ?? $gig->seller_id;
            $targetUser = User::findOrFail($target);

            return [
                $targetUser,
                [
                    'gigId' => $gig->id,
                    'subject' => $gig->title,
                    'legacyBuyerId' => $currentUser->id,
                    'legacySellerId' => $targetUser->id,
                    'buyerName' => $currentUser->name,
                    'sellerName' => $targetUser->name,
                    'currentRole' => 'buying',
                    'targetRole' => 'selling',
                    'metadata' => [
                        'gigSlug' => $gig->slug,
                        'gigTitle' => $gig->title,
                    ],
                ],
            ];
        }

        if ($contextType === 'order' && $contextId) {
            $orderCode = ltrim($contextId, '#');
            $order = Order::where('code', $orderCode)->orWhere('id', $contextId)->firstOrFail();
            abort_unless(in_array($currentUser->id, [$order->buyer_id, $order->seller_id], true), 403);
            $targetUserId = $order->buyer_id === $currentUser->id ? $order->seller_id : $order->buyer_id;
            $targetUser = User::findOrFail($payload['targetUserId'] ?? $targetUserId);

            return [
                $targetUser,
                [
                    'gigId' => $order->gig_id,
                    'subject' => $order->service,
                    'legacyBuyerId' => $order->buyer_id,
                    'legacySellerId' => $order->seller_id,
                    'buyerName' => $order->buyer_name ?: $order->buyer?->name,
                    'sellerName' => $order->seller_name ?: $order->seller?->name,
                    'currentRole' => 'order',
                    'targetRole' => 'order',
                    'status' => $order->status,
                    'statusClass' => $order->status_class,
                    'metadata' => [
                        'orderCode' => $order->code,
                    ],
                ],
            ];
        }

        $targetUser = ! empty($payload['targetUserId'])
            ? User::findOrFail($payload['targetUserId'])
            : $this->resolveProfileUser($payload);

        return [
            $targetUser,
            [
                'subject' => 'Conversation with '.$targetUser->name,
                'legacyBuyerId' => $currentUser->id,
                'legacySellerId' => $targetUser->id,
                'buyerName' => $currentUser->name,
                'sellerName' => $targetUser->name,
                'currentRole' => 'buying',
                'targetRole' => 'selling',
                'metadata' => [
                    'profileSlug' => $payload['targetSlug'] ?? null,
                    'targetName' => $payload['targetName'] ?? $targetUser->name,
                ],
            ],
        ];
    }

    private function resolveProfileUser(array $payload): User
    {
        if (! empty($payload['targetName'])) {
            $user = User::where('name', $payload['targetName'])->first();

            if ($user) {
                return $user;
            }
        }

        $slug = ltrim((string) ($payload['targetSlug'] ?? $payload['contextId'] ?? ''), '@');

        if ($slug) {
            $user = User::where('username', $slug)->first()
                ?: User::all()->first(fn (User $user) => Str::slug($user->name) === $slug);

            if ($user) {
                return $user;
            }
        }

        abort(422, 'This profile is not available for messaging yet.');
    }

    private function syncLegacyUnreadCounts(Conversation $conversation): void
    {
        $conversation->load('participants');

        $conversation->forceFill([
            'buyer_unread_count' => $conversation->participants->firstWhere('user_id', $conversation->buyer_id)?->unread_count ?? 0,
            'seller_unread_count' => $conversation->participants->firstWhere('user_id', $conversation->seller_id)?->unread_count ?? 0,
        ])->save();
    }

    private function conversationPayloadForUser(Conversation $conversation, User $user): array
    {
        $request = request()->duplicate();
        $request->setUserResolver(fn () => $user);

        return ConversationResource::make($conversation)->resolve($request);
    }
}
