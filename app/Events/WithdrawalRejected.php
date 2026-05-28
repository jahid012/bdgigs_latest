<?php

namespace App\Events;

use App\Models\WithdrawalRequest;
use App\Models\User;

class WithdrawalRejected
{
    public function __construct(public WithdrawalRequest $withdrawal, public ?User $admin = null)
    {
    }
}
