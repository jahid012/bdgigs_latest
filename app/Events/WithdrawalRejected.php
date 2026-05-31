<?php

namespace App\Events;

use App\Models\Admin;
use App\Models\User;
use App\Models\WithdrawalRequest;

class WithdrawalRejected
{
    public function __construct(public WithdrawalRequest $withdrawal, public User|Admin|null $admin = null)
    {
    }
}
