<?php

namespace App\Events;

use App\Models\ModerationReport;

class MessageReported
{
    public function __construct(public ModerationReport $report)
    {
    }
}
