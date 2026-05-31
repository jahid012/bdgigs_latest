<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreAdminDisputeRequest;
use App\Http\Requests\Admin\UpdateAdminDisputeRequest;
use App\Models\Admin;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use App\Services\AdminDisputeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DisputeController extends AdminController
{
    private const ASSIGNEE_FILTERS = [
        'all' => 'Any assignee',
        'unassigned' => 'Unassigned',
        'mine' => 'Assigned to me',
    ];

    private const AGE_FILTERS = [
        'all' => 'Any age',
        'today' => 'Opened today',
        '7d' => 'Opened in 7 days',
        'older_7d' => 'Older than 7 days',
    ];

    private const SORT_OPTIONS = [
        'latest' => 'Newest first',
        'oldest' => 'Oldest first',
        'priority' => 'Priority first',
        'updated' => 'Recently updated',
    ];

    public function index(Request $request)
    {
        $filterState = $this->filterState($request);

        $disputesQuery = Dispute::query()
            ->with(['order.buyer', 'order.seller', 'assignedTo', 'assignedAdmin']);

        $this->applyFilters($disputesQuery, $filterState, $request->user('admin'));
        $this->applySort($disputesQuery, $filterState['sort']);

        $perPage = 8;
        $total = (clone $disputesQuery)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $disputes = $disputesQuery
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get();

        $openCases = Dispute::whereNotIn('status', ['resolved', 'rejected', 'closed'])->count();
        $buyerWaiting = Dispute::where('status', 'evidence_requested')->count();
        $sellerWaiting = Dispute::where('status', 'awaiting_response')->count();

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
            'currentStatus' => $filterState['status'],
            'currentPriority' => $filterState['priority'],
            'searchQuery' => $filterState['q'],
            'filterState' => $filterState,
            'assigneeFilters' => self::ASSIGNEE_FILTERS,
            'ageFilters' => self::AGE_FILTERS,
            'sortOptions' => self::SORT_OPTIONS,
            'assignees' => Admin::permission('admin.access')->orderBy('name')->get(),
            'statusOptions' => Dispute::STATUSES,
            'priorityOptions' => Dispute::PRIORITIES,
            'hasActiveFilters' => $this->hasActiveFilters($filterState),
            'canBulkResolve' => $request->user('admin')?->can('disputes.resolve') ?? false,
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
            'openedByAdmin',
            'assignedTo',
            'assignedAdmin',
            'resolvedBy',
            'resolvedByAdmin',
            'activities' => fn ($activities) => $activities->with(['actor', 'adminActor'])->latest(),
        ]);

        return $this->panelView('admin.pages.dispute-details', [
            'pageTitle' => 'Dispute '.$dispute->case_code,
            'pageEyebrow' => 'Dispute details',
            'pageDescription' => 'Review linked order context, conversation evidence, assignment, and resolution history.',
            'searchPlaceholder' => 'Search disputes, orders, users',
            'dispute' => $dispute,
            'assignees' => Admin::permission('admin.access')->orderBy('name')->get(),
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
        $dispute = $disputes->openForOrder($order, $request->user('admin'), $request->validated());

        return redirect()
            ->route('admin.disputes.show', $dispute)
            ->withNotify('success', 'Dispute '.$dispute->case_code.' was opened for order #'.$order->code.'.', 'Dispute opened');
    }

    public function update(
        UpdateAdminDisputeRequest $request,
        Dispute $dispute,
        AdminDisputeService $disputes
    ) {
        $disputes->update($dispute, $request->user('admin'), $request->validated());

        return back()->withNotify('success', 'Dispute '.$dispute->case_code.' was updated.', 'Dispute updated');
    }

    public function bulkAction(
        Request $request,
        AdminDisputeService $disputes
    ) {
        abort_unless($request->user('admin')?->can('disputes.resolve'), 403);

        $payload = $request->validate([
            'bulk_action' => ['required', 'string', Rule::in(['set_status', 'set_priority', 'assign'])],
            'disputes' => ['required', 'array', 'min:1'],
            'disputes.*' => ['required', 'string', 'distinct', 'exists:disputes,case_code'],
            'status' => ['nullable', 'string', 'required_if:bulk_action,set_status', Rule::in(Dispute::STATUSES)],
            'priority' => ['nullable', 'string', 'required_if:bulk_action,set_priority', Rule::in(Dispute::PRIORITIES)],
            'assigned_to_id' => ['nullable', 'integer', 'exists:admins,id'],
            'resolution' => ['nullable', 'string', 'max:3000', 'required_if:status,resolved,rejected,closed'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $query = Dispute::query()->whereIn('case_code', $payload['disputes']);

        if ($payload['bulk_action'] !== 'set_status') {
            $query->whereNotIn('status', ['resolved', 'rejected', 'closed']);
        }

        $selectedDisputes = $query->get();
        $updated = 0;

        foreach ($selectedDisputes as $dispute) {
            $disputes->update($dispute, $request->user('admin'), [
                'status' => $payload['bulk_action'] === 'set_status' ? $payload['status'] : $dispute->status,
                'priority' => $payload['bulk_action'] === 'set_priority' ? $payload['priority'] : $dispute->priority,
                'assigned_to_id' => $payload['bulk_action'] === 'assign' ? ($payload['assigned_to_id'] ?? null) : $dispute->assigned_to_admin_id,
                'resolution' => $payload['resolution'] ?? $dispute->resolution,
                'note' => $payload['note'] ?? 'Bulk dispute action applied from the admin queue.',
            ]);
            $updated++;
        }

        if ($updated === 0) {
            return back()->withNotify('warning', 'No eligible dispute cases changed.', 'Bulk action skipped');
        }

        return back()->withNotify('success', number_format($updated).' dispute '.($updated === 1 ? 'case' : 'cases').' updated.', 'Bulk action applied');
    }

    public function join(Request $request, Dispute $dispute, AdminDisputeService $disputes)
    {
        $payload = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $disputes->join($dispute->loadMissing('order'), $request->user('admin'), $payload['note'] ?? null);

        return back()->withNotify('success', 'You joined dispute '.$dispute->case_code.'.', 'Admin joined');
    }

    public function requestEvidence(Request $request, Dispute $dispute, AdminDisputeService $disputes)
    {
        $payload = $request->validate([
            'recipient_id' => ['nullable', 'integer', 'exists:users,id'],
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $recipient = isset($payload['recipient_id']) ? User::find($payload['recipient_id']) : null;
        $disputes->requestEvidence($dispute->loadMissing('order.buyer', 'order.seller'), $request->user('admin'), $recipient, $payload['note']);

        return back()->withNotify('success', 'Evidence request sent.', 'Evidence requested');
    }

    public function refund(Request $request, Dispute $dispute, AdminDisputeService $disputes)
    {
        abort_unless($request->user('admin')?->can('payments.release'), 403);

        $payload = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $disputes->issueRefund(
            $dispute->loadMissing('order.buyer', 'order.seller', 'order.gig'),
            $request->user('admin'),
            (int) round(((float) $payload['amount']) * 100),
            $payload['reason'] ?? null,
        );

        return back()->withNotify('success', 'Dispute refund processed.', 'Refund issued');
    }

    private function filterState(Request $request): array
    {
        $assignee = trim((string) $request->query('assignee', 'all'));

        if (! in_array($assignee, array_keys(self::ASSIGNEE_FILTERS), true) && ! Admin::whereKey($assignee)->exists()) {
            $assignee = 'all';
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $this->validatedOption($request->query('status', 'open'), ['all', ...Dispute::STATUSES], 'open'),
            'priority' => $this->validatedOption($request->query('priority', 'all'), ['all', ...Dispute::PRIORITIES], 'all'),
            'assignee' => $assignee,
            'age' => $this->validatedOption($request->query('age', 'all'), array_keys(self::AGE_FILTERS), 'all'),
            'sort' => $this->validatedOption($request->query('sort', 'latest'), array_keys(self::SORT_OPTIONS), 'latest'),
        ];
    }

    private function applyFilters(Builder $query, array $filters, ?Admin $admin): void
    {
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('case_code', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('order', function (Builder $orders) use ($search) {
                        $orders
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('service', 'like', "%{$search}%")
                            ->orWhereHas('buyer', fn (Builder $buyers) => $buyers
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%"))
                            ->orWhereHas('seller', fn (Builder $sellers) => $sellers
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%"));
                    });
            });
        }

        match ($filters['assignee']) {
            'unassigned' => $query->whereNull('assigned_to_admin_id')->whereNull('assigned_to_id'),
            'mine' => $admin ? $query->where('assigned_to_admin_id', $admin->id) : null,
            'all' => null,
            default => $query->where('assigned_to_admin_id', (int) $filters['assignee']),
        };

        match ($filters['age']) {
            'today' => $query->whereDate('created_at', now()->toDateString()),
            '7d' => $query->where('created_at', '>=', now()->subDays(7)),
            'older_7d' => $query->where('created_at', '<', now()->subDays(7)),
            default => null,
        };
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'priority' => $query
                ->orderByRaw("case when priority = 'critical' then 0 when priority = 'high' then 1 else 2 end")
                ->latest(),
            'updated' => $query->orderByDesc('updated_at'),
            default => $query->latest(),
        };
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['status'] !== 'open'
            || $filters['priority'] !== 'all'
            || $filters['assignee'] !== 'all'
            || $filters['age'] !== 'all'
            || $filters['sort'] !== 'latest';
    }

    private function validatedOption(mixed $value, array $allowed, string $fallback): string
    {
        $value = trim((string) $value);

        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
