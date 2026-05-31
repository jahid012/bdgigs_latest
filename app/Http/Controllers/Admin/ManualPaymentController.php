<?php

namespace App\Http\Controllers\Admin;

use App\Models\ManualPaymentMethod;
use App\Models\ManualPaymentSubmission;
use App\Services\ManualPaymentReviewService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManualPaymentController extends AdminController
{
    private const STATUS_FILTERS = [
        'all' => 'All statuses',
        'pending' => 'Pending review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
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
        'reviewed' => 'Recently reviewed',
    ];

    public function index(Request $request)
    {
        $filterState = $this->filterState($request);

        $query = ManualPaymentSubmission::query()
            ->with(['order', 'buyer', 'method', 'reviewer', 'adminReviewer']);

        $this->applyFilters($query, $filterState);
        $this->applySort($query, $filterState['sort']);

        $perPage = 12;
        $total = (clone $query)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $submissions = $query
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->panelView('admin.pages.manual-payments', [
            'pageTitle' => 'Manual Payments',
            'pageEyebrow' => 'Payment review',
            'pageDescription' => 'Approve buyer payment references before manually paid orders move into delivery.',
            'searchPlaceholder' => 'Search order, buyer, or payment reference',
            'stats' => [
                ['label' => 'Pending review', 'value' => number_format(ManualPaymentSubmission::where('status', 'pending')->count()), 'meta' => 'Needs decision'],
                ['label' => 'Approved', 'value' => number_format(ManualPaymentSubmission::where('status', 'approved')->count()), 'meta' => 'Released to requirements'],
                ['label' => 'Rejected', 'value' => number_format(ManualPaymentSubmission::where('status', 'rejected')->count()), 'meta' => 'Buyer follow-up'],
                ['label' => 'Pending value', 'value' => $this->money((int) ManualPaymentSubmission::where('status', 'pending')->sum('amount_cents')), 'meta' => 'Submitted references'],
            ],
            'filters' => collect(array_keys(self::STATUS_FILTERS))->map(fn (string $filter) => [
                'label' => self::STATUS_FILTERS[$filter],
                'value' => $filter,
                'count' => $filter === 'all'
                    ? ManualPaymentSubmission::count()
                    : ManualPaymentSubmission::where('status', $filter)->count(),
            ])->all(),
            'currentFilter' => $filterState['status'],
            'searchQuery' => $filterState['q'],
            'filterState' => $filterState,
            'amountFilters' => self::AMOUNT_FILTERS,
            'sortOptions' => self::SORT_OPTIONS,
            'submissions' => $submissions,
            'methods' => ManualPaymentMethod::query()->orderBy('sort_order')->orderBy('name')->get(),
            'pagination' => $pagination,
            'hasActiveFilters' => $this->hasActiveFilters($filterState),
            'canBulkReview' => $request->user('admin')?->can('manual-payments.approve') ?? false,
        ]);
    }

    public function bulkAction(
        Request $request,
        ManualPaymentReviewService $reviewer
    ) {
        abort_unless($request->user('admin')?->can('manual-payments.approve'), 403);

        $payload = $request->validate([
            'bulk_action' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'submissions' => ['required', 'array', 'min:1'],
            'submissions.*' => ['required', 'integer', 'distinct', 'exists:manual_payment_submissions,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $submissions = ManualPaymentSubmission::query()
            ->with('order')
            ->whereIn('id', $payload['submissions'])
            ->where('status', 'pending')
            ->get();

        $updated = 0;

        foreach ($submissions as $submission) {
            $reviewer->review(
                $submission,
                $request->user('admin'),
                $payload['bulk_action'],
                $payload['note'] ?? null,
            );
            $updated++;
        }

        if ($updated === 0) {
            return back()->withNotify('warning', 'No pending payment submissions were eligible for that action.', 'Bulk action skipped');
        }

        return back()->withNotify('success', number_format($updated).' payment '.($updated === 1 ? 'submission' : 'submissions').' reviewed.', 'Bulk action applied');
    }

    public function review(
        Request $request,
        ManualPaymentSubmission $submission,
        ManualPaymentReviewService $reviewer
    ) {
        $payload = $request->validate([
            'decision' => ['required', 'string', 'in:approve,reject'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $reviewer->review(
            $submission->loadMissing('order'),
            $request->user('admin'),
            $payload['decision'],
            $payload['note'] ?? null,
        );

        return back()->withNotify(
            'success',
            'Manual payment '.$payload['decision'].'d.',
            'Payment reviewed',
        );
    }

    private function filterState(Request $request): array
    {
        $method = trim((string) $request->query('method', 'all'));

        if ($method !== 'all' && ! ManualPaymentMethod::whereKey($method)->exists()) {
            $method = 'all';
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $this->validatedOption($request->query('status', 'pending'), array_keys(self::STATUS_FILTERS), 'pending'),
            'method' => $method,
            'amount' => $this->validatedOption($request->query('amount', 'all'), array_keys(self::AMOUNT_FILTERS), 'all'),
            'sort' => $this->validatedOption($request->query('sort', 'latest'), array_keys(self::SORT_OPTIONS), 'latest'),
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['method'] !== 'all') {
            $query->where('manual_payment_method_id', (int) $filters['method']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function (Builder $submissionQuery) use ($search) {
                $submissionQuery
                    ->where('reference', 'like', "%{$search}%")
                    ->orWhere('proof_reference', 'like', "%{$search}%")
                    ->orWhereHas('order', fn (Builder $orders) => $orders
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('service', 'like', "%{$search}%"))
                    ->orWhereHas('buyer', fn (Builder $buyers) => $buyers
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
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
            'reviewed' => $query->orderByDesc('reviewed_at')->latest(),
            default => $query->latest(),
        };
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['status'] !== 'pending'
            || $filters['method'] !== 'all'
            || $filters['amount'] !== 'all'
            || $filters['sort'] !== 'latest';
    }

    private function validatedOption(mixed $value, array $allowed, string $fallback): string
    {
        $value = trim((string) $value);

        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
