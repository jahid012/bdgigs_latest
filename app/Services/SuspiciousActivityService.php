<?php

namespace App\Services;

use App\Events\AdminSuspiciousActivityAlert;
use App\Events\SuspiciousActivityDetected;
use App\Models\SuspiciousActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuspiciousActivityService
{
    public function log(
        ?User $user,
        string $type,
        string $severity,
        string $description,
        array $metadata = [],
        ?Request $request = null,
    ): SuspiciousActivityLog {
        return DB::transaction(function () use ($user, $type, $severity, $description, $metadata, $request) {
            $activity = SuspiciousActivityLog::create([
                'user_id' => $user?->id,
                'type' => $type,
                'severity' => $severity,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'description' => $description,
                'metadata' => $metadata,
            ]);

            DB::afterCommit(function () use ($activity) {
                event(new SuspiciousActivityDetected($activity->fresh(['user'])));

                if (in_array($activity->severity, ['high', 'critical'], true)) {
                    event(new AdminSuspiciousActivityAlert($activity->fresh(['user'])));
                }
            });

            return $activity->fresh(['user']);
        });
    }

    public function detectFailedLogin(User|string|null $user, Request $request, int $attempts): ?SuspiciousActivityLog
    {
        if ($attempts < 5) {
            return null;
        }

        return $this->log(
            $user instanceof User ? $user : null,
            'failed_login_spike',
            $attempts >= 10 ? 'critical' : 'high',
            'Multiple failed login attempts were detected.',
            ['attempts' => $attempts, 'email' => is_string($user) ? $user : $user?->email],
            $request,
        );
    }
}
