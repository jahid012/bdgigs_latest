<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\SellerApplicationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SellerApplicationController extends AdminController
{
    private const STATUS_FILTERS = [
        'all' => 'All sellers',
        'not_applied' => 'Not applied',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'suspended' => 'Suspended',
    ];

    private const ACTIVITY_FILTERS = [
        'all' => 'Any activity',
        '7d' => 'Active in 7 days',
        '30d' => 'Active in 30 days',
        'inactive' => 'No recent activity',
    ];

    private const SORT_OPTIONS = [
        'latest' => 'Newest first',
        'oldest' => 'Oldest first',
        'name' => 'Name A-Z',
        'reviewed' => 'Recently reviewed',
        'activity' => 'Recently active',
    ];

    public function index(Request $request)
    {
        $filterState = $this->filterState($request);

        $query = User::query()
            ->with('sellerProfile')
            ->where(function ($users) {
                $users->where('profile_type', 'seller')
                    ->orWhere('seller_status', '!=', 'not_applied');
            });

        $this->applyFilters($query, $filterState);
        $this->applySort($query, $filterState['sort']);

        $perPage = 12;
        $total = (clone $query)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $sellers = $query
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->panelView('admin.pages.seller-applications', [
            'pageTitle' => 'Seller Applications',
            'pageEyebrow' => 'Seller onboarding',
            'pageDescription' => 'Approve, reject, and audit sellers before they publish marketplace services.',
            'searchPlaceholder' => 'Search seller applicants',
            'stats' => [
                ['label' => 'Pending', 'value' => number_format(User::where('seller_status', 'pending')->count()), 'meta' => 'Need review'],
                ['label' => 'Approved', 'value' => number_format(User::where('seller_status', 'approved')->count()), 'meta' => 'Can publish'],
                ['label' => 'Rejected', 'value' => number_format(User::where('seller_status', 'rejected')->count()), 'meta' => 'Can resubmit'],
                ['label' => 'Suspended', 'value' => number_format(User::where('seller_status', 'suspended')->count()), 'meta' => 'Seller access paused'],
            ],
            'sellers' => $sellers,
            'pagination' => $pagination,
            'filters' => collect(array_keys(self::STATUS_FILTERS))->map(fn ($filter) => [
                'label' => self::STATUS_FILTERS[$filter],
                'value' => $filter,
                'count' => $filter === 'all' ? User::where('seller_status', '!=', 'not_applied')->count() : User::where('seller_status', $filter)->count(),
            ])->all(),
            'currentStatus' => $filterState['status'],
            'searchQuery' => $filterState['q'],
            'filterState' => $filterState,
            'countries' => $this->countryOptions(),
            'activityFilters' => self::ACTIVITY_FILTERS,
            'sortOptions' => self::SORT_OPTIONS,
            'hasActiveFilters' => $this->hasActiveFilters($filterState),
            'canBulkReview' => $request->user('admin')?->can('users.verify') ?? false,
        ]);
    }

    public function show(User $user)
    {
        $user->load([
            'sellerProfile',
            'sellerStatusEvents' => fn ($events) => $events->with(['actor', 'adminActor'])->latest(),
            'gigs' => fn ($gigs) => $gigs->latest()->take(8),
        ]);

        return $this->panelView('admin.pages.seller-application-details', [
            'pageTitle' => $user->name ?: $user->email,
            'pageEyebrow' => 'Seller application',
            'pageDescription' => 'Review seller readiness, history, and catalog eligibility.',
            'searchPlaceholder' => 'Search seller applications',
            'seller' => $user,
            'stats' => [
                ['label' => 'Status', 'value' => str($user->seller_status ?: 'not_applied')->replace('_', ' ')->title()->toString(), 'meta' => $user->seller_status_reviewed_at?->diffForHumans() ?? 'No review yet'],
                ['label' => 'Gigs', 'value' => number_format($user->gigs->count()), 'meta' => 'Current catalog records'],
                ['label' => 'Country', 'value' => $user->country ?: 'Unknown', 'meta' => 'Profile location'],
                ['label' => 'Joined', 'value' => $user->created_at?->format('M Y') ?? 'Unknown', 'meta' => 'Account age'],
            ],
        ]);
    }

    public function approve(Request $request, User $user, SellerApplicationService $applications)
    {
        $payload = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $applications->approve($user, $request->user('admin'), $payload['reason'] ?? null);

        return back()->withNotify('success', 'Seller application approved.', 'Seller approved');
    }

    public function reject(Request $request, User $user, SellerApplicationService $applications)
    {
        $payload = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $applications->reject($user, $request->user('admin'), $payload['reason']);

        return back()->withNotify('success', 'Seller application rejected.', 'Seller rejected');
    }

    public function bulkAction(
        Request $request,
        SellerApplicationService $applications
    ) {
        abort_unless($request->user('admin')?->can('users.verify'), 403);

        $payload = $request->validate([
            'bulk_action' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'sellers' => ['required', 'array', 'min:1'],
            'sellers.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:1000', 'required_if:bulk_action,reject'],
        ]);

        $targetStatus = $payload['bulk_action'] === 'approve' ? 'approved' : 'rejected';
        $sellers = User::query()
            ->whereIn('id', $payload['sellers'])
            ->where('seller_status', '!=', $targetStatus)
            ->get();
        $updated = 0;

        foreach ($sellers as $seller) {
            $payload['bulk_action'] === 'approve'
                ? $applications->approve($seller, $request->user('admin'), $payload['reason'] ?? null)
                : $applications->reject($seller, $request->user('admin'), $payload['reason']);
            $updated++;
        }

        if ($updated === 0) {
            return back()->withNotify('warning', 'No seller applications changed.', 'Bulk action skipped');
        }

        return back()->withNotify('success', number_format($updated).' seller '.($updated === 1 ? 'application' : 'applications').' reviewed.', 'Bulk action applied');
    }

    private function filterState(Request $request): array
    {
        $country = trim((string) $request->query('country', 'all'));

        if ($country !== 'all' && ! User::where('country', $country)->exists()) {
            $country = 'all';
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $this->validatedOption($request->query('status', 'pending'), array_keys(self::STATUS_FILTERS), 'pending'),
            'country' => $country,
            'activity' => $this->validatedOption($request->query('activity', 'all'), array_keys(self::ACTIVITY_FILTERS), 'all'),
            'sort' => $this->validatedOption($request->query('sort', 'latest'), array_keys(self::SORT_OPTIONS), 'latest'),
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] !== 'all') {
            $query->where('seller_status', $filters['status']);
        }

        if ($filters['country'] !== 'all') {
            $query->where('country', $filters['country']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function (Builder $users) use ($search) {
                $users
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('seller_status_reason', 'like', "%{$search}%");
            });
        }

        match ($filters['activity']) {
            '7d' => $query->where('last_seen_at', '>=', now()->subDays(7)),
            '30d' => $query->where('last_seen_at', '>=', now()->subDays(30)),
            'inactive' => $query->where(function (Builder $users) {
                $users->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subDays(30));
            }),
            default => null,
        };
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name')->latest(),
            'reviewed' => $query->orderByDesc('seller_status_reviewed_at')->latest(),
            'activity' => $query->orderByDesc('last_seen_at')->latest(),
            default => $query->latest(),
        };
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['status'] !== 'pending'
            || $filters['country'] !== 'all'
            || $filters['activity'] !== 'all'
            || $filters['sort'] !== 'latest';
    }

    private function validatedOption(mixed $value, array $allowed, string $fallback): string
    {
        $value = trim((string) $value);

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function countryOptions()
    {
        return User::query()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');
    }
}
