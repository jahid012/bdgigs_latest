<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order, public string $reason = '')
    {
    }
}
