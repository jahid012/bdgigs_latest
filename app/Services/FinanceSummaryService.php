<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;

class FinanceSummaryService
{
    public function buyer(User $user): array
    {
        $orders = $user->buyerOrders()->latest()->get();

        return [
            'history' => $orders->map(fn (Order $order) => [
                'id' => $order->code,
                'date' => $order->created_at?->format('M j, Y'),
                'document' => 'Order receipt',
                'service' => $order->service,
                'order' => '#'.$order->code,
                'currency' => 'USD',
                'total' => $this->money($order->price_cents),
                'status' => $order->status,
            ])->values(),
            'balances' => [
                'balance' => '$0',
                'credits' => '$0',
                'refunded' => '$0',
            ],
            'paymentMethods' => [],
            'documents' => [],
        ];
    }

    public function seller(User $user): array
    {
        $orders = $user->sellerOrders()->latest()->get();
        $available = (int) $orders->whereIn('status', ['Delivered', 'Completed'])->sum('earnings_cents');
        $active = (int) $orders->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled'])->sum('earnings_cents');
        $toDate = (int) $orders->sum('earnings_cents');

        return [
            'summary' => [
                'availableFunds' => $this->money($available),
                'withdrawnToDate' => '$0',
                'clearing' => '$0',
                'activeOrderEarnings' => $this->money($active),
                'earningsToDate' => $this->money($toDate),
                'expensesToDate' => '$0',
            ],
            'history' => $orders->map(fn (Order $order) => [
                'id' => $order->code,
                'date' => $order->created_at?->format('M j, Y'),
                'amount' => $this->money($order->earnings_cents),
                'status' => 'Earning',
                'from' => $order->buyer_name ?: 'Buyer',
                'activity' => 'earning',
                'description' => $order->service,
            ])->values(),
            'chartData' => $this->monthlySeries($orders),
            'documents' => [],
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
