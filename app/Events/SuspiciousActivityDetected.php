<?php

namespace App\Events;

use App\Models\SuspiciousActivityLog;

class SuspiciousActivityDetected
{
    public function __construct(public SuspiciousActivityLog $activity)
    {
    }
}
