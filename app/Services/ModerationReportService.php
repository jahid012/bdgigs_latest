<?php

namespace App\Services;

use App\Events\GigReported;
use App\Events\MessageReported;
use App\Events\OrderReported;
use App\Events\ReportStatusUpdated;
use App\Events\UserReported;
use App\Models\Admin;
use App\Models\Gig;
use App\Models\Message;
use App\Models\ModerationReport;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModerationReportService
{
    public function create(User $reporter, string $type, int|string $targetId, string $reason, ?string $description = null): ModerationReport
    {
        $target = $this->target($type, $targetId);
        $reportedUserId = $this->reportedUserId($type, $target);

        return DB::transaction(function () use ($reporter, $type, $target, $reportedUserId, $reason, $description) {
            $report = ModerationReport::create([
                'code' => $this->code(),
                'reporter_id' => $reporter->id,
                'reported_user_id' => $reportedUserId,
                'reportable_type' => $target::class,
                'reportable_id' => $target->getKey(),
                'type' => $type,
                'status' => 'pending',
                'reason' => $reason,
                'description' => $description,
            ]);

            DB::afterCommit(fn () => match ($type) {
                'user' => event(new UserReported($report->fresh(['reporter', 'reportedUser']))),
                'gig' => event(new GigReported($report->fresh(['reporter', 'reportedUser']))),
                'order' => event(new OrderReported($report->fresh(['reporter', 'reportedUser']))),
                default => event(new MessageReported($report->fresh(['reporter', 'reportedUser']))),
            });

            return $report->fresh(['reporter', 'reportedUser']);
        });
    }

    public function updateStatus(ModerationReport $report, Admin $admin, string $status, ?string $note = null): ModerationReport
    {
        $previous = $report->status;

        $report->forceFill([
            'status' => $status,
            'assigned_to_id' => null,
            'assigned_to_admin_id' => $report->assigned_to_admin_id ?: $admin->id,
            'resolved_by_id' => null,
            'resolved_by_admin_id' => in_array($status, ['resolved', 'rejected'], true) ? $admin->id : null,
            'resolution_note' => $note,
            'resolved_at' => in_array($status, ['resolved', 'rejected'], true) ? now() : null,
        ])->save();

        event(new ReportStatusUpdated($report->fresh(['reporter', 'reportedUser', 'resolvedBy', 'resolvedByAdmin']), $admin, $previous));

        return $report->fresh(['reporter', 'reportedUser', 'assignedAdmin', 'resolvedBy', 'resolvedByAdmin']);
    }

    private function target(string $type, int|string $targetId): Model
    {
        return match ($type) {
            'user' => User::findOrFail($targetId),
            'gig' => Gig::query()->where('id', $targetId)->orWhere('slug', $targetId)->firstOrFail(),
            'order' => Order::query()->where('id', $targetId)->orWhere('code', $targetId)->firstOrFail(),
            'message' => Message::findOrFail($targetId),
        };
    }

    private function reportedUserId(string $type, Model $target): ?int
    {
        return match ($type) {
            'user' => $target->id,
            'gig' => $target->seller_id,
            'order' => $target->seller_id,
            'message' => $target->sender_id,
            default => null,
        };
    }

    private function code(): string
    {
        do {
            $code = 'RPT-'.Str::upper(Str::random(8));
        } while (ModerationReport::where('code', $code)->exists());

        return $code;
    }
}
