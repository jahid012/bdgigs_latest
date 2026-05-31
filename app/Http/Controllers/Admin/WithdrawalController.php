<?php

namespace App\Http\Controllers\Admin;

use App\Models\WithdrawalRequest;
use App\Services\ManualWithdrawalReviewService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WithdrawalController extends AdminController
{
    private const STATUS_FILTERS = [
        'all' => 'All statuses',
        'pending' => 'Pending',
        'under_review' => 'Under review',
        'approved' => 'Approved',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
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
        'amount_high' => 'Amount high to low',
        'amount_low' => 'Amount low to high',
        'paid_recently' => 'Recently paid',
    ];

    public function index(Request $request)
    {
        $filterState = $this->filterState($request);

        $query = WithdrawalRequest::query()
            ->with(['seller', 'payoutMethod', 'reviewer', 'adminReviewer', 'payer', 'adminPayer']);

        $this->applyFilters($query, $filterState);
        $this->applySort($query, $filterState['sort']);

        $perPage = 12;
        $total = (clone $query)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $withdrawals = $query
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->panelView('admin.pages.withdrawals', [
            'pageTitle' => 'Withdrawals',
            'pageEyebrow' => 'Manual payouts',
            'pageDescription' => 'Review seller withdrawal requests, record manual payout references, and keep payout history auditable.',
            'searchPlaceholder' => 'Search withdrawal, seller, or payout reference',
            'stats' => [
                ['label' => 'Pending requests', 'value' => number_format(WithdrawalRequest::where('status', 'pending')->count()), 'meta' => 'Needs review'],
                ['label' => 'Approved', 'value' => number_format(WithdrawalRequest::where('status', 'approved')->count()), 'meta' => 'Ready to pay'],
                ['label' => 'Paid to date', 'value' => $this->money($this->paidTotal()), 'meta' => 'Manual references recorded'],
                ['label' => 'Reserved value', 'value' => $this->money((int) WithdrawalRequest::whereIn('status', ['pending', 'under_review', 'approved'])->sum('amount_cents')), 'meta' => 'Held from available balance'],
            ],
            'filters' => collect(array_keys(self::STATUS_FILTERS))->map(fn (string $filter) => [
                'label' => self::STATUS_FILTERS[$filter],
                'value' => $filter,
                'count' => $filter === 'all'
                    ? WithdrawalRequest::count()
                    : WithdrawalRequest::where('status', $filter)->count(),
            ])->all(),
            'currentFilter' => $filterState['status'],
            'searchQuery' => $filterState['q'],
            'filterState' => $filterState,
            'amountFilters' => self::AMOUNT_FILTERS,
            'sortOptions' => self::SORT_OPTIONS,
            'withdrawals' => $withdrawals,
            'pagination' => $pagination,
            'hasActiveFilters' => $this->hasActiveFilters($filterState),
            'canBulkReview' => $request->user('admin')?->can('withdrawals.review') ?? false,
        ]);
    }

    public function bulkAction(
        Request $request,
        ManualWithdrawalReviewService $reviews
    ) {
        abort_unless($request->user('admin')?->can('withdrawals.review'), 403);

        $payload = $request->validate([
            'bulk_action' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'withdrawals' => ['required', 'array', 'min:1'],
            'withdrawals.*' => ['required', 'string', 'distinct', 'exists:withdrawal_requests,code'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $withdrawals = WithdrawalRequest::query()
            ->with('seller')
            ->whereIn('code', $payload['withdrawals'])
            ->whereIn('status', $payload['bulk_action'] === 'approve' ? ['pending', 'under_review'] : ['pending', 'under_review', 'approved'])
            ->get();

        $updated = 0;

        foreach ($withdrawals as $withdrawal) {
            $reviews->decide(
                $withdrawal,
                $request->user('admin'),
                $payload['bulk_action'],
                $payload['note'] ?? null,
            );
            $updated++;
        }

        if ($updated === 0) {
            return back()->withNotify('warning', 'No eligible withdrawal requests changed.', 'Bulk action skipped');
        }

        return back()->withNotify('success', number_format($updated).' withdrawal '.($updated === 1 ? 'request' : 'requests').' reviewed.', 'Bulk action applied');
    }

    public function review(
        Request $request,
        WithdrawalRequest $withdrawal,
        ManualWithdrawalReviewService $reviews
    ) {
        $payload = $request->validate([
            'action' => ['required', 'string', 'in:approve,reject,mark_paid,mark_failed'],
            'note' => ['nullable', 'string', 'max:1000'],
            'payment_reference' => ['nullable', 'string', 'max:180', 'required_if:action,mark_paid'],
        ]);

        if ($payload['action'] === 'mark_paid') {
            abort_unless($request->user('admin')?->can('withdrawals.pay'), 403);
        } else {
            abort_unless($request->user('admin')?->can('withdrawals.review'), 403);
        }

        $reviews->decide(
            $withdrawal->loadMissing('seller'),
            $request->user('admin'),
            $payload['action'],
            $payload['note'] ?? null,
            $payload['payment_reference'] ?? null,
        );

        return back()->withNotify('success', 'Withdrawal queue updated.', 'Withdrawal reviewed');
    }

    private function filterState(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $this->validatedOption($request->query('status', 'pending'), array_keys(self::STATUS_FILTERS), 'pending'),
            'seller' => trim((string) $request->query('seller', '')),
            'amount' => $this->validatedOption($request->query('amount', 'all'), array_keys(self::AMOUNT_FILTERS), 'all'),
            'sort' => $this->validatedOption($request->query('sort', 'latest'), array_keys(self::SORT_OPTIONS), 'latest'),
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function (Builder $withdrawals) use ($search) {
                $withdrawals
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhere('seller_note', 'like', "%{$search}%")
                    ->orWhere('review_note', 'like', "%{$search}%")
                    ->orWhereHas('seller', fn (Builder $sellers) => $sellers
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($filters['seller'] !== '') {
            $seller = $filters['seller'];
            $query->whereHas('seller', fn (Builder $sellers) => $sellers
                ->where('name', 'like', "%{$seller}%")
                ->orWhere('email', 'like', "%{$seller}%")
                ->orWhere('username', 'like', "%{$seller}%"));
        }

        match ($filters['amount']) {
            'under_50' => $query->where('amount_cents', '<', 5000),
            '50_200' => $query->whereBetween('amount_cents', [5000, 19999]),
            '200_plus' => $query->where('amount_cents', '>=', 20000),
            default => null,
        };
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'amount_high' => $query->orderByDesc('amount_cents')->latest(),
            'amount_low' => $query->orderBy('amount_cents')->latest(),
            'paid_recently' => $query->orderByDesc('paid_at')->latest(),
            default => $query->latest(),
        };
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['status'] !== 'pending'
            || $filters['seller'] !== ''
            || $filters['amount'] !== 'all'
            || $filters['sort'] !== 'latest';
    }

    private function validatedOption(mixed $value, array $allowed, string $fallback): string
    {
        $value = trim((string) $value);

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function paidTotal(): int
    {
        return (int) WithdrawalRequest::where('status', 'paid')->get()
            ->sum(fn (WithdrawalRequest $withdrawal) => $withdrawal->approved_amount_cents ?: $withdrawal->amount_cents);
    }
}
