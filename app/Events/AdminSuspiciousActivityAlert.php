<?php

namespace App\Events;

use App\Models\SuspiciousActivityLog;

class AdminSuspiciousActivityAlert
{
    public function __construct(public SuspiciousActivityLog $activity)
    {
    }
}
