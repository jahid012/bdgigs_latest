<?php

namespace App\Events;

use App\Models\OrderCancellation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCancellationRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(public OrderCancellation $cancellation)
    {
    }
}
