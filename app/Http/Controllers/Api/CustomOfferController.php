<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCustomOfferRequest;
use App\Http\Resources\CustomOfferActionResource;
use App\Http\Resources\CustomOfferGigOptionResource;
use App\Models\Conversation;
use App\Models\CustomOffer;
use App\Models\Gig;
use App\Services\CustomOfferService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomOfferController extends Controller
{
    public function options(Request $request, Conversation $conversation): AnonymousResourceCollection
    {
        $this->authorizeConversation($request, $conversation);

        abort_unless((int) $conversation->seller_id === (int) $request->user()->id, 403);

        return CustomOfferGigOptionResource::collection(
            Gig::query()
                ->where('seller_id', $request->user()->id)
                ->whereIn('status', ['Published', 'Live'])
                ->latest()
                ->get()
        );
    }

    public function store(
        StoreCustomOfferRequest $request,
        Conversation $conversation,
        CustomOfferService $offers
    ): CustomOfferActionResource {
        $offer = $offers->create($conversation, $request->user(), $request->validated());

        return $this->offerResponse($request, $offers, $offer);
    }

    public function accept(Request $request, CustomOffer $customOffer, CustomOfferService $offers): CustomOfferActionResource
    {
        $offer = $offers->accept($customOffer->loadMissing(['buyer', 'seller', 'conversation']), $request->user());

        return $this->offerResponse($request, $offers, $offer);
    }

    public function pay(Request $request, CustomOffer $customOffer, CustomOfferService $offers): CustomOfferActionResource
    {
        $order = $offers->pay($customOffer->loadMissing(['buyer', 'seller', 'gig', 'conversation']), $request->user());
        $offer = $customOffer->refresh()->load(['conversation.participants.user', 'gig', 'order']);

        return $this->offerResponse($request, $offers, $offer, $order);
    }

    public function decline(Request $request, CustomOffer $customOffer, CustomOfferService $offers): CustomOfferActionResource
    {
        $offer = $offers->decline($customOffer->loadMissing(['buyer', 'seller', 'conversation']), $request->user());

        return $this->offerResponse($request, $offers, $offer);
    }

    public function cancel(Request $request, CustomOffer $customOffer, CustomOfferService $offers): CustomOfferActionResource
    {
        $offer = $offers->cancel($customOffer->loadMissing(['buyer', 'seller', 'conversation']), $request->user());

        return $this->offerResponse($request, $offers, $offer);
    }

    private function offerResponse(
        Request $request,
        CustomOfferService $offers,
        CustomOffer $offer,
        mixed $order = null
    ): CustomOfferActionResource
    {
        $conversation = $offers->conversationForResponse($offer, $request->user());

        return CustomOfferActionResource::make([
            'offer' => $offer,
            'conversation' => $conversation,
            'order' => $order,
        ]);
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
