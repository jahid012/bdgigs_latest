<?php

namespace App\Events;

use App\Models\CustomOffer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomOfferPaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CustomOffer $offer,
        public string $reason,
    ) {
    }
}
