<?php

namespace App\Events;

use App\Models\ModerationReport;
use App\Models\User;

class ReportStatusUpdated
{
    public function __construct(public ModerationReport $report, public User $admin, public string $previousStatus)
    {
    }
}
