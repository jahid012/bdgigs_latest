<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;

class PaymentController extends AdminController
{
    public function index()
    {
        $deliveredOrdersQuery = Order::query()
            ->with('seller')
            ->whereIn('status', ['Delivered', 'Completed'])
            ->latest();
        $perPage = 8;
        $total = (clone $deliveredOrdersQuery)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $deliveredOrders = $deliveredOrdersQuery
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get();
        $gross = (int) Order::sum('price_cents');
        $sellerEarnings = (int) Order::sum('earnings_cents');
        $estimatedFees = max(0, $gross - $sellerEarnings);

        return $this->panelView('admin.pages.payments', [
            'pageTitle' => 'Payments',
            'pageEyebrow' => 'Finance desk',
            'pageDescription' => 'Monitor platform balance, payout readiness, holds, refunds, and transaction health.',
            'searchPlaceholder' => 'Search payouts, invoices, transactions',
            'pageActions' => [
                ['label' => 'Payment schema', 'route' => 'admin.payments', 'meta' => 'Part 3'],
                ['label' => 'Delivered orders', 'route' => 'admin.orders', 'meta' => number_format($deliveredOrders->count()).' ready'],
                ['label' => 'Finance report', 'route' => 'admin.reports', 'meta' => 'Dynamic'],
            ],
            'stats' => [
                ['label' => 'Gross order value', 'value' => $this->money($gross), 'meta' => 'From current orders'],
                ['label' => 'Seller earnings', 'value' => $this->money($sellerEarnings), 'meta' => 'Estimated from orders'],
                ['label' => 'Marketplace fees', 'value' => $this->money($estimatedFees), 'meta' => 'Temporary estimate'],
                ['label' => 'Refunds', 'value' => 'Part 3', 'meta' => 'Awaiting payment tables'],
            ],
            'payments' => $deliveredOrders
                ->map(fn (Order $order) => [
                    'id' => 'PAY-'.$order->id,
                    'seller' => $order->seller?->name ?: $order->seller_name ?: 'Unknown seller',
                    'method' => 'Part 3 payout method',
                    'amount' => $this->money((int) $order->earnings_cents),
                    'status' => 'Awaiting payment system',
                ])
                ->all(),
            'pagination' => $pagination,
        ]);
    }
}
