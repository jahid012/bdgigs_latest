<?php

namespace App\Events;

use App\Models\ModerationReport;

class UserReported
{
    public function __construct(public ModerationReport $report)
    {
    }
}
