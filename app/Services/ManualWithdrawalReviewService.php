<?php

namespace App\Services;

use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Support\MarketplaceNotifier;
use Illuminate\Support\Facades\DB;

class ManualWithdrawalReviewService
{
    public function __construct(private readonly MarketplaceNotifier $notifier)
    {
    }

    public function decide(
        WithdrawalRequest $withdrawal,
        User $admin,
        string $action,
        ?string $note,
        ?string $paymentReference = null,
    ): WithdrawalRequest {
        return DB::transaction(function () use ($withdrawal, $admin, $action, $note, $paymentReference) {
            return match ($action) {
                'approve' => $this->approve($withdrawal, $admin, $note),
                'reject' => $this->reject($withdrawal, $admin, $note),
                'mark_paid' => $this->markPaid($withdrawal, $admin, $note, $paymentReference),
            };
        });
    }

    private function approve(WithdrawalRequest $withdrawal, User $admin, ?string $note): WithdrawalRequest
    {
        abort_unless(in_array($withdrawal->status, ['pending', 'under_review'], true), 422, 'Only pending withdrawals can be approved.');

        $withdrawal->forceFill([
            'status' => 'approved',
            'approved_amount_cents' => $withdrawal->amount_cents,
            'review_note' => $note,
            'reviewed_by' => $admin->id,
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

    private function reject(WithdrawalRequest $withdrawal, User $admin, ?string $note): WithdrawalRequest
    {
        abort_unless(in_array($withdrawal->status, ['pending', 'under_review', 'approved'], true), 422, 'This withdrawal cannot be rejected.');

        $withdrawal->forceFill([
            'status' => 'rejected',
            'review_note' => $note,
            'reviewed_by' => $admin->id,
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
        User $admin,
        ?string $note,
        ?string $paymentReference
    ): WithdrawalRequest {
        abort_unless($withdrawal->status === 'approved', 422, 'Approve the withdrawal before marking it paid.');
        abort_unless(filled($paymentReference), 422, 'Add a manual payout reference before marking paid.');

        $withdrawal->forceFill([
            'status' => 'paid',
            'payment_reference' => $paymentReference,
            'review_note' => $note ?: $withdrawal->review_note,
            'paid_by' => $admin->id,
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

    private function record(
        WithdrawalRequest $withdrawal,
        User $admin,
        string $type,
        string $title,
        string $detail,
    ): WithdrawalRequest {
        $withdrawal->activities()->create([
            'actor_id' => $admin->id,
            'type' => $type,
            'title' => $title,
            'detail' => $detail,
        ]);

        $this->notifier->notify(
            $withdrawal->seller,
            'Withdrawal',
            $title,
            $withdrawal->code.': '.$detail,
            '/dashboard/seller/earnings',
            ['withdrawal' => $withdrawal->code, 'status' => $withdrawal->status],
        );

        return $withdrawal->refresh(['seller', 'payoutMethod', 'reviewer', 'payer', 'activities']);
    }
}
