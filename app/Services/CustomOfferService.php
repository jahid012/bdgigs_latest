<?php

namespace App\Services;

use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\CustomOffer;
use App\Models\Gig;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomOfferService
{
    public function __construct(
        private readonly UserWalletService $wallets,
        private readonly OrderEventNotificationService $events,
    ) {
    }

    public function create(Conversation $conversation, User $seller, array $payload): CustomOffer
    {
        $this->authorizeParticipant($conversation, $seller);

        if ((int) $conversation->seller_id !== (int) $seller->id) {
            throw new AuthorizationException('Only the seller can create a custom offer.');
        }

        $buyer = User::findOrFail($conversation->buyer_id);
        $gig = $this->sellerGig($seller, $payload['gigId']);
        $priceCents = $this->moneyToCents($payload['price']);

        return DB::transaction(function () use ($conversation, $seller, $buyer, $gig, $payload, $priceCents) {
            $offer = $conversation->customOffers()->create([
                'seller_id' => $seller->id,
                'buyer_id' => $buyer->id,
                'gig_id' => $gig->id,
                'code' => $this->nextOfferCode(),
                'title' => trim($payload['title']),
                'description' => trim((string) ($payload['description'] ?? '')),
                'price_cents' => $priceCents,
                'currency' => 'USD',
                'delivery_days' => (int) $payload['deliveryDays'],
                'revisions' => trim($payload['revisions']),
                'terms' => trim((string) ($payload['terms'] ?? '')),
                'status' => 'pending',
                'expires_at' => now()->addDays((int) ($payload['expiresInDays'] ?? 7))->endOfDay(),
                'metadata' => [
                    'source' => 'conversation',
                ],
            ]);

            $this->createOfferMessage(
                $conversation,
                $seller,
                $buyer,
                $offer,
                $seller->name.' sent a custom offer.',
            );

            $this->events->send(
                $buyer,
                'custom_offer_received',
                'Custom offer received',
                $seller->name.' sent you a custom offer for '.$offer->title.'.',
                '/dashboard/messages?conversation='.$conversation->public_id,
                ['conversationId' => $conversation->public_id, 'offerId' => $offer->id],
            );

            return $offer->fresh(['conversation.participants.user', 'gig', 'order']);
        });
    }

    public function accept(CustomOffer $offer, User $buyer): CustomOffer
    {
        $this->authorizeBuyer($offer, $buyer);
        $this->ensurePayable($offer);

        return DB::transaction(function () use ($offer, $buyer) {
            if ($offer->status === 'pending') {
                $offer->forceFill([
                    'status' => 'accepted',
                    'accepted_at' => now(),
                ])->save();

                $this->createStatusMessage(
                    $offer,
                    $buyer,
                    $offer->seller,
                    $buyer->name.' accepted the custom offer.',
                );

                if ($offer->seller) {
                    $this->events->send(
                        $offer->seller,
                        'custom_offer_accepted',
                        'Custom offer accepted',
                        $buyer->name.' accepted custom offer '.$offer->code.'.',
                        '/dashboard/seller/messages?conversation='.$offer->conversation?->public_id,
                        ['offerId' => $offer->id],
                    );
                }
            }

            return $offer->refresh()->load(['conversation.participants.user', 'gig', 'order']);
        });
    }

    public function pay(CustomOffer $offer, User $buyer): Order
    {
        $this->authorizeBuyer($offer, $buyer);
        $this->ensurePayable($offer);

        return DB::transaction(function () use ($offer, $buyer) {
            $offer = CustomOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            $this->ensurePayable($offer);

            $this->wallets->debit($buyer, $offer->price_cents, 'Custom offer payment '.$offer->code, [
                'customOfferId' => $offer->id,
                'customOfferCode' => $offer->code,
            ]);

            $gig = $offer->gig;
            $seller = $offer->seller;
            $hasRequirements = collect($gig?->requirements ?: [])->isNotEmpty();
            $order = Order::create([
                'code' => $this->nextOrderCode(),
                'buyer_id' => $buyer->id,
                'seller_id' => $offer->seller_id,
                'gig_id' => $offer->gig_id,
                'service' => $offer->title,
                'buyer_name' => $buyer->name,
                'seller_name' => $seller?->name ?: $offer->conversation?->seller_name,
                'status' => $hasRequirements ? 'Pending Requirements' : 'In Progress',
                'status_class' => $hasRequirements ? 'status-delivered' : 'status-progress',
                'due_date' => now()->addDays($offer->delivery_days)->toDateString(),
                'price_cents' => $offer->price_cents,
                'earnings_cents' => (int) round($offer->price_cents * 0.85),
                'metadata' => [
                    'itemSummary' => $offer->description ?: 'Custom offer',
                    'quantity' => 1,
                    'duration' => $offer->delivery_days.' day'.($offer->delivery_days === 1 ? '' : 's'),
                    'revisions' => $offer->revisions,
                    'customOfferCode' => $offer->code,
                    'customOfferTerms' => $offer->terms,
                    'requirements' => collect($gig?->requirements ?: [])
                        ->map(fn (array $item, int $index) => [
                            'id' => (string) ($item['id'] ?? 'requirement-'.$index),
                            'question' => $item['question'] ?? $item['label'] ?? 'Requirement',
                            'label' => $item['label'] ?? $item['question'] ?? 'Requirement',
                            'type' => $item['type'] ?? 'Free text',
                            'required' => (bool) ($item['required'] ?? false),
                            'optional' => ! (bool) ($item['required'] ?? false),
                            'options' => array_values($item['options'] ?? []),
                            'answer' => '',
                            'files' => [],
                        ])
                        ->values()
                        ->all(),
                ],
            ]);

            $order->activities()->create([
                'actor_id' => $buyer->id,
                'type' => 'custom_offer_paid',
                'title' => 'Custom offer paid',
                'detail' => $buyer->name.' paid custom offer '.$offer->code.' and created order #'.$order->code.'.',
                'metadata' => [
                    'custom_offer_id' => $offer->id,
                    'conversation_id' => $offer->conversation_id,
                ],
            ]);

            $offer->forceFill([
                'order_id' => $order->id,
                'status' => 'paid',
                'accepted_at' => $offer->accepted_at ?: now(),
                'paid_at' => now(),
            ])->save();

            $this->createStatusMessage(
                $offer->refresh(),
                $buyer,
                $seller,
                $buyer->name.' paid the custom offer. Order #'.$order->code.' is ready.',
            );

            if ($seller) {
                $this->events->send(
                    $seller,
                    'custom_offer_paid',
                    'Custom offer paid',
                    $buyer->name.' paid custom offer '.$offer->code.'. Order #'.$order->code.' is ready.',
                    '/dashboard/seller/orders/'.$order->code,
                    ['orderId' => $order->code, 'offerId' => $offer->id],
                );
            }

            $this->events->send(
                $buyer,
                'order_created',
                'Order created',
                'Your custom offer payment created order #'.$order->code.'. Submit requirements to help the seller start.',
                '/dashboard/orders/'.$order->code,
                ['orderId' => $order->code, 'offerId' => $offer->id],
            );

            return $order->fresh(['buyer', 'seller', 'gig', 'activities']);
        });
    }

    public function decline(CustomOffer $offer, User $buyer): CustomOffer
    {
        $this->authorizeBuyer($offer, $buyer);
        $this->ensurePending($offer, 'Only pending offers can be declined.');

        return $this->changeStatus($offer, $buyer, 'declined', 'declined_at', $buyer->name.' declined the custom offer.');
    }

    public function cancel(CustomOffer $offer, User $seller): CustomOffer
    {
        if ((int) $offer->seller_id !== (int) $seller->id) {
            throw new AuthorizationException('Only the seller can cancel this offer.');
        }

        $this->ensurePending($offer, 'Only pending offers can be cancelled.');

        return $this->changeStatus($offer, $seller, 'cancelled', 'cancelled_at', $seller->name.' cancelled the custom offer.');
    }

    public function conversationForResponse(CustomOffer $offer, User $viewer): Conversation
    {
        return $offer->conversation->fresh([
            'gig',
            'messages.attachments',
            'messages.customOffer.gig',
            'messages.customOffer.order',
            'messages.savedByUsers',
            'participants.user',
        ]);
    }

    private function createOfferMessage(
        Conversation $conversation,
        User $sender,
        ?User $recipient,
        CustomOffer $offer,
        string $body
    ): Message {
        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient?->id,
            'custom_offer_id' => $offer->id,
            'sender_name' => $sender->name,
            'body' => $body,
            'sent_at' => now(),
        ]);

        $this->touchConversation($conversation, $sender, $recipient, $message);

        return $message;
    }

    private function createStatusMessage(CustomOffer $offer, User $sender, ?User $recipient, string $body): Message
    {
        $conversation = $offer->conversation;
        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient?->id,
            'sender_name' => $sender->name,
            'body' => $body,
            'sent_at' => now(),
            'metadata' => [
                'custom_offer_id' => $offer->id,
                'custom_offer_status' => $offer->status,
            ],
        ]);

        $this->touchConversation($conversation, $sender, $recipient, $message);

        return $message;
    }

    private function touchConversation(Conversation $conversation, User $sender, ?User $recipient, Message $message): void
    {
        $conversation->forceFill(['last_message_at' => $message->sent_at])->save();
        $conversation->participants()
            ->where('user_id', $sender->id)
            ->update(['last_read_at' => now(), 'last_seen_at' => now()]);

        if ($recipient) {
            $conversation->participants()
                ->where('user_id', $recipient->id)
                ->increment('unread_count');

            $freshConversation = $conversation->fresh([
                'gig',
                'messages.attachments',
                'messages.customOffer.gig',
                'messages.customOffer.order',
                'messages.savedByUsers',
                'participants.user',
            ]);
            $message->load(['conversation', 'attachments', 'customOffer.gig', 'customOffer.order']);

            event(new MessageSent(
                $message,
                $recipient->id,
                $this->conversationPayloadForUser($freshConversation, $recipient),
            ));
            event(new ConversationUpdated(
                $freshConversation,
                $recipient->id,
                $this->conversationPayloadForUser($freshConversation, $recipient),
            ));
        }
    }

    private function changeStatus(CustomOffer $offer, User $actor, string $status, string $timestampColumn, string $message): CustomOffer
    {
        return DB::transaction(function () use ($offer, $actor, $status, $timestampColumn, $message) {
            $offer->forceFill([
                'status' => $status,
                $timestampColumn => now(),
            ])->save();

            $recipient = (int) $actor->id === (int) $offer->buyer_id ? $offer->seller : $offer->buyer;
            $this->createStatusMessage($offer->refresh(), $actor, $recipient, $message);

            if ($recipient) {
                $this->events->send(
                    $recipient,
                    'custom_offer_'.$status,
                    'Custom offer '.str($status)->replace('_', ' ')->title()->toString(),
                    $message,
                    '/dashboard/messages?conversation='.$offer->conversation?->public_id,
                    ['offerId' => $offer->id],
                );
            }

            return $offer->refresh()->load(['conversation.participants.user', 'gig', 'order']);
        });
    }

    private function sellerGig(User $seller, string $gigId): Gig
    {
        return Gig::query()
            ->where('seller_id', $seller->id)
            ->where(fn ($query) => $query->where('slug', $gigId)->orWhere('id', $gigId))
            ->firstOrFail();
    }

    private function authorizeParticipant(Conversation $conversation, User $user): void
    {
        if ($conversation->participants()->where('user_id', $user->id)->exists()) {
            return;
        }

        throw new AuthorizationException('You cannot manage this conversation.');
    }

    private function authorizeBuyer(CustomOffer $offer, User $buyer): void
    {
        if ((int) $offer->buyer_id === (int) $buyer->id) {
            return;
        }

        throw new AuthorizationException('Only the buyer can manage this custom offer action.');
    }

    private function ensurePayable(CustomOffer $offer): void
    {
        if ($offer->expires_at && $offer->expires_at->isPast() && $offer->status === 'pending') {
            $offer->forceFill(['status' => 'expired'])->save();
        }

        if (! $offer->isPayable()) {
            throw ValidationException::withMessages([
                'offer' => 'This custom offer is no longer available.',
            ]);
        }
    }

    private function ensurePending(CustomOffer $offer, string $message): void
    {
        if ($offer->status !== 'pending') {
            throw ValidationException::withMessages([
                'offer' => $message,
            ]);
        }
    }

    private function conversationPayloadForUser(Conversation $conversation, User $user): array
    {
        $request = request()->duplicate();
        $request->setUserResolver(fn () => $user);

        return ConversationResource::make($conversation)->resolve($request);
    }

    private function moneyToCents(mixed $value): int
    {
        return (int) round((float) preg_replace('/[^0-9.]/', '', (string) $value) * 100);
    }

    private function nextOfferCode(): string
    {
        do {
            $code = 'OFR-'.Str::upper(Str::random(8));
        } while (CustomOffer::where('code', $code)->exists());

        return $code;
    }

    private function nextOrderCode(): string
    {
        do {
            $code = 'CO-'.Str::upper(Str::random(8));
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
