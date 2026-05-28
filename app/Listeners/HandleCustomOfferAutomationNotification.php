<?php

namespace App\Listeners;

use App\Events\CustomOfferExpired;
use App\Events\CustomOfferPaymentFailed;
use App\Models\CustomOffer;
use App\Models\User;
use App\Services\OrderEventNotificationService;

class HandleCustomOfferAutomationNotification
{
    public function __construct(private readonly OrderEventNotificationService $events)
    {
    }

    public function handle(object $event): void
    {
        if ($event instanceof CustomOfferExpired) {
            $offer = $event->offer->fresh(['buyer', 'seller', 'conversation']);
            $offer->buyer && $this->send($offer, $offer->buyer, 'custom_offer_expired', 'Custom offer expired', 'Custom offer '.$offer->code.' has expired.');
            $offer->seller && $this->send($offer, $offer->seller, 'custom_offer_expired', 'Custom offer expired', 'Custom offer '.$offer->code.' expired before payment.');
        }

        if ($event instanceof CustomOfferPaymentFailed) {
            $offer = $event->offer->fresh(['buyer', 'seller', 'conversation']);
            $offer->buyer && $this->send($offer, $offer->buyer, 'custom_offer_payment_failed', 'Custom offer payment failed', $event->reason);
            $offer->seller && $this->send($offer, $offer->seller, 'custom_offer_payment_failed', 'Custom offer payment failed', 'Payment failed for custom offer '.$offer->code.'.');
        }
    }

    private function send(CustomOffer $offer, User $recipient, string $type, string $title, string $detail): void
    {
        $this->events->send(
            $recipient,
            $type,
            $title,
            $detail,
            '/dashboard/messages?conversation='.$offer->conversation?->public_id,
            [
                'preferenceKey' => 'inboxMessages',
                'conversationId' => $offer->conversation?->public_id,
                'offerId' => $offer->id,
                'customOfferTitle' => $offer->title,
                'custom_offer_title' => $offer->title,
                'custom_offer_price' => '$'.number_format($offer->price_cents / 100, 2),
                'emailTemplate' => $type,
            ],
        );
    }
}
