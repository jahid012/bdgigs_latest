<?php

namespace App\Http\Controllers\Admin;

use App\Models\Gig;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends AdminController
{
    public function index(Request $request)
    {
        [$growthFrom, $growthTo] = $this->dateRange(
            $request->query('growth_from'),
            $request->query('growth_to'),
            now()->subDays(30),
            now()
        );
        $visitorDay = $this->dateValue($request->query('visitor_day'), now());
        [$profileFrom, $profileTo] = $this->dateRange(
            $request->query('profile_from'),
            $request->query('profile_to'),
            now()->subDays(30),
            now()
        );

        return $this->panelView('admin.pages.reports', [
            'pageTitle' => 'Reports',
            'pageEyebrow' => 'Marketplace analytics',
            'pageDescription' => 'Understand growth, conversion, repeat purchase behavior, and category performance.',
            'searchPlaceholder' => 'Search reports and segments',
            'stats' => [
                ['label' => 'Users today', 'value' => number_format(User::whereDate('created_at', now()->toDateString())->count()), 'meta' => 'New registrations'],
                ['label' => 'Verified users', 'value' => number_format(User::where('verification_status', 'verified')->count()), 'meta' => 'Admin or email verified'],
                ['label' => 'Published gigs', 'value' => number_format(Gig::whereIn('status', ['Live', 'Published'])->count()), 'meta' => number_format(Gig::where('created_at', '>=', now()->startOfMonth())->count()).' this month'],
                ['label' => 'Gross orders', 'value' => $this->money((int) Order::sum('price_cents')), 'meta' => number_format(Order::count()).' orders'],
            ],
            'marketplaceGrowth' => $this->marketplaceGrowthChart($growthFrom, $growthTo),
            'visitorAnalytics' => $this->hourlyActivityChart($visitorDay),
            'profileActivityGrowth' => $this->profileActivityChart($profileFrom, $profileTo),
            'segments' => $this->segments(),
            'buyerBehavior' => $this->buyerBehavior(),
        ]);
    }

    private function marketplaceGrowthChart(Carbon $from, Carbon $to): array
    {
        $buckets = $this->dateBuckets($from, $to);
        $labels = $buckets->map(fn (Carbon $day) => $day->format('M d'))->all();
        $orders = $buckets
            ->map(fn (Carbon $day) => Order::whereDate('created_at', $day->toDateString())->count())
            ->all();
        $revenue = $buckets
            ->map(fn (Carbon $day) => (int) round(Order::whereDate('created_at', $day->toDateString())->sum('price_cents') / 100))
            ->all();

        return [
            'title' => 'Marketplace growth',
            'description' => 'Orders and revenue movement across the selected reporting range.',
            'controls' => [
                ['label' => 'From', 'type' => 'date', 'name' => 'growth_from', 'value' => $from->toDateString()],
                ['label' => 'To', 'type' => 'date', 'name' => 'growth_to', 'value' => $to->toDateString()],
            ],
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Orders', 'values' => $orders, 'color' => '#4f46e5', 'fill' => 'rgba(79, 70, 229, 0.12)'],
                ['label' => 'Revenue', 'values' => $revenue, 'color' => '#10b981', 'fill' => 'rgba(16, 185, 129, 0.10)'],
            ],
            'summary' => [
                ['label' => 'Revenue', 'value' => $this->money((int) Order::whereBetween('created_at', [$from, $to])->sum('price_cents'))],
                ['label' => 'Orders', 'value' => number_format(array_sum($orders))],
                ['label' => 'Best day', 'value' => $this->bestDay($labels, $orders)],
            ],
        ];
    }

    private function hourlyActivityChart(Carbon $day): array
    {
        $hours = collect(range(0, 23));
        $labels = $hours->map(fn (int $hour) => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00')->all();
        $users = $hours
            ->map(fn (int $hour) => User::whereDate('created_at', $day->toDateString())->whereTime('created_at', '>=', sprintf('%02d:00:00', $hour))->whereTime('created_at', '<=', sprintf('%02d:59:59', $hour))->count())
            ->all();
        $orders = $hours
            ->map(fn (int $hour) => Order::whereDate('created_at', $day->toDateString())->whereTime('created_at', '>=', sprintf('%02d:00:00', $hour))->whereTime('created_at', '<=', sprintf('%02d:59:59', $hour))->count())
            ->all();

        return [
            'title' => 'Hourly platform activity',
            'description' => 'User registrations and order creation for the selected day.',
            'controls' => [
                ['label' => 'Day', 'type' => 'date', 'name' => 'visitor_day', 'value' => $day->toDateString()],
            ],
            'labels' => $labels,
            'max' => max(1, max([...$users, ...$orders])),
            'datasets' => [
                ['label' => 'New users', 'values' => $users, 'color' => '#6366f1', 'fill' => 'rgba(99, 102, 241, 0.12)'],
                ['label' => 'New orders', 'values' => $orders, 'color' => '#10b981', 'fill' => 'rgba(16, 185, 129, 0.12)'],
            ],
            'summary' => [
                ['label' => 'New users', 'value' => number_format(array_sum($users))],
                ['label' => 'New orders', 'value' => number_format(array_sum($orders))],
                ['label' => 'Peak hour', 'value' => $this->bestDay($labels, array_map(fn ($userCount, $orderCount) => $userCount + $orderCount, $users, $orders))],
            ],
        ];
    }

    private function profileActivityChart(Carbon $from, Carbon $to): array
    {
        $buckets = $this->dateBuckets($from, $to);
        $labels = $buckets->map(fn (Carbon $day) => $day->format('M d'))->all();
        $newUsers = $buckets
            ->map(fn (Carbon $day) => User::whereDate('created_at', $day->toDateString())->count())
            ->all();
        $verifiedUsers = $buckets
            ->map(fn (Carbon $day) => User::whereDate('email_verified_at', $day->toDateString())->count())
            ->all();
        $newGigs = $buckets
            ->map(fn (Carbon $day) => Gig::whereDate('created_at', $day->toDateString())->count())
            ->all();

        return [
            'title' => 'Profile readiness & gig publishing',
            'description' => 'Users register once, then switch buyer or seller profile activity from the dashboard.',
            'controls' => [
                ['label' => 'From', 'type' => 'date', 'name' => 'profile_from', 'value' => $from->toDateString()],
                ['label' => 'To', 'type' => 'date', 'name' => 'profile_to', 'value' => $to->toDateString()],
            ],
            'labels' => $labels,
            'datasets' => [
                ['label' => 'New users', 'values' => $newUsers, 'color' => '#2563eb', 'fill' => 'rgba(37, 99, 235, 0.10)'],
                ['label' => 'Verified users', 'values' => $verifiedUsers, 'color' => '#10b981', 'fill' => 'rgba(16, 185, 129, 0.10)'],
                ['label' => 'New gigs', 'values' => $newGigs, 'color' => '#f59e0b', 'fill' => 'rgba(245, 158, 11, 0.14)'],
            ],
            'summary' => [
                ['label' => 'New users', 'value' => number_format(array_sum($newUsers))],
                ['label' => 'Verified users', 'value' => number_format(array_sum($verifiedUsers))],
                ['label' => 'New gigs', 'value' => number_format(array_sum($newGigs))],
            ],
        ];
    }

    private function segments(): array
    {
        return Gig::query()
            ->selectRaw('COALESCE(category_label, "Uncategorized") as category')
            ->selectRaw('COUNT(*) as gigs_count')
            ->groupBy('category')
            ->orderByDesc('gigs_count')
            ->take(4)
            ->get()
            ->map(function ($row) {
                $gigIds = Gig::where('category_label', $row->category)->pluck('id');
                $sales = (int) Order::whereIn('gig_id', $gigIds)->sum('price_cents');

                return [
                    'name' => $row->category,
                    'sales' => $this->money($sales),
                    'growth' => number_format($row->gigs_count).' gigs',
                ];
            })
            ->all();
    }

    private function buyerBehavior(): array
    {
        $users = max(1, User::count());
        $savedServices = \DB::table('saved_services')->count();
        $orders = Order::count();
        $repeatBuyers = Order::query()
            ->select('buyer_id')
            ->whereNotNull('buyer_id')
            ->groupBy('buyer_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        return [
            ['label' => 'Save-to-user', 'value' => min(100, (int) round(($savedServices / $users) * 100))],
            ['label' => 'Order-to-user', 'value' => min(100, (int) round(($orders / $users) * 100))],
            ['label' => 'Repeat purchase', 'value' => min(100, (int) round(($repeatBuyers / $users) * 100))],
        ];
    }

    private function dateRange($from, $to, Carbon $defaultFrom, Carbon $defaultTo): array
    {
        $fromDate = $this->dateValue($from, $defaultFrom)->startOfDay();
        $toDate = $this->dateValue($to, $defaultTo)->endOfDay();

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        if ($fromDate->diffInDays($toDate) > 90) {
            $fromDate = $toDate->copy()->subDays(90)->startOfDay();
        }

        return [$fromDate, $toDate];
    }

    private function dateValue($value, Carbon $fallback): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : $fallback->copy();
        } catch (\Throwable) {
            return $fallback->copy();
        }
    }

    private function bestDay(array $labels, array $values): string
    {
        if ($values === []) {
            return 'None';
        }

        $max = max($values);
        $index = array_search($max, $values, true);

        return $labels[$index] ?? 'None';
    }
}
