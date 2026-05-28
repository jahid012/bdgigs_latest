<?php

namespace App\Events;

use App\Models\User;

class SellerApplicationSubmitted
{
    public function __construct(public User $seller)
    {
    }
}
