<?php

namespace App\Events;

use App\Models\User;

class GigPerformanceSummary
{
    public function __construct(public User $seller, public array $summary = [])
    {
    }
}
