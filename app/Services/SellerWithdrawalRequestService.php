<?php

namespace App\Services;

use App\Models\SellerPayoutMethod;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Support\MarketplaceNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SellerWithdrawalRequestService
{
    public function __construct(
        private readonly SellerWithdrawalBalanceService $balances,
        private readonly MarketplaceNotifier $notifier,
    ) {
    }

    public function create(User $seller, SellerPayoutMethod $method, int $amountCents, ?string $note): WithdrawalRequest
    {
        abort_unless($method->user_id === $seller->id && $method->active, 422, 'Choose an active payout method.');
        abort_if(
            $amountCents < SellerWithdrawalBalanceService::MINIMUM_WITHDRAWAL_CENTS,
            422,
            'The minimum withdrawal amount is $10.'
        );
        abort_if(
            $amountCents > $this->balances->snapshot($seller)['available_cents'],
            422,
            'The requested amount is higher than your available withdrawal balance.'
        );

        return DB::transaction(function () use ($seller, $method, $amountCents, $note) {
            $withdrawal = $seller->withdrawalRequests()->create([
                'code' => $this->nextCode(),
                'seller_payout_method_id' => $method->id,
                'amount_cents' => $amountCents,
                'currency' => 'USD',
                'payout_snapshot' => $this->methodSnapshot($method),
                'status' => 'pending',
                'seller_note' => $note,
            ]);

            $withdrawal->activities()->create([
                'actor_id' => $seller->id,
                'type' => 'requested',
                'title' => 'Withdrawal requested',
                'detail' => $note ?: 'Seller submitted a manual withdrawal request.',
            ]);
            $method->forceFill(['last_used_at' => now()])->save();

            $this->notifier->notify(
                $seller,
                'Withdrawal',
                'Withdrawal request submitted',
                $withdrawal->code.' is waiting for manual finance review.',
                '/dashboard/seller/earnings',
                ['withdrawal' => $withdrawal->code],
            );

            return $withdrawal->load(['payoutMethod', 'activities']);
        });
    }

    public function cancel(WithdrawalRequest $withdrawal, User $seller): WithdrawalRequest
    {
        abort_unless($withdrawal->seller_id === $seller->id, 403);
        abort_unless(in_array($withdrawal->status, ['pending', 'under_review'], true), 422, 'Only reviewable withdrawals can be cancelled.');

        return DB::transaction(function () use ($withdrawal, $seller) {
            $withdrawal->forceFill([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ])->save();

            $withdrawal->activities()->create([
                'actor_id' => $seller->id,
                'type' => 'cancelled',
                'title' => 'Withdrawal cancelled',
                'detail' => 'Seller cancelled the manual withdrawal request before approval.',
            ]);

            return $withdrawal->refresh(['payoutMethod', 'activities']);
        });
    }

    private function methodSnapshot(SellerPayoutMethod $method): array
    {
        return [
            'methodId' => $method->id,
            'type' => $method->type,
            'label' => $method->label,
            'accountHolder' => $method->account_holder,
            'accountNumber' => $method->account_number,
            'routingDetails' => $method->routing_details,
        ];
    }

    private function nextCode(): string
    {
        do {
            $code = 'WD-'.Str::upper(Str::random(8));
        } while (WithdrawalRequest::where('code', $code)->exists());

        return $code;
    }
}
