<?php

namespace App\Events;

use App\Models\Gig;
use App\Models\Message;

class GigInquiryReceived
{
    public function __construct(public Gig $gig, public Message $message)
    {
    }
}
