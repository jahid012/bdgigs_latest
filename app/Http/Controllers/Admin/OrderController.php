<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UpdateAdminOrderStatusRequest;
use App\Models\Order;
use App\Services\AdminOrderStatusService;
use Illuminate\Http\Request;

class OrderController extends AdminController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));
        $allowedStatuses = ['all', 'active', 'late', 'revision', 'delivered', 'cancelled'];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'all';

        $ordersQuery = Order::query()
            ->with(['buyer', 'seller'])
            ->latest();

        if ($search !== '') {
            $ordersQuery->where(function ($query) use ($search) {
                $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('service', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%")
                    ->orWhere('seller_name', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        match ($status) {
            'active' => $ordersQuery->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled']),
            'late' => $ordersQuery
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled']),
            'revision' => $ordersQuery->whereIn('status', ['Revision', 'Revision Requested']),
            'delivered' => $ordersQuery->whereIn('status', ['Delivered', 'Completed']),
            'cancelled' => $ordersQuery->where('status', 'Cancelled'),
            default => null,
        };

        $perPage = 8;
        $total = (clone $ordersQuery)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $orders = $ordersQuery
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn (Order $order) => $this->orderRow($order))
            ->all();

        $todayOrders = Order::whereDate('created_at', now()->toDateString())->count();
        $lateOrders = $this->lateOrdersQuery()->count();
        $deliveredToday = Order::whereIn('status', ['Delivered', 'Completed'])
            ->whereDate('updated_at', now()->toDateString())
            ->count();
        $cancelled = Order::where('status', 'Cancelled')->count();
        $activeOrders = Order::whereNotIn('status', ['Delivered', 'Completed', 'Cancelled'])->count();

        return $this->panelView('admin.pages.orders', [
            'pageTitle' => 'Orders',
            'pageEyebrow' => 'Delivery operations',
            'pageDescription' => 'Track order status, due dates, revision risk, cancellations, and buyer experience.',
            'searchPlaceholder' => 'Search orders, buyers, sellers',
            'stats' => [
                ['label' => 'Orders today', 'value' => number_format($todayOrders), 'meta' => $this->money((int) Order::whereDate('created_at', now()->toDateString())->sum('price_cents'))],
                ['label' => 'Late risk', 'value' => number_format($lateOrders), 'meta' => 'Needs follow-up'],
                ['label' => 'Delivered', 'value' => number_format($deliveredToday), 'meta' => 'Today'],
                ['label' => 'Cancelled', 'value' => number_format($cancelled), 'meta' => 'All time'],
            ],
            'orders' => $orders,
            'pagination' => $pagination,
            'filters' => [
                ['label' => 'All', 'value' => 'all', 'count' => Order::count()],
                ['label' => 'Active', 'value' => 'active', 'count' => $activeOrders],
                ['label' => 'Late risk', 'value' => 'late', 'count' => $lateOrders],
                ['label' => 'Revision', 'value' => 'revision', 'count' => Order::whereIn('status', ['Revision', 'Revision Requested'])->count()],
                ['label' => 'Delivered', 'value' => 'delivered', 'count' => Order::whereIn('status', ['Delivered', 'Completed'])->count()],
                ['label' => 'Cancelled', 'value' => 'cancelled', 'count' => $cancelled],
            ],
            'currentFilter' => $status,
            'searchQuery' => $search,
            'slaBars' => $this->slaBars($activeOrders, $lateOrders),
            'workflowSteps' => [
                ['step' => '1', 'label' => 'Nudge missing requirements', 'meta' => number_format(Order::whereIn('status', ['Pending', 'Pending Requirements'])->count()).' pending'],
                ['step' => '2', 'label' => 'Contact late-risk sellers', 'meta' => number_format($lateOrders).' orders'],
                ['step' => '3', 'label' => 'Audit cancellation reasons', 'meta' => number_format($cancelled).' cancelled'],
            ],
        ]);
    }

    public function show(Order $order)
    {
        $order->load([
            'buyer',
            'seller',
            'gig',
            'activities' => fn ($activities) => $activities->with('actor')->latest(),
            'manualPaymentSubmission.method',
            'manualPaymentSubmission.reviewer',
            'disputes.assignedTo',
        ]);

        return $this->panelView('admin.pages.order-details', [
            'pageTitle' => 'Order #'.$order->code,
            'pageEyebrow' => 'Order details',
            'pageDescription' => 'Review the buyer, seller, payment evidence, requirements, and activity before changing an order.',
            'searchPlaceholder' => 'Search orders, buyers, sellers',
            'order' => $order,
            'statusOptions' => $this->statusOptions(),
            'stats' => [
                ['label' => 'Order value', 'value' => $this->money((int) $order->price_cents), 'meta' => 'Buyer amount'],
                ['label' => 'Seller earnings', 'value' => $this->money((int) $order->earnings_cents), 'meta' => 'Current order record'],
                ['label' => 'Due date', 'value' => $order->due_date?->format('M j') ?? 'None', 'meta' => $order->due_date?->diffForHumans() ?? 'No delivery date'],
                ['label' => 'Activity', 'value' => number_format($order->activities->count()), 'meta' => 'Audit entries'],
            ],
        ]);
    }

    public function updateStatus(
        UpdateAdminOrderStatusRequest $request,
        Order $order,
        AdminOrderStatusService $statuses
    ) {
        $statuses->update($order, $request->user(), $request->validated()['status']);

        return back()->withNotify('success', 'Order #'.$order->code.' is now '.$request->validated()['status'].'.', 'Order updated');
    }

    private function lateOrdersQuery()
    {
        return Order::query()
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled']);
    }

    private function slaBars(int $activeOrders, int $lateOrders): array
    {
        $total = max(1, Order::count());
        $delivered = Order::whereIn('status', ['Delivered', 'Completed'])->count();
        $revision = Order::where('status', 'Revision')->count();
        $onTime = $activeOrders === 0 ? 100 : max(0, 100 - (int) round(($lateOrders / $activeOrders) * 100));

        return [
            ['label' => 'On-time delivery', 'value' => $onTime],
            ['label' => 'Delivered orders', 'value' => (int) round(($delivered / $total) * 100)],
            ['label' => 'Revision pressure', 'value' => (int) round(($revision / $total) * 100)],
        ];
    }

    private function statusOptions(): array
    {
        return [
            'Pending Payment Review',
            'Payment Rejected',
            'Pending Requirements',
            'In Progress',
            'Revision Requested',
            'Delivered',
            'Completed',
            'Cancelled',
        ];
    }
}
