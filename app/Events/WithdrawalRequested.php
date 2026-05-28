<?php

namespace App\Events;

use App\Models\WithdrawalRequest;

class WithdrawalRequested
{
    public function __construct(public WithdrawalRequest $withdrawal)
    {
    }
}
