<?php

namespace App\Events;

use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public WalletTransaction $transaction,
        public int $amountCents,
        public ?User $actor = null,
        public ?string $reason = null,
    ) {
    }
}
