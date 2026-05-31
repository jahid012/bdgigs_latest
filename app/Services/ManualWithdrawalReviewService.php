<?php

namespace App\Services;

use App\Events\WithdrawalApproved;
use App\Events\WithdrawalFailed;
use App\Events\WithdrawalPaid;
use App\Events\WithdrawalRejected;
use App\Models\Admin;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;

class ManualWithdrawalReviewService
{
    public function decide(
        WithdrawalRequest $withdrawal,
        Admin $admin,
        string $action,
        ?string $note,
        ?string $paymentReference = null,
    ): WithdrawalRequest {
        return DB::transaction(function () use ($withdrawal, $admin, $action, $note, $paymentReference) {
            return match ($action) {
                'approve' => $this->approve($withdrawal, $admin, $note),
                'reject' => $this->reject($withdrawal, $admin, $note),
                'mark_paid' => $this->markPaid($withdrawal, $admin, $note, $paymentReference),
                'mark_failed' => $this->markFailed($withdrawal, $admin, $note),
            };
        });
    }

    private function approve(WithdrawalRequest $withdrawal, Admin $admin, ?string $note): WithdrawalRequest
    {
        abort_unless(in_array($withdrawal->status, ['pending', 'under_review'], true), 422, 'Only pending withdrawals can be approved.');

        $withdrawal->forceFill([
            'status' => 'approved',
            'approved_amount_cents' => $withdrawal->amount_cents,
            'review_note' => $note,
            'reviewed_by' => null,
            'reviewed_by_admin_id' => $admin->id,
            'reviewed_at' => now(),
        ])->save();

        return $this->record(
            $withdrawal,
            $admin,
            'approved',
            'Withdrawal approved',
            $note ?: 'Finance approved this manual withdrawal request.',
        );
    }

    private function reject(WithdrawalRequest $withdrawal, Admin $admin, ?string $note): WithdrawalRequest
    {
        abort_unless(in_array($withdrawal->status, ['pending', 'under_review', 'approved'], true), 422, 'This withdrawal cannot be rejected.');

        $withdrawal->forceFill([
            'status' => 'rejected',
            'review_note' => $note,
            'reviewed_by' => null,
            'reviewed_by_admin_id' => $admin->id,
            'reviewed_at' => now(),
        ])->save();

        return $this->record(
            $withdrawal,
            $admin,
            'rejected',
            'Withdrawal rejected',
            $note ?: 'Finance rejected this manual withdrawal request.',
        );
    }

    private function markPaid(
        WithdrawalRequest $withdrawal,
        Admin $admin,
        ?string $note,
        ?string $paymentReference
    ): WithdrawalRequest {
        abort_unless($withdrawal->status === 'approved', 422, 'Approve the withdrawal before marking it paid.');
        abort_unless(filled($paymentReference), 422, 'Add a manual payout reference before marking paid.');

        $withdrawal->forceFill([
            'status' => 'paid',
            'payment_reference' => $paymentReference,
            'review_note' => $note ?: $withdrawal->review_note,
            'paid_by' => null,
            'paid_by_admin_id' => $admin->id,
            'paid_at' => now(),
        ])->save();

        return $this->record(
            $withdrawal,
            $admin,
            'paid',
            'Withdrawal marked paid',
            $note ?: 'Finance recorded the manual payout reference.',
        );
    }

    private function markFailed(WithdrawalRequest $withdrawal, Admin $admin, ?string $note): WithdrawalRequest
    {
        abort_unless(in_array($withdrawal->status, ['approved', 'paid'], true), 422, 'Only approved or paid withdrawals can be marked failed.');

        $withdrawal->forceFill([
            'status' => 'failed',
            'review_note' => $note ?: $withdrawal->review_note,
            'reviewed_by' => null,
            'reviewed_by_admin_id' => $admin->id,
            'reviewed_at' => now(),
        ])->save();

        return $this->record(
            $withdrawal,
            $admin,
            'failed',
            'Withdrawal marked failed',
            $note ?: 'Finance marked this manual payout as failed.',
        );
    }

    private function record(
        WithdrawalRequest $withdrawal,
        Admin $admin,
        string $type,
        string $title,
        string $detail,
    ): WithdrawalRequest {
        $withdrawal->activities()->create([
            'actor_admin_id' => $admin->id,
            'type' => $type,
            'title' => $title,
            'detail' => $detail,
        ]);

        DB::afterCommit(function () use ($withdrawal, $admin) {
            $fresh = $withdrawal->fresh(['seller', 'payoutMethod', 'reviewer', 'adminReviewer', 'payer', 'adminPayer', 'activities.adminActor']);

            match ($fresh->status) {
                'approved' => event(new WithdrawalApproved($fresh, $admin)),
                'rejected' => event(new WithdrawalRejected($fresh, $admin)),
                'paid' => event(new WithdrawalPaid($fresh, $admin)),
                'failed' => event(new WithdrawalFailed($fresh, $admin, $fresh->review_note)),
                default => null,
            };
        });

        return $withdrawal->refresh(['seller', 'payoutMethod', 'reviewer', 'adminReviewer', 'payer', 'adminPayer', 'activities.adminActor']);
    }
}
