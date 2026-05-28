<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SellerPayoutMethod;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Collection;

class FinanceSummaryService
{
    public function __construct(private readonly SellerWithdrawalBalanceService $withdrawalBalances)
    {
    }

    public function buyer(User $user): array
    {
        $orders = $user->buyerOrders()->latest()->get();
        $wallet = $user->wallet()->firstOrCreate([], [
            'balance_cents' => 0,
            'credits_cents' => 0,
            'refunded_cents' => 0,
            'currency' => 'USD',
        ]);
        $walletHistory = $wallet->transactions()
            ->latest('processed_at')
            ->latest()
            ->get();
        $orderHistory = $orders->map(fn (Order $order) => [
            'id' => $order->code,
            'date' => $order->created_at?->format('M j, Y'),
            'document' => 'Order receipt',
            'service' => $order->service,
            'order' => '#'.$order->code,
            'currency' => 'USD',
            'total' => $this->money($order->price_cents),
            'status' => $order->status,
            'documentUrl' => '/api/orders/'.$order->code.'/receipt',
            'sortAt' => $order->created_at?->getTimestamp() ?? 0,
        ]);
        $transactions = $walletHistory->map(fn (WalletTransaction $transaction) => [
            'id' => $transaction->code,
            'date' => $transaction->processed_at?->format('M j, Y') ?? $transaction->created_at?->format('M j, Y'),
            'document' => 'Wallet transaction',
            'service' => $transaction->description ?: str($transaction->type)->replace('_', ' ')->title()->toString(),
            'order' => $transaction->code,
            'currency' => $transaction->currency,
            'total' => $this->money($transaction->amount_cents),
            'status' => str($transaction->status)->title()->toString(),
            'sortAt' => $transaction->processed_at?->getTimestamp() ?? $transaction->created_at?->getTimestamp() ?? 0,
        ]);

        return [
            'history' => $orderHistory
                ->concat($transactions)
                ->sortByDesc('sortAt')
                ->map(function (array $item) {
                    unset($item['sortAt']);

                    return $item;
                })
                ->values(),
            'balances' => [
                'balance' => $this->money((int) $wallet->balance_cents),
                'balanceValue' => round(((int) $wallet->balance_cents) / 100, 2),
                'credits' => $this->money((int) $wallet->credits_cents),
                'refunded' => $this->money((int) $wallet->refunded_cents),
            ],
            'paymentMethods' => [
                [
                    'id' => 'bdgigs-wallet',
                    'label' => 'bdgigs wallet',
                    'detail' => 'Use your wallet balance for marketplace purchases.',
                    'status' => ((int) $wallet->balance_cents) > 0
                        ? 'Ready'
                        : 'Empty',
                ],
            ],
            'documents' => [],
        ];
    }

    public function seller(User $user): array
    {
        $orders = $user->sellerOrders()->latest()->get();
        $withdrawals = $user->withdrawalRequests()->latest()->get();
        $payoutMethods = $user->sellerPayoutMethods()->latest()->get();
        $balance = $this->withdrawalBalances->snapshot($user);
        $toDate = (int) $orders->sum('earnings_cents');

        return [
            'summary' => [
                'availableFunds' => $this->money($balance['available_cents']),
                'withdrawnToDate' => $this->money($balance['paid_cents']),
                'clearing' => $this->money($balance['reserved_cents']),
                'activeOrderEarnings' => $this->money($balance['active_cents']),
                'earningsToDate' => $this->money($toDate),
                'expensesToDate' => '$0',
                'minimumWithdrawal' => $this->money($balance['minimum_cents']),
                'availableValue' => round($balance['available_cents'] / 100, 2),
            ],
            'history' => $this->sellerHistory($orders, $withdrawals),
            'chartData' => $this->monthlySeries($orders),
            'documents' => [],
            'payoutMethods' => $payoutMethods
                ->map(fn (SellerPayoutMethod $method) => $this->payoutMethod($method))
                ->values(),
            'withdrawals' => $withdrawals
                ->map(fn (WithdrawalRequest $withdrawal) => $this->withdrawalRow($withdrawal))
                ->values(),
        ];
    }

    private function sellerHistory(Collection $orders, Collection $withdrawals): Collection
    {
        $earnings = $orders->map(fn (Order $order) => [
            'id' => $order->code,
            'date' => $order->created_at?->format('M j, Y'),
            'amount' => $this->money($order->earnings_cents),
            'status' => 'Earning',
            'from' => $order->buyer_name ?: 'Buyer',
            'activity' => 'earning',
            'description' => $order->service,
            'sortAt' => $order->created_at?->getTimestamp() ?? 0,
        ]);
        $payouts = $withdrawals->map(fn (WithdrawalRequest $withdrawal) => [
            'id' => $withdrawal->code,
            'date' => $withdrawal->created_at?->format('M j, Y'),
            'amount' => '-'.$this->money($withdrawal->approved_amount_cents ?: $withdrawal->amount_cents),
            'status' => str($withdrawal->status)->replace('_', ' ')->title()->toString(),
            'from' => 'bdgigs manual payout',
            'activity' => 'withdrawal',
            'description' => $withdrawal->payout_snapshot['label'] ?? 'Manual withdrawal',
            'sortAt' => $withdrawal->created_at?->getTimestamp() ?? 0,
        ]);

        return $earnings
            ->concat($payouts)
            ->sortByDesc('sortAt')
            ->map(function (array $item) {
                unset($item['sortAt']);

                return $item;
            })
            ->values();
    }

    private function payoutMethod(SellerPayoutMethod $method): array
    {
        return [
            'id' => $method->id,
            'type' => $method->type,
            'label' => $method->label,
            'accountHolder' => $method->account_holder,
            'accountNumber' => $method->account_number,
            'routingDetails' => $method->routing_details,
            'active' => $method->active,
        ];
    }

    private function withdrawalRow(WithdrawalRequest $withdrawal): array
    {
        return [
            'id' => $withdrawal->code,
            'code' => $withdrawal->code,
            'amount' => $this->money($withdrawal->amount_cents),
            'amountValue' => round($withdrawal->amount_cents / 100, 2),
            'status' => str($withdrawal->status)->replace('_', ' ')->title()->toString(),
            'statusKey' => $withdrawal->status,
            'payout' => $withdrawal->payout_snapshot,
            'paymentReference' => $withdrawal->payment_reference,
            'canCancel' => in_array($withdrawal->status, ['pending', 'under_review'], true),
            'requestedDate' => $withdrawal->created_at?->format('M j, Y'),
        ];
    }

    private function monthlySeries(Collection $orders): array
    {
        return collect(range(6, 0))
            ->map(function (int $offset) use ($orders) {
                $month = now()->subMonths($offset);

                return [
                    'label' => $month->format('M'),
                    'value' => (int) round($orders
                        ->filter(fn (Order $order) => $order->created_at?->isSameMonth($month))
                        ->sum('earnings_cents') / 100),
                ];
            })
            ->all();
    }

    private function money(int $cents): string
    {
        return '$'.number_format($cents / 100, 0);
    }
}
