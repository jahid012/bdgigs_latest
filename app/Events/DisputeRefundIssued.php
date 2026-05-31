<?php

namespace App\Events;

use App\Models\Admin;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;

class DisputeRefundIssued
{
    public function __construct(public Dispute $dispute, public Order $order, public User|Admin|null $actor, public int $amountCents)
    {
    }
}
