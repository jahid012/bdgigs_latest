<?php

namespace App\Services;

use App\Events\DisputeAdminJoined;
use App\Events\DisputeClosed;
use App\Events\DisputeEvidenceRequested;
use App\Events\DisputeOpened;
use App\Events\DisputeRefundIssued;
use App\Events\DisputeRejected;
use App\Events\DisputeResolved;
use App\Events\DisputeStatusUpdated;
use App\Models\Admin;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminDisputeService
{
    public function __construct(private readonly OrderPaymentLifecycleService $payments)
    {
    }

    public function openForOrder(Order $order, Admin $actor, array $payload): Dispute
    {
        return DB::transaction(function () use ($order, $actor, $payload) {
            $dispute = $order->disputes()->create([
                'case_code' => $this->caseCode(),
                'opened_by_admin_id' => $actor->id,
                'reason' => $payload['reason'],
                'description' => $payload['description'] ?? null,
                'priority' => $payload['priority'],
                'status' => 'open',
                'metadata' => ['source' => 'admin_order_detail'],
            ]);

            $dispute->activities()->create([
                'actor_admin_id' => $actor->id,
                'type' => 'opened',
                'title' => 'Dispute opened',
                'detail' => $payload['description'] ?? $payload['reason'],
            ]);

            $order->activities()->create([
                'actor_admin_id' => $actor->id,
                'type' => 'resolution_opened',
                'title' => 'Resolution Center case opened',
                'detail' => 'Admin opened '.$dispute->case_code.' for '.$payload['reason'].'.',
            ]);

            DB::afterCommit(fn () => event(new DisputeOpened($dispute->fresh(['order.buyer', 'order.seller', 'openedBy', 'openedByAdmin']))));

            return $dispute->fresh(['order', 'openedBy', 'openedByAdmin', 'activities.actor', 'activities.adminActor']);
        });
    }

    public function update(Dispute $dispute, Admin $actor, array $payload): Dispute
    {
        return DB::transaction(function () use ($dispute, $actor, $payload) {
            $this->ensureAdminAssignee($payload['assigned_to_id'] ?? null);

            $previous = [
                'status' => $dispute->status,
                'priority' => $dispute->priority,
                'assigned_to_admin_id' => $dispute->assigned_to_admin_id,
            ];
            $terminal = in_array($payload['status'], ['resolved', 'rejected', 'closed'], true);

            $dispute->forceFill([
                'status' => $payload['status'],
                'priority' => $payload['priority'],
                'assigned_to_id' => null,
                'assigned_to_admin_id' => $payload['assigned_to_id'] ?? null,
                'resolution' => $payload['resolution'] ?? $dispute->resolution,
                'resolved_by_id' => null,
                'resolved_by_admin_id' => $terminal ? $actor->id : null,
                'resolved_at' => $terminal ? now() : null,
            ])->save();

            $dispute->activities()->create([
                'actor_admin_id' => $actor->id,
                'type' => $terminal ? 'resolved' : 'updated',
                'title' => $terminal ? 'Dispute '.$payload['status'] : 'Dispute updated',
                'detail' => $payload['note'] ?? $this->changeSummary($previous, $payload),
                'metadata' => [
                    'status' => [$previous['status'], $payload['status']],
                    'priority' => [$previous['priority'], $payload['priority']],
                    'assignedTo' => [$previous['assigned_to_admin_id'], $payload['assigned_to_id'] ?? null],
                ],
            ]);

            $dispute->order?->activities()->create([
                'actor_admin_id' => $actor->id,
                'type' => 'resolution_status_updated',
                'title' => 'Resolution Center status updated',
                'detail' => 'Dispute '.$dispute->case_code.' changed from '.$previous['status'].' to '.$payload['status'].'.',
            ]);

            DB::afterCommit(function () use ($dispute, $actor, $previous, $payload) {
                $fresh = $dispute->fresh(['order.buyer', 'order.seller', 'openedBy', 'openedByAdmin']);

                event(new DisputeStatusUpdated($fresh, $actor, $previous['status']));

                match ($payload['status']) {
                    'resolved' => event(new DisputeResolved($fresh, $actor)),
                    'rejected' => event(new DisputeRejected($fresh, $actor)),
                    'closed' => event(new DisputeClosed($fresh, $actor)),
                    default => null,
                };
            });

            return $dispute->fresh(['assignedAdmin', 'resolvedByAdmin', 'activities.actor', 'activities.adminActor']);
        });
    }

    public function join(Dispute $dispute, Admin $admin, ?string $note = null): Dispute
    {
        return DB::transaction(function () use ($dispute, $admin, $note) {
            $previous = $dispute->status;

            $dispute->forceFill([
                'status' => 'under_admin_review',
                'assigned_to_admin_id' => $dispute->assigned_to_admin_id ?: $admin->id,
            ])->save();

            $dispute->activities()->create([
                'actor_admin_id' => $admin->id,
                'type' => 'admin_joined',
                'title' => 'Admin joined dispute',
                'detail' => $note ?: 'Support joined the Resolution Center case.',
            ]);

            $dispute->order?->activities()->create([
                'actor_admin_id' => $admin->id,
                'type' => 'resolution_admin_joined',
                'title' => 'Admin joined dispute',
                'detail' => $note ?: 'Support joined the Resolution Center case.',
            ]);

            DB::afterCommit(function () use ($dispute, $admin, $previous) {
                $fresh = $dispute->fresh(['order.buyer', 'order.seller']);
                event(new DisputeAdminJoined($fresh, $admin));
                event(new DisputeStatusUpdated($fresh, $admin, $previous));
            });

            return $dispute->fresh(['assignedAdmin', 'activities.actor', 'activities.adminActor']);
        });
    }

    public function requestEvidence(Dispute $dispute, Admin $admin, ?User $recipient, string $note): Dispute
    {
        return DB::transaction(function () use ($dispute, $admin, $recipient, $note) {
            $previous = $dispute->status;

            $dispute->forceFill([
                'status' => 'evidence_requested',
                'assigned_to_admin_id' => $dispute->assigned_to_admin_id ?: $admin->id,
            ])->save();

            $dispute->activities()->create([
                'actor_admin_id' => $admin->id,
                'type' => 'evidence_requested',
                'title' => 'Evidence requested',
                'detail' => $note,
                'metadata' => ['recipient_id' => $recipient?->id],
            ]);

            $dispute->order?->activities()->create([
                'actor_admin_id' => $admin->id,
                'type' => 'resolution_evidence_requested',
                'title' => 'Dispute evidence requested',
                'detail' => $note,
            ]);

            DB::afterCommit(function () use ($dispute, $admin, $recipient, $note, $previous) {
                $fresh = $dispute->fresh(['order.buyer', 'order.seller']);
                event(new DisputeEvidenceRequested($fresh, $admin, $recipient, $note));
                event(new DisputeStatusUpdated($fresh, $admin, $previous));
            });

            return $dispute->fresh(['assignedAdmin', 'activities.actor', 'activities.adminActor']);
        });
    }

    public function issueRefund(Dispute $dispute, Admin $admin, int $amountCents, ?string $reason = null): Order
    {
        $order = $this->payments->refund(
            $dispute->order->loadMissing(['buyer', 'seller', 'gig']),
            $admin,
            $amountCents,
            $reason ?: 'Refund issued from dispute '.$dispute->case_code,
        );

        $dispute->forceFill([
            'status' => 'resolved',
            'resolution' => $reason ?: 'Refund issued.',
            'resolved_by_id' => null,
            'resolved_by_admin_id' => $admin->id,
            'resolved_at' => now(),
        ])->save();

        $dispute->activities()->create([
            'actor_admin_id' => $admin->id,
            'type' => 'refund_issued',
            'title' => 'Refund issued',
            'detail' => $reason ?: 'A refund was issued from this dispute decision.',
            'metadata' => ['amount_cents' => $amountCents],
        ]);

        $order->activities()->create([
            'actor_admin_id' => $admin->id,
            'type' => 'resolution_refund_issued',
            'title' => 'Dispute refund issued',
            'detail' => 'A refund of $'.number_format($amountCents / 100, 2).' was issued from '.$dispute->case_code.'.',
        ]);

        event(new DisputeRefundIssued($dispute->fresh(['order.buyer', 'order.seller']), $order, $admin, $amountCents));

        return $order;
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

        if (! Admin::find($assigneeId)?->can('admin.access')) {
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
