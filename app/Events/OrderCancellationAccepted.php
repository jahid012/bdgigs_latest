<?php

namespace App\Events;

use App\Models\OrderCancellation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCancellationAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(public OrderCancellation $cancellation)
    {
    }
}
