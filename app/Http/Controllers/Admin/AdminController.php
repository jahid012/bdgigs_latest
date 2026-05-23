<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Gig;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

abstract class AdminController extends Controller
{
    protected function panelView(string $view, array $data = [])
    {
        if (! auth()->check()) {
            return redirect()->route('admin.login');
        }

        if (! auth()->user()->can('admin.access')) {
            abort(403);
        }

        $data['healthSummary'] = $data['healthSummary'] ?? $this->healthSummary();

        return view($view, $data);
    }

    protected function paginationMeta(int $total, int $perPage): array
    {
        $lastPage = max(1, (int) ceil($total / $perPage));
        $requestedPage = (int) request()->query('page', 1);
        $currentPage = min(max(1, $requestedPage), $lastPage);
        $from = $total === 0 ? 0 : (($currentPage - 1) * $perPage) + 1;
        $to = min($total, $currentPage * $perPage);

        return [
            'from' => $from,
            'to' => $to,
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $currentPage,
            'lastPage' => $lastPage,
            'pages' => $this->paginationWindow($currentPage, $lastPage),
        ];
    }

    protected function money(int $cents): string
    {
        return '$'.number_format($cents / 100, 0);
    }

    protected function orderRow(Order $order): array
    {
        return [
            'id' => '#'.$order->code,
            'code' => $order->code,
            'route_id' => $order->id,
            'buyer' => $order->buyer?->name ?: $order->buyer_name ?: 'Guest buyer',
            'seller' => $order->seller?->name ?: $order->seller_name ?: 'Unassigned seller',
            'service' => $order->service,
            'status' => $order->status,
            'status_class' => $order->status_class,
            'due' => $order->due_date?->format('M j, Y') ?? 'No due date',
            'amount' => $this->money((int) $order->price_cents),
        ];
    }

    protected function orderStatusClass(string $status): string
    {
        return match (strtolower($status)) {
            'delivered', 'completed' => 'status-completed',
            'cancelled', 'canceled' => 'status-cancelled',
            'revision', 'revision requested', 'pending', 'pending requirements' => 'status-delivered',
            default => 'status-progress',
        };
    }

    protected function gigStatusClass(string $status): string
    {
        return match (strtolower($status)) {
            'live', 'published' => 'status-completed',
            'rejected' => 'status-cancelled',
            'paused', 'pending', 'needs edit', 'review', 'optimize' => 'status-delivered',
            default => 'status-progress',
        };
    }

    protected function dateBuckets(Carbon $from, Carbon $to): Collection
    {
        $days = collect();
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        return $days;
    }

    private function healthSummary(): array
    {
        $openOrders = Order::query()
            ->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled'])
            ->count();
        $lateOrders = Order::query()
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled'])
            ->count();
        $reviewGigs = Gig::query()
            ->whereNotIn('status', ['Live', 'Published'])
            ->count();
        $priorityMessages = Conversation::query()
            ->where(function ($query) {
                $query
                    ->where('buyer_unread_count', '>', 0)
                    ->orWhere('seller_unread_count', '>', 0)
                    ->orWhereNotNull('priority');
            })
            ->count();

        return [
            ['label' => 'Open orders', 'value' => number_format($openOrders)],
            ['label' => 'Late risk', 'value' => number_format($lateOrders)],
            ['label' => 'Review gigs', 'value' => number_format($reviewGigs)],
            ['label' => 'Message queue', 'value' => number_format($priorityMessages)],
        ];
    }

    private function paginationWindow(int $currentPage, int $lastPage): array
    {
        $start = max(1, $currentPage - 2);
        $end = min($lastPage, $start + 4);
        $start = max(1, $end - 4);

        return range($start, $end);
    }
}
