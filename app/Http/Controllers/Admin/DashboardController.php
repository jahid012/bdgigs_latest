<?php

namespace App\Http\Controllers\Admin;

use App\Models\Conversation;
use App\Models\Dispute;
use App\Models\Gig;
use App\Models\AdminNotification;
use App\Models\ManualPaymentSubmission;
use App\Models\ModerationReport;
use App\Models\Order;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WithdrawalRequest;

class DashboardController extends AdminController
{
    public function index()
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();
        $totalRevenue = (int) Order::sum('price_cents');
        $monthRevenue = (int) Order::where('created_at', '>=', $monthStart)->sum('price_cents');
        $openOrders = Order::whereNotIn('status', ['Delivered', 'Completed', 'Cancelled'])->count();
        $dueToday = Order::whereDate('due_date', $today)->count();
        $lateOrders = Order::whereDate('due_date', '<', $today)
            ->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled'])
            ->count();
        $pendingGigs = Gig::whereNotIn('status', ['Live', 'Published', 'approved'])->count();
        $messageQueue = Conversation::where(function ($query) {
            $query
                ->where('buyer_unread_count', '>', 0)
                ->orWhere('seller_unread_count', '>', 0)
                ->orWhereNotNull('priority');
        })->count();
        $suspendedUsers = User::whereNotNull('suspended_at')->count();
        $pendingManualPayments = ManualPaymentSubmission::where('status', 'pending')->count();
        $pendingWithdrawals = WithdrawalRequest::whereIn('status', ['pending', 'under_review'])->count();
        $openDisputes = Dispute::whereNotIn('status', ['resolved', 'rejected', 'closed'])->count();
        $pendingReports = ModerationReport::where('status', 'pending')->count();
        $pendingSellers = User::where('seller_status', 'pending')->count();

        $recentOrders = Order::with(['buyer', 'seller'])
            ->latest()
            ->take(4)
            ->get()
            ->map(fn (Order $order) => $this->orderRow($order))
            ->all();

