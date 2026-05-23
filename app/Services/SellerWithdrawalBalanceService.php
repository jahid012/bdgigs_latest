<?php

namespace App\Services;

use App\Models\User;
use App\Models\WithdrawalRequest;

class SellerWithdrawalBalanceService
{
    public const MINIMUM_WITHDRAWAL_CENTS = 1000;

    public function snapshot(User $seller): array
    {
        $eligible = (int) $seller->sellerOrders()
            ->whereIn('status', ['Delivered', 'Completed'])
            ->sum('earnings_cents');
        $active = (int) $seller->sellerOrders()
            ->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled'])
            ->sum('earnings_cents');
        $reserved = (int) $seller->withdrawalRequests()
            ->whereIn('status', ['pending', 'under_review', 'approved'])
            ->get()
            ->sum(fn (WithdrawalRequest $withdrawal) => $this->reservedAmount($withdrawal));
        $paid = (int) $seller->withdrawalRequests()
            ->where('status', 'paid')
            ->get()
            ->sum(fn (WithdrawalRequest $withdrawal) => $this->paidAmount($withdrawal));

        return [
            'eligible_cents' => $eligible,
            'active_cents' => $active,
            'reserved_cents' => $reserved,
            'paid_cents' => $paid,
            'available_cents' => max(0, $eligible - $reserved - $paid),
            'minimum_cents' => self::MINIMUM_WITHDRAWAL_CENTS,
        ];
    }

    private function reservedAmount(WithdrawalRequest $withdrawal): int
    {
        return (int) ($withdrawal->approved_amount_cents ?: $withdrawal->amount_cents);
    }

    private function paidAmount(WithdrawalRequest $withdrawal): int
    {
        return (int) ($withdrawal->approved_amount_cents ?: $withdrawal->amount_cents);
    }
}
