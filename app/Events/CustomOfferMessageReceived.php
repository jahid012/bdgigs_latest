<?php

namespace App\Events;

use App\Models\CustomOffer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomOfferMessageReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CustomOffer $offer,
        public Message $message,
        public User $recipient,
    ) {
    }
}