        return $this->panelView('admin.dashboard', [
            'pageTitle' => 'Marketplace overview',
            'pageEyebrow' => 'Admin dashboard',
            'pageDescription' => 'Live operational snapshot for orders, moderation, payouts, and marketplace trust.',
            'searchPlaceholder' => 'Search users, gigs, orders',
            'briefing' => [
                ['label' => 'Gross marketplace value', 'value' => $this->money($totalRevenue), 'meta' => $this->money($monthRevenue).' this month'],
                ['label' => 'Trust risk', 'value' => $suspendedUsers > 0 ? 'Review' : 'Stable', 'meta' => number_format($suspendedUsers).' suspended accounts'],
                ['label' => 'SLA health', 'value' => $lateOrders > 0 ? 'Needs attention' : 'Healthy', 'meta' => number_format($lateOrders).' late-risk orders'],
            ],
            'health' => [
                ['label' => 'Payments', 'value' => $pendingManualPayments > 0 ? 'Review queue' : 'Clear', 'tone' => $pendingManualPayments > 0 ? 'warn' : 'good'],
                ['label' => 'Messaging', 'value' => $messageQueue > 0 ? 'Needs replies' : 'Healthy', 'tone' => $messageQueue > 0 ? 'warn' : 'good'],
                ['label' => 'Moderation', 'value' => $pendingGigs > 0 ? 'Backlog' : 'Clear', 'tone' => $pendingGigs > 0 ? 'warn' : 'good'],
                ['label' => 'Orders', 'value' => $lateOrders > 0 ? 'Late risk' : 'On track', 'tone' => $lateOrders > 0 ? 'warn' : 'good'],
            ],
            'stats' => [
                ['label' => 'Gross sales', 'value' => $this->money($totalRevenue), 'meta' => $this->money($monthRevenue).' this month'],
                ['label' => 'Open orders', 'value' => number_format($openOrders), 'meta' => number_format($dueToday).' due today'],
                ['label' => 'Pending gigs', 'value' => number_format($pendingGigs), 'meta' => 'Need review'],
                ['label' => 'Message queue', 'value' => number_format($messageQueue), 'meta' => 'Unread or priority conversations'],
            ],
            'orders' => $recentOrders,
            'pagination' => $this->paginationMeta(Order::count(), 4),
            'activities' => $this->activities(),
            'revenueTrend' => $this->weeklyRevenueTrend(),
            'moderationQueue' => [
                ['label' => 'Gig approvals', 'value' => number_format($pendingGigs), 'route' => 'admin.gigs', 'params' => ['status' => 'review']],
                ['label' => 'Seller applications', 'value' => number_format($pendingSellers), 'route' => 'admin.seller-applications', 'params' => ['status' => 'pending']],
                ['label' => 'Manual payments', 'value' => number_format($pendingManualPayments), 'route' => 'admin.manual-payments', 'params' => ['status' => 'pending']],
                ['label' => 'Withdrawal reviews', 'value' => number_format($pendingWithdrawals), 'route' => 'admin.withdrawals', 'params' => ['status' => 'pending']],
                ['label' => 'Open disputes', 'value' => number_format($openDisputes), 'route' => 'admin.disputes', 'params' => ['status' => 'open']],
                ['label' => 'Moderation reports', 'value' => number_format($pendingReports), 'route' => 'admin.moderation-reports', 'params' => ['status' => 'pending']],
            ],
            'priorityWorkflow' => [
                ['step' => '1', 'label' => 'Review late orders', 'meta' => number_format($lateOrders).' at risk'],
                ['step' => '2', 'label' => 'Clear payment reviews', 'meta' => number_format($pendingManualPayments).' pending'],
                ['step' => '3', 'label' => 'Release payout queue', 'meta' => number_format($pendingWithdrawals).' waiting'],
                ['step' => '4', 'label' => 'Triage trust queues', 'meta' => number_format($openDisputes + $pendingReports).' cases'],
            ],
            'qualityBars' => $this->qualityBars($openOrders, $lateOrders),
        ]);
    }

    private function activities(): array
    {
        $activities = collect();

        AdminNotification::latest()
            ->take(2)
            ->get()
            ->each(fn (AdminNotification $notification) => $activities->push($notification->title.': '.$notification->detail));

        UserNotification::latest()
            ->take(2)
            ->get()
            ->each(fn (UserNotification $notification) => $activities->push($notification->title.': '.$notification->detail));

        Gig::latest()
            ->take(1)
            ->get()
            ->each(fn (Gig $gig) => $activities->push('Gig "'.$gig->title.'" is currently '.$gig->status.'.'));

        Order::latest()
            ->take(1)
            ->get()
            ->each(fn (Order $order) => $activities->push('Order #'.$order->code.' is '.$order->status.'.'));

        if ($activities->isEmpty()) {
            $activities->push('No operational activity has been recorded yet.');
        }

        return $activities->take(4)->values()->all();
    }

    private function weeklyRevenueTrend(): array
    {
        $weeks = collect(range(7, 0))->map(function (int $weeksAgo) {
            $start = now()->startOfWeek()->subWeeks($weeksAgo);
            $end = $start->copy()->endOfWeek();

            return [
                'label' => $start->format('M j'),
                'value' => (int) round(Order::whereBetween('created_at', [$start, $end])->sum('price_cents') / 100),
            ];
        });

        $values = $weeks->pluck('value')->all();

        return [
            'title' => 'Revenue trend',
            'description' => 'Last 8 weeks marketplace performance.',
            'labels' => $weeks->pluck('label')->all(),
            'max' => max(1, $values === [] ? 0 : max($values)),
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'values' => $values,
                    'color' => '#10b981',
                    'fill' => 'rgba(16, 185, 129, 0.12)',
                    'valuePrefix' => '$',
                ],
            ],
            'summary' => [
                ['label' => '8 week revenue', 'value' => $this->money(array_sum($values) * 100)],
                ['label' => 'Orders', 'value' => number_format(Order::where('created_at', '>=', now()->startOfWeek()->subWeeks(7))->count())],
                ['label' => 'Current week', 'value' => $this->money((int) Order::where('created_at', '>=', now()->startOfWeek())->sum('price_cents'))],
            ],
        ];
    }

    private function qualityBars(int $openOrders, int $lateOrders): array
    {
        $sellerResponse = Conversation::count() === 0
            ? 100
            : max(0, 100 - (int) round((Conversation::where('seller_unread_count', '>', 0)->count() / Conversation::count()) * 100));
        $orderHealth = $openOrders === 0 ? 100 : max(0, 100 - (int) round(($lateOrders / $openOrders) * 100));
        $publishedGigs = Gig::whereIn('status', ['Live', 'Published', 'approved'])->count();
        $totalGigs = max(1, Gig::count());

        return [
            ['label' => 'Gig publish health', 'value' => (int) round(($publishedGigs / $totalGigs) * 100)],
            ['label' => 'Order SLA health', 'value' => $orderHealth],
            ['label' => 'Seller response health', 'value' => $sellerResponse],
        ];
    }
}
