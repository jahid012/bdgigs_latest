<?php

namespace App\Events;

use App\Models\CustomOffer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomOfferExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(public CustomOffer $offer)
    {
    }
}
