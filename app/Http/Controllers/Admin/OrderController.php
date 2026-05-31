<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\RefundOrderRequest;
use App\Http\Requests\Admin\UpdateAdminOrderStatusRequest;
use App\Models\Order;
use App\Services\AdminOrderStatusService;
use App\Services\OrderCancellationService;
use App\Services\OrderPaymentLifecycleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends AdminController
{
    private const STATUS_FILTERS = [
        'all' => 'All',
        'active' => 'Active',
        'late' => 'Late risk',
        'revision' => 'Revision',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];

    private const PAYMENT_FILTERS = [
        'all' => 'Any payment state',
        'unpaid' => 'Unpaid',
        'pending' => 'Pending review',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'partially_refunded' => 'Partially refunded',
    ];

    private const DUE_FILTERS = [
        'all' => 'Any due date',
        'overdue' => 'Overdue',
        'today' => 'Due today',
        '7d' => 'Due in 7 days',
        'none' => 'No due date',
    ];

    private const AMOUNT_FILTERS = [
        'all' => 'Any amount',
        'under_50' => 'Under $50',
        '50_200' => '$50 to $199',
        '200_plus' => '$200+',
    ];

    private const SORT_OPTIONS = [
        'latest' => 'Newest first',
        'oldest' => 'Oldest first',
        'due_soon' => 'Due soon',
        'amount_high' => 'Amount high to low',
        'amount_low' => 'Amount low to high',
    ];

    public function index(Request $request)
    {
        $filterState = $this->filterState($request);

        $ordersQuery = Order::query()
            ->with(['buyer', 'seller']);

        $this->applyOrderFilters($ordersQuery, $filterState);
        $this->applyOrderSort($ordersQuery, $filterState['sort']);

        $perPage = 12;
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
            'filters' => $this->statusFilters($activeOrders, $lateOrders, $cancelled),
            'currentFilter' => $filterState['status'],
            'searchQuery' => $filterState['q'],
            'filterState' => $filterState,
            'paymentFilters' => self::PAYMENT_FILTERS,
            'dueFilters' => self::DUE_FILTERS,
            'amountFilters' => self::AMOUNT_FILTERS,
            'sortOptions' => self::SORT_OPTIONS,
            'statusOptions' => $this->statusOptions(),
            'hasActiveFilters' => $this->hasActiveFilters($filterState),
            'slaBars' => $this->slaBars($activeOrders, $lateOrders),
            'workflowSteps' => [
                ['step' => '1', 'label' => 'Nudge missing requirements', 'meta' => number_format(Order::whereIn('status', ['Pending', 'Pending Requirements'])->count()).' pending'],
                ['step' => '2', 'label' => 'Contact late-risk sellers', 'meta' => number_format($lateOrders).' orders'],
                ['step' => '3', 'label' => 'Audit cancellation reasons', 'meta' => number_format($cancelled).' cancelled'],
            ],
        ]);
    }

    public function bulkAction(Request $request, AdminOrderStatusService $statuses)
    {
        abort_unless($request->user('admin')?->can('orders.manage'), 403);

        $payload = $request->validate([
            'orders' => ['required', 'array', 'min:1'],
            'orders.*' => ['required', 'string', 'distinct', 'exists:orders,code'],
            'status' => ['required', 'string', Rule::in($this->statusOptions())],
        ]);

        $orders = Order::query()
            ->whereIn('code', $payload['orders'])
            ->get();
        $updated = 0;

        foreach ($orders as $order) {
            if ($order->status === $payload['status']) {
                continue;
            }

            $statuses->update($order, $request->user('admin'), $payload['status']);
            $updated++;
        }

        if ($updated === 0) {
            return back()->withNotify('warning', 'No eligible orders changed status.', 'Bulk action skipped');
        }

        return back()->withNotify('success', number_format($updated).' '.($updated === 1 ? 'order' : 'orders').' updated.', 'Bulk action applied');
    }

    public function show(Order $order)
    {
        $order->load([
            'buyer',
            'seller',
            'gig',
            'activities' => fn ($activities) => $activities->with(['actor', 'adminActor'])->latest(),
            'manualPaymentSubmission.method',
            'manualPaymentSubmission.reviewer',
            'manualPaymentSubmission.adminReviewer',
            'disputes.assignedTo',
            'disputes.assignedAdmin',
            'invoice',
            'latestCancellation.requester',
            'latestCancellation.responder',
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
                ['label' => 'Payment', 'value' => str($order->payment_status ?: 'unpaid')->replace('_', ' ')->title()->toString(), 'meta' => $order->transaction_id ?: 'No transaction'],
                ['label' => 'Due date', 'value' => $order->due_date?->format('M j') ?? 'None', 'meta' => $order->due_date?->diffForHumans() ?? 'No delivery date'],
            ],
        ]);
    }

    public function updateStatus(
        UpdateAdminOrderStatusRequest $request,
        Order $order,
        AdminOrderStatusService $statuses
    ) {
        $statuses->update($order, $request->user('admin'), $request->validated()['status']);

        return back()->withNotify('success', 'Order #'.$order->code.' is now '.$request->validated()['status'].'.', 'Order updated');
    }

    public function refund(
        RefundOrderRequest $request,
        Order $order,
        OrderPaymentLifecycleService $payments
    ) {
        $payments->refund(
            $order->loadMissing(['buyer', 'seller', 'gig']),
            $request->user('admin'),
            $request->amountCents(),
            $request->validated('reason'),
        );

        return back()->withNotify('success', 'Order #'.$order->code.' was refunded.', 'Refund processed');
    }

    public function cancel(
        Request $request,
        Order $order,
        OrderCancellationService $cancellations
    ) {
        $payload = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $cancellations->adminCancel($order->loadMissing(['buyer', 'seller']), $request->user('admin'), $payload['reason']);

        return back()->withNotify('success', 'Order #'.$order->code.' was cancelled.', 'Order cancelled');
    }

    private function lateOrdersQuery()
    {
        return Order::query()
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled']);
    }

    private function filterState(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $this->validatedOption($request->query('status', 'all'), array_keys(self::STATUS_FILTERS), 'all'),
            'buyer' => trim((string) $request->query('buyer', '')),
            'seller' => trim((string) $request->query('seller', '')),
            'payment' => $this->validatedOption($request->query('payment', 'all'), array_keys(self::PAYMENT_FILTERS), 'all'),
            'due' => $this->validatedOption($request->query('due', 'all'), array_keys(self::DUE_FILTERS), 'all'),
            'amount' => $this->validatedOption($request->query('amount', 'all'), array_keys(self::AMOUNT_FILTERS), 'all'),
            'sort' => $this->validatedOption($request->query('sort', 'latest'), array_keys(self::SORT_OPTIONS), 'latest'),
        ];
    }

    private function applyOrderFilters(Builder $query, array $filters): void
    {
        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('service', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%")
                    ->orWhere('seller_name', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('buyer', fn (Builder $buyers) => $buyers->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('seller', fn (Builder $sellers) => $sellers->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        match ($filters['status']) {
            'active' => $query->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled']),
            'late' => $query
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled']),
            'revision' => $query->whereIn('status', ['Revision', 'Revision Requested']),
            'delivered' => $query->whereIn('status', ['Delivered', 'Completed']),
            'cancelled' => $query->where('status', 'Cancelled'),
            default => null,
        };

        if ($filters['buyer'] !== '') {
            $buyer = $filters['buyer'];
            $query->where(function (Builder $query) use ($buyer) {
                $query
                    ->where('buyer_name', 'like', "%{$buyer}%")
                    ->orWhereHas('buyer', fn (Builder $buyers) => $buyers->where('name', 'like', "%{$buyer}%")->orWhere('email', 'like', "%{$buyer}%"));
            });
        }

        if ($filters['seller'] !== '') {
            $seller = $filters['seller'];
            $query->where(function (Builder $query) use ($seller) {
                $query
                    ->where('seller_name', 'like', "%{$seller}%")
                    ->orWhereHas('seller', fn (Builder $sellers) => $sellers->where('name', 'like', "%{$seller}%")->orWhere('email', 'like', "%{$seller}%"));
            });
        }

        if ($filters['payment'] !== 'all') {
            $query->where('payment_status', $filters['payment']);
        }

        match ($filters['due']) {
            'overdue' => $query->whereDate('due_date', '<', now()->toDateString()),
            'today' => $query->whereDate('due_date', now()->toDateString()),
            '7d' => $query->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()]),
            'none' => $query->whereNull('due_date'),
            default => null,
        };

        match ($filters['amount']) {
            'under_50' => $query->where('price_cents', '<', 5000),
            '50_200' => $query->whereBetween('price_cents', [5000, 19999]),
            '200_plus' => $query->where('price_cents', '>=', 20000),
            default => null,
        };
    }

    private function applyOrderSort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'due_soon' => $query->orderByRaw('due_date IS NULL')->orderBy('due_date')->latest(),
            'amount_high' => $query->orderByDesc('price_cents')->latest(),
            'amount_low' => $query->orderBy('price_cents')->latest(),
            default => $query->latest(),
        };
    }

    private function statusFilters(int $activeOrders, int $lateOrders, int $cancelled): array
    {
        return [
            ['label' => self::STATUS_FILTERS['all'], 'value' => 'all', 'count' => Order::count()],
            ['label' => self::STATUS_FILTERS['active'], 'value' => 'active', 'count' => $activeOrders],
            ['label' => self::STATUS_FILTERS['late'], 'value' => 'late', 'count' => $lateOrders],
            ['label' => self::STATUS_FILTERS['revision'], 'value' => 'revision', 'count' => Order::whereIn('status', ['Revision', 'Revision Requested'])->count()],
            ['label' => self::STATUS_FILTERS['delivered'], 'value' => 'delivered', 'count' => Order::whereIn('status', ['Delivered', 'Completed'])->count()],
            ['label' => self::STATUS_FILTERS['cancelled'], 'value' => 'cancelled', 'count' => $cancelled],
        ];
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['status'] !== 'all'
            || $filters['buyer'] !== ''
            || $filters['seller'] !== ''
            || $filters['payment'] !== 'all'
            || $filters['due'] !== 'all'
            || $filters['amount'] !== 'all'
            || $filters['sort'] !== 'latest';
    }

    private function validatedOption(mixed $value, array $allowed, string $fallback): string
    {
        $value = trim((string) $value);

        return in_array($value, $allowed, true) ? $value : $fallback;
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
            'Waiting for Requirements',
            'Requirements Submitted',
            'In Progress',
            'Overdue',
            'Revision Requested',
            'Delivered',
            'Completed',
            'Cancelled',
        ];
    }
}
