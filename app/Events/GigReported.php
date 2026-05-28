<?php

namespace App\Events;

use App\Models\ModerationReport;

class GigReported
{
    public function __construct(public ModerationReport $report)
    {
    }
}
