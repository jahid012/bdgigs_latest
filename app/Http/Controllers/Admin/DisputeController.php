<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreAdminDisputeRequest;
use App\Http\Requests\Admin\UpdateAdminDisputeRequest;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use App\Services\AdminDisputeService;
use Illuminate\Http\Request;

class DisputeController extends AdminController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'open'));
        $priority = trim((string) $request->query('priority', 'all'));
        $status = in_array($status, ['all', ...Dispute::STATUSES], true) ? $status : 'open';
        $priority = in_array($priority, ['all', ...Dispute::PRIORITIES], true) ? $priority : 'all';

        $disputesQuery = Dispute::query()
            ->with(['order.buyer', 'order.seller', 'assignedTo'])
            ->latest();

        if ($status !== 'all') {
            $disputesQuery->where('status', $status);
        }

        if ($priority !== 'all') {
            $disputesQuery->where('priority', $priority);
        }

        if ($search !== '') {
            $disputesQuery->where(function ($query) use ($search) {
                $query
                    ->where('case_code', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($orders) use ($search) {
                        $orders
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('service', 'like', "%{$search}%")
                            ->orWhereHas('buyer', fn ($buyers) => $buyers
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%"))
                            ->orWhereHas('seller', fn ($sellers) => $sellers
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%"));
                    });
            });
        }

        $perPage = 8;
        $total = (clone $disputesQuery)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $disputes = $disputesQuery
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get();

        $openCases = Dispute::whereNotIn('status', ['resolved', 'closed'])->count();
        $buyerWaiting = Dispute::where('status', 'waiting_buyer')->count();
        $sellerWaiting = Dispute::where('status', 'waiting_seller')->count();

        return $this->panelView('admin.pages.disputes', [
            'pageTitle' => 'Disputes',
            'pageEyebrow' => 'Resolution center',
            'pageDescription' => 'Prioritize buyer and seller conflicts with evidence, SLA, and refund visibility.',
            'searchPlaceholder' => 'Search disputes, orders, users',
            'stats' => [
                ['label' => 'Open cases', 'value' => number_format($openCases), 'meta' => 'Needs resolution'],
                ['label' => 'Awaiting buyer', 'value' => number_format($buyerWaiting), 'meta' => 'Evidence or reply needed'],
                ['label' => 'Awaiting seller', 'value' => number_format($sellerWaiting), 'meta' => 'Seller follow-up'],
                ['label' => 'Resolved', 'value' => number_format(Dispute::where('status', 'resolved')->count()), 'meta' => 'Closed with decision'],
            ],
            'disputes' => $disputes,
            'pagination' => $pagination,
            'statusFilters' => collect(['all', ...Dispute::STATUSES])->map(fn (string $filter) => [
                'label' => str($filter)->replace('_', ' ')->title()->toString(),
                'value' => $filter,
                'count' => $filter === 'all' ? Dispute::count() : Dispute::where('status', $filter)->count(),
            ])->all(),
            'priorityFilters' => collect(['all', ...Dispute::PRIORITIES])->map(fn (string $filter) => [
                'label' => str($filter)->title()->toString(),
                'value' => $filter,
            ])->all(),
            'currentStatus' => $status,
            'currentPriority' => $priority,
            'searchQuery' => $search,
        ]);
    }

    public function show(Dispute $dispute)
    {
        $dispute->load([
            'order.buyer',
            'order.seller',
            'order.gig',
            'conversation.messages.sender',
            'openedBy',
            'assignedTo',
            'resolvedBy',
            'activities' => fn ($activities) => $activities->with('actor')->latest(),
        ]);

        return $this->panelView('admin.pages.dispute-details', [
            'pageTitle' => 'Dispute '.$dispute->case_code,
            'pageEyebrow' => 'Dispute details',
            'pageDescription' => 'Review linked order context, conversation evidence, assignment, and resolution history.',
            'searchPlaceholder' => 'Search disputes, orders, users',
            'dispute' => $dispute,
            'assignees' => User::permission('admin.access')->orderBy('name')->get(),
            'statusOptions' => Dispute::STATUSES,
            'priorityOptions' => Dispute::PRIORITIES,
            'stats' => [
                ['label' => 'Order value', 'value' => $this->money((int) $dispute->order->price_cents), 'meta' => '#'.$dispute->order->code],
                ['label' => 'Priority', 'value' => str($dispute->priority)->title(), 'meta' => 'Case triage'],
                ['label' => 'Activity', 'value' => number_format($dispute->activities->count()), 'meta' => 'Audit entries'],
                ['label' => 'Age', 'value' => $dispute->created_at?->diffForHumans() ?? 'Unknown', 'meta' => str($dispute->status)->replace('_', ' ')->title()],
            ],
        ]);
    }

    public function store(
        StoreAdminDisputeRequest $request,
        Order $order,
        AdminDisputeService $disputes
    ) {
        $dispute = $disputes->openForOrder($order, $request->user(), $request->validated());

        return redirect()
            ->route('admin.disputes.show', $dispute)
            ->withNotify('success', 'Dispute '.$dispute->case_code.' was opened for order #'.$order->code.'.', 'Dispute opened');
    }

    public function update(
        UpdateAdminDisputeRequest $request,
        Dispute $dispute,
        AdminDisputeService $disputes
    ) {
        $disputes->update($dispute, $request->user(), $request->validated());

        return back()->withNotify('success', 'Dispute '.$dispute->case_code.' was updated.', 'Dispute updated');
    }
}
