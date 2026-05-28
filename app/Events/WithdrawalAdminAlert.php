<?php

namespace App\Events;

use App\Models\WithdrawalRequest;

class WithdrawalAdminAlert
{
    public function __construct(public WithdrawalRequest $withdrawal)
    {
    }
}
