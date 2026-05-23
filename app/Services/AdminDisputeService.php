<?php

namespace App\Services;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminDisputeService
{
    public function openForOrder(Order $order, User $actor, array $payload): Dispute
    {
        return DB::transaction(function () use ($order, $actor, $payload) {
            $dispute = $order->disputes()->create([
                'case_code' => $this->caseCode(),
                'opened_by_id' => $actor->id,
                'reason' => $payload['reason'],
                'description' => $payload['description'] ?? null,
                'priority' => $payload['priority'],
                'status' => 'open',
                'metadata' => ['source' => 'admin_order_detail'],
            ]);

            $dispute->activities()->create([
                'actor_id' => $actor->id,
                'type' => 'opened',
                'title' => 'Dispute opened',
                'detail' => $payload['description'] ?? $payload['reason'],
            ]);

            return $dispute->fresh(['order', 'openedBy', 'activities.actor']);
        });
    }

    public function update(Dispute $dispute, User $actor, array $payload): Dispute
    {
        return DB::transaction(function () use ($dispute, $actor, $payload) {
            $this->ensureAdminAssignee($payload['assigned_to_id'] ?? null);

            $previous = [
                'status' => $dispute->status,
                'priority' => $dispute->priority,
                'assigned_to_id' => $dispute->assigned_to_id,
            ];
            $terminal = in_array($payload['status'], ['resolved', 'closed'], true);

            $dispute->forceFill([
                'status' => $payload['status'],
                'priority' => $payload['priority'],
                'assigned_to_id' => $payload['assigned_to_id'] ?? null,
                'resolution' => $payload['resolution'] ?? $dispute->resolution,
                'resolved_by_id' => $terminal ? $actor->id : null,
                'resolved_at' => $terminal ? now() : null,
            ])->save();

            $dispute->activities()->create([
                'actor_id' => $actor->id,
                'type' => $terminal ? 'resolved' : 'updated',
                'title' => $terminal ? 'Dispute '.$payload['status'] : 'Dispute updated',
                'detail' => $payload['note'] ?? $this->changeSummary($previous, $payload),
                'metadata' => [
                    'status' => [$previous['status'], $payload['status']],
                    'priority' => [$previous['priority'], $payload['priority']],
                    'assignedTo' => [$previous['assigned_to_id'], $payload['assigned_to_id'] ?? null],
                ],
            ]);

            return $dispute->fresh(['assignedTo', 'resolvedBy', 'activities.actor']);
        });
    }

    private function caseCode(): string
    {
        do {
            $code = 'DSP-'.Str::upper(Str::random(8));
        } while (Dispute::where('case_code', $code)->exists());

        return $code;
    }

    private function ensureAdminAssignee(?int $assigneeId): void
    {
        if (! $assigneeId) {
            return;
        }

        if (! User::find($assigneeId)?->can('admin.access')) {
            throw ValidationException::withMessages([
                'assigned_to_id' => 'Disputes can only be assigned to admin panel users.',
            ]);
        }
    }

    private function changeSummary(array $previous, array $payload): string
    {
        return 'Status '.$previous['status'].' to '.$payload['status']
            .'; priority '.$previous['priority'].' to '.$payload['priority'].'.';
    }
}
