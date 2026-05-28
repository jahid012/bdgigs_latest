<?php

namespace App\Events;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SellerStartedWorking
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public User $seller,
    ) {
    }
}
