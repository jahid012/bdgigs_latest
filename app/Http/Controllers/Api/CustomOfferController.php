<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCustomOfferRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\CustomOfferResource;
use App\Models\Conversation;
use App\Models\CustomOffer;
use App\Models\Gig;
use App\Services\CustomOfferService;
use Illuminate\Http\Request;

class CustomOfferController extends Controller
{
    public function options(Request $request, Conversation $conversation): array
    {
        $this->authorizeConversation($request, $conversation);

        abort_unless((int) $conversation->seller_id === (int) $request->user()->id, 403);

        return [
            'data' => Gig::query()
                ->where('seller_id', $request->user()->id)
                ->whereIn('status', ['Published', 'Live'])
                ->latest()
                ->get()
                ->map(fn (Gig $gig) => [
                    'id' => $gig->slug,
                    'title' => $gig->title,
                    'image' => $gig->image,
                    'price' => '$'.number_format($gig->price_cents / 100, 0),
                    'priceValue' => round($gig->price_cents / 100, 2),
                    'deliveryDays' => $gig->delivery_days ?: 3,
                ])
                ->values(),
        ];
    }

    public function store(
        StoreCustomOfferRequest $request,
        Conversation $conversation,
        CustomOfferService $offers
    ): array {
        $offer = $offers->create($conversation, $request->user(), $request->validated());

        return $this->offerResponse($request, $offers, $offer);
    }

    public function accept(Request $request, CustomOffer $customOffer, CustomOfferService $offers): array
    {
        $offer = $offers->accept($customOffer->loadMissing(['buyer', 'seller', 'conversation']), $request->user());

        return $this->offerResponse($request, $offers, $offer);
    }

    public function pay(Request $request, CustomOffer $customOffer, CustomOfferService $offers): array
    {
        $order = $offers->pay($customOffer->loadMissing(['buyer', 'seller', 'gig', 'conversation']), $request->user());
        $offer = $customOffer->refresh()->load(['conversation.participants.user', 'gig', 'order']);

        return [
            'data' => [
                ...$this->offerResponse($request, $offers, $offer)['data'],
                'order' => [
                    'code' => $order->code,
                    'path' => '/dashboard/orders/'.$order->code,
                ],
            ],
        ];
    }

    public function decline(Request $request, CustomOffer $customOffer, CustomOfferService $offers): array
    {
        $offer = $offers->decline($customOffer->loadMissing(['buyer', 'seller', 'conversation']), $request->user());

        return $this->offerResponse($request, $offers, $offer);
    }

    public function cancel(Request $request, CustomOffer $customOffer, CustomOfferService $offers): array
    {
        $offer = $offers->cancel($customOffer->loadMissing(['buyer', 'seller', 'conversation']), $request->user());

        return $this->offerResponse($request, $offers, $offer);
    }

    private function offerResponse(Request $request, CustomOfferService $offers, CustomOffer $offer): array
    {
        $conversation = $offers->conversationForResponse($offer, $request->user());

        return [
            'data' => [
                'offer' => CustomOfferResource::make($offer->loadMissing(['gig', 'order']))->resolve($request),
                'conversation' => ConversationResource::make($conversation)->resolve($request),
            ],
        ];
    }

    private function authorizeConversation(Request $request, Conversation $conversation): void
    {
        abort_unless(
            $conversation->participants()
                ->where('user_id', $request->user()->id)
                ->exists(),
            403,
        );
    }
}
