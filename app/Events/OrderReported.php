<?php

namespace App\Events;

use App\Models\ModerationReport;

class OrderReported
{
    public function __construct(public ModerationReport $report)
    {
    }
}
