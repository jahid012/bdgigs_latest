<?php

namespace App\Http\Controllers\Admin;

use App\Events\EmailVerified;
use App\Http\Requests\Admin\UpdateAdminUserStatusRequest;
use App\Models\IdentityVerificationSubmission;
use App\Models\User;
use App\Services\AccountStatusService;
use App\Services\IdentityVerificationReviewService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends AdminController
{
    private const TYPE_FILTERS = [
        'all' => 'All',
        'buyers' => 'Buyers',
        'sellers' => 'Sellers',
        'flagged' => 'Flagged',
    ];

    private const STATUS_FILTERS = [
        'all' => 'Any status',
        'active' => 'Active',
        'review' => 'Awaiting review',
        'submitted' => 'Submitted',
        'under_review' => 'Under review',
        'suspended' => 'Suspended',
        'unverified' => 'Email unverified',
        'deactivated' => 'Deactivated',
    ];

    private const SELLER_STATUS_FILTERS = [
        'all' => 'Any seller state',
        'not_applied' => 'Not applied',
        'pending' => 'Pending seller review',
        'approved' => 'Approved seller',
        'rejected' => 'Rejected seller',
        'suspended' => 'Seller suspended',
    ];

    private const EMAIL_FILTERS = [
        'all' => 'Any email state',
        'verified' => 'Email verified',
        'unverified' => 'Email unverified',
    ];

    private const ACTIVITY_FILTERS = [
        'all' => 'Any activity',
        'active_7d' => 'Seen in 7 days',
        'active_30d' => 'Seen in 30 days',
        'never_seen' => 'Never seen',
    ];

    private const JOINED_FILTERS = [
        'all' => 'Any join date',
        '7d' => 'Joined last 7 days',
        '30d' => 'Joined last 30 days',
        'month' => 'Joined this month',
        'year' => 'Joined this year',
    ];

    private const SORT_OPTIONS = [
        'latest' => 'Newest first',
        'oldest' => 'Oldest first',
        'name' => 'Name A-Z',
        'last_seen' => 'Last seen',
        'gigs' => 'Most gigs',
        'buyer_orders' => 'Most buyer orders',
        'seller_orders' => 'Most seller orders',
    ];

    private const BULK_ACTIONS = [
        'verify',
        'suspend',
        'restore',
        'deactivate',
    ];

    public function index(Request $request)
    {
        $filterState = $this->filterState($request);

        $usersQuery = User::query()
            ->withCount(['gigs', 'buyerOrders', 'sellerOrders']);

        $this->applyUserFilters($usersQuery, $filterState);
        $this->applyUserSort($usersQuery, $filterState['sort']);

        $perPage = 12;
        $total = (clone $usersQuery)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $users = $usersQuery
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn (User $user) => $this->userRow($user))
            ->all();

        $reviewUsers = User::whereIn('verification_status', ['review', 'submitted', 'under_review', 'additional_document_required'])->count();
        $suspendedUsers = User::whereNotNull('suspended_at')->count();
        $sellerCount = $this->sellerQuery()->count();
        $buyerCount = $this->buyerQuery()->count();

        return $this->panelView('admin.pages.users', [
            'pageTitle' => 'Users',
            'pageEyebrow' => 'People operations',
            'pageDescription' => 'Manage buyer and seller lifecycle, verification, account health, and trust signals.',
            'searchPlaceholder' => 'Search buyers, sellers, emails',
            'stats' => [
                ['label' => 'Total users', 'value' => number_format(User::count()), 'meta' => number_format(User::where('created_at', '>=', now()->startOfMonth())->count()).' this month'],
                ['label' => 'Active sellers', 'value' => number_format($sellerCount), 'meta' => number_format($reviewUsers).' awaiting review'],
                ['label' => 'New buyers', 'value' => number_format($this->buyerQuery()->where('created_at', '>=', now()->subDays(7))->count()), 'meta' => 'Last 7 days'],
                ['label' => 'Flagged accounts', 'value' => number_format($suspendedUsers), 'meta' => 'Suspended users'],
            ],
            'users' => $users,
            'pagination' => $pagination,
            'filters' => $this->typeFilters($reviewUsers, $suspendedUsers, $buyerCount, $sellerCount),
            'currentFilter' => $filterState['type'],
            'currentStatus' => $filterState['status'],
            'searchQuery' => $filterState['q'],
            'filterState' => $filterState,
            'statusFilters' => $this->statusFilters(),
            'sellerStatusFilters' => self::SELLER_STATUS_FILTERS,
            'emailFilters' => self::EMAIL_FILTERS,
            'activityFilters' => self::ACTIVITY_FILTERS,
            'joinedFilters' => self::JOINED_FILTERS,
            'sortOptions' => self::SORT_OPTIONS,
            'countryOptions' => $this->countryOptions(),
            'hasActiveFilters' => $this->hasActiveFilters($filterState),
            'verificationFocus' => [
                ['value' => number_format($reviewUsers), 'label' => 'Users awaiting review'],
                ['value' => number_format($suspendedUsers), 'label' => 'Suspended accounts'],
                ['value' => number_format(User::whereNull('email_verified_at')->count()), 'label' => 'Unverified emails'],
            ],
            'verificationPipeline' => [
                ['label' => 'Email verified', 'value' => $this->percent(User::whereNotNull('email_verified_at')->count(), User::count())],
                ['label' => 'Profile classified', 'value' => $this->percent(User::whereNotNull('profile_type')->count(), User::count())],
                ['label' => 'Account clear', 'value' => $this->percent(User::whereNull('suspended_at')->count(), User::count())],
            ],
        ]);
    }

    public function bulkAction(Request $request, AccountStatusService $accounts)
    {
        $payload = $request->validate([
            'bulk_action' => ['required', 'string', Rule::in(self::BULK_ACTIONS)],
            'users' => ['required', 'array', 'min:1'],
            'users.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $action = $payload['bulk_action'];
        $reason = trim((string) ($payload['reason'] ?? ''));

        if (in_array($action, ['suspend', 'deactivate'], true) && $reason === '') {
            return back()
                ->withInput()
                ->withErrors(['reason' => 'A reason is required for this bulk action.'])
                ->withNotify('error', 'Add a reason before applying this bulk action.', 'Bulk action needs a reason');
        }

        abort_unless($this->adminCanRunBulkAction($request, $action), 403);

        $selectedUsers = User::query()
            ->whereIn('id', $payload['users'])
            ->get();

        $updated = 0;
        $skipped = max(0, count($payload['users']) - $selectedUsers->count());

        foreach ($selectedUsers as $user) {
            if (! $this->userCanReceiveBulkAction($user, $action)) {
                $skipped++;

                continue;
            }

            match ($action) {
                'verify' => $this->verifyUser($user),
                'suspend' => $accounts->suspend($user, $request->user('admin'), $reason),
                'restore' => $accounts->reactivate($user, $request->user('admin'), $reason !== '' ? $reason : null),
                'deactivate' => $accounts->deactivate($user, $request->user('admin'), $reason),
            };

            $updated++;
        }

        if ($updated === 0) {
            return back()->withNotify('warning', 'No eligible users were changed by that bulk action.', 'Bulk action skipped');
        }

        $message = number_format($updated).' '.($updated === 1 ? 'user' : 'users').' updated.';

        if ($skipped > 0) {
            $message .= ' '.number_format($skipped).' skipped.';
        }

        return back()->withNotify('success', $message, 'Bulk action applied');
    }

    public function show(User $user)
    {
        $user->load([
            'buyerProfile',
            'sellerProfile',
            'billingProfile',
            'identityVerificationSubmissions' => fn ($submissions) => $submissions->latest()->take(3),
            'accountStatusEvents' => fn ($events) => $events->with(['actor', 'adminActor'])->latest()->take(10),
        ])->loadCount(['gigs', 'buyerOrders', 'sellerOrders', 'savedServices']);

        return $this->panelView('admin.pages.user-details', [
            'pageTitle' => $user->name ?: $user->email,
            'pageEyebrow' => 'User details',
            'pageDescription' => 'Inspect account identity, marketplace activity, profile data, and operational actions before intervening.',
            'searchPlaceholder' => 'Search admin',
            'targetUser' => $user,
            'account' => $this->userRow($user),
            'impersonationAllowed' => $this->canImpersonate($user),
            'stats' => [
                ['label' => 'Seller gigs', 'value' => number_format($user->gigs_count), 'meta' => 'Current catalog'],
                ['label' => 'Buyer orders', 'value' => number_format($user->buyer_orders_count), 'meta' => 'Placed orders'],
                ['label' => 'Seller orders', 'value' => number_format($user->seller_orders_count), 'meta' => 'Fulfillment history'],
                ['label' => 'Saved services', 'value' => number_format($user->saved_services_count), 'meta' => 'Shortlisted gigs'],
            ],
            'recentBuyerOrders' => $user->buyerOrders()->latest()->take(5)->get()->map(fn ($order) => $this->orderRow($order)),
            'recentSellerOrders' => $user->sellerOrders()->latest()->take(5)->get()->map(fn ($order) => $this->orderRow($order)),
            'recentGigs' => $user->gigs()->latest()->take(5)->get(),
        ]);
    }

    public function impersonate(Request $request, User $user)
    {
        abort_unless($request->user('admin')?->can('users.impersonate'), 403);

        if (! $this->canImpersonate($user)) {
            return back()->withNotify('error', 'This account cannot be impersonated from the admin panel.', 'Login as blocked');
        }

        $admin = $request->user('admin');

        $request->session()->put([
            'admin_impersonator_id' => $admin->id,
            'admin_impersonator_name' => $admin->name,
            'admin_impersonation_return_to' => route('admin.users.show', $user),
        ]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect('/dashboard')
            ->withNotify('info', 'You are now viewing bdgigs as '.$user->name.'.', 'Impersonation active');
    }

    public function stopImpersonating(Request $request)
    {
        $adminId = $request->session()->get('admin_impersonator_id');
        $returnTo = $request->session()->get('admin_impersonation_return_to', route('admin.users'));
        $admin = $request->user('admin');

        abort_unless($admin && (int) $admin->id === (int) $adminId && $admin->can('users.impersonate'), 403);

        Auth::guard('web')->logout();
        $request->session()->forget([
            'admin_impersonator_id',
            'admin_impersonator_name',
            'admin_impersonation_return_to',
        ]);
        $request->session()->regenerate();

        return redirect($returnTo)
            ->withNotify('success', 'Returned to your admin account.', 'Impersonation ended');
    }

    public function verify(User $user)
    {
        $this->verifyUser($user);

        return back()->withNotify('success', $user->name.' is now verified.', 'User verified');
    }

    public function suspend(UpdateAdminUserStatusRequest $request, User $user, AccountStatusService $accounts)
    {
        $accounts->suspend($user, $request->user('admin'), $request->validated('reason'));

        return back()->withNotify('success', $user->name.' has been suspended.', 'User suspended');
    }

    public function restore(UpdateAdminUserStatusRequest $request, User $user, AccountStatusService $accounts)
    {
        $accounts->reactivate($user, $request->user('admin'), $request->validated('reason'));

        return back()->withNotify('success', $user->name.' has been restored.', 'User restored');
    }

    public function deactivate(UpdateAdminUserStatusRequest $request, User $user, AccountStatusService $accounts)
    {
        $accounts->deactivate($user, $request->user('admin'), $request->validated('reason'));

        return back()->withNotify('success', $user->name.' has been deactivated.', 'User deactivated');
    }

    public function reviewIdentity(
        Request $request,
        User $user,
        IdentityVerificationSubmission $submission,
        IdentityVerificationReviewService $reviews
    ) {
        abort_unless((int) $submission->user_id === (int) $user->id, 404);

        $payload = $request->validate([
            'action' => ['required', 'string', 'in:under_review,approve,reject,request_documents'],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:action,reject,request_documents'],
        ]);

        match ($payload['action']) {
            'approve' => $reviews->approve($submission, $request->user('admin'), $payload['note'] ?? null),
            'reject' => $reviews->reject($submission, $request->user('admin'), $payload['note']),
            'request_documents' => $reviews->requestAdditionalDocument($submission, $request->user('admin'), $payload['note']),
            default => $reviews->markUnderReview($submission, $request->user('admin')),
        };

        return back()->withNotify('success', 'Identity verification updated.', 'Identity reviewed');
    }

    private function userRow(User $user): array
    {
        $profileType = $user->profile_type ?: match (true) {
            $user->gigs_count > 0 || $user->seller_orders_count > 0 => 'seller',
            $user->buyer_orders_count > 0 => 'buyer',
            default => 'buyer',
        };
        $sellerLevel = $profileType === 'seller'
            ? ($user->gigs()->latest()->value('seller_level') ?: 'New Seller')
            : 'Buyer profile';

        return [
            'id' => $user->id,
            'name' => $user->name ?: $user->email,
            'email' => $user->email,
            'profile_type' => str($profileType)->title()->toString(),
            'seller_level' => $sellerLevel,
            'seller_status' => str($user->seller_status ?: 'not_applied')->replace('_', ' ')->title()->toString(),
            'verification' => str($user->verification_status ?: 'active')->replace('_', ' ')->title()->toString(),
            'email_verified' => (bool) $user->email_verified_at,
            'country' => $user->country ?: 'Unknown',
            'status' => $user->suspended_at
                ? 'Suspended'
                : ($user->deactivated_at ? 'Deactivated' : str($user->verification_status ?: 'active')->replace('_', ' ')->title()->toString()),
            'status_class' => ($user->suspended_at || $user->deactivated_at) ? 'is-danger' : (in_array($user->verification_status, ['review', 'submitted', 'under_review', 'additional_document_required'], true) ? 'is-warn' : 'is-good'),
            'joined' => $user->created_at?->format('M j, Y') ?? 'Unknown',
            'last_seen' => $user->last_seen_at?->diffForHumans() ?? 'Never',
            'metrics' => [
                'gigs' => (int) ($user->gigs_count ?? 0),
                'buyer_orders' => (int) ($user->buyer_orders_count ?? 0),
                'seller_orders' => (int) ($user->seller_orders_count ?? 0),
            ],
            'can_suspend' => ! $user->suspended_at && ! $user->deactivated_at,
            'can_deactivate' => ! $user->deactivated_at,
            'can_restore' => (bool) ($user->suspended_at || $user->deactivated_at),
            'can_impersonate' => $this->canImpersonate($user),
        ];
    }

    private function verifyUser(User $user): User
    {
        $wasUnverified = ! $user->email_verified_at;

        $user->forceFill([
            'verification_status' => 'verified',
            'email_verified_at' => $user->email_verified_at ?: now(),
            'suspended_at' => null,
        ])->save();

        if ($wasUnverified) {
            event(new EmailVerified($user->fresh()));
        }

        return $user->fresh();
    }

    private function filterState(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'type' => $this->validatedOption($request->query('type', 'all'), array_keys(self::TYPE_FILTERS), 'all'),
            'status' => $this->validatedOption($request->query('status', 'all'), array_keys(self::STATUS_FILTERS), 'all'),
            'seller_status' => $this->validatedOption($request->query('seller_status', 'all'), array_keys(self::SELLER_STATUS_FILTERS), 'all'),
            'email' => $this->validatedOption($request->query('email', 'all'), array_keys(self::EMAIL_FILTERS), 'all'),
            'country' => trim((string) $request->query('country', '')),
            'activity' => $this->validatedOption($request->query('activity', 'all'), array_keys(self::ACTIVITY_FILTERS), 'all'),
            'joined' => $this->validatedOption($request->query('joined', 'all'), array_keys(self::JOINED_FILTERS), 'all'),
            'sort' => $this->validatedOption($request->query('sort', 'latest'), array_keys(self::SORT_OPTIONS), 'latest'),
        ];
    }

    private function applyUserFilters(Builder $query, array $filters): void
    {
        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        match ($filters['type']) {
            'buyers' => $this->applyBuyerFilter($query),
            'sellers' => $this->applySellerFilter($query),
            'flagged' => $query->where(function (Builder $query) {
                $query
                    ->whereNotNull('suspended_at')
                    ->orWhereNotNull('deactivated_at')
                    ->orWhereIn('verification_status', ['review', 'submitted', 'under_review', 'additional_document_required']);
            }),
            default => null,
        };

        match ($filters['status']) {
            'active' => $query
                ->whereNull('suspended_at')
                ->whereNull('deactivated_at')
                ->whereNotIn('verification_status', ['review', 'submitted', 'under_review', 'additional_document_required', 'suspended', 'deactivated']),
            'review' => $query->whereIn('verification_status', ['review', 'submitted', 'under_review', 'additional_document_required']),
            'submitted' => $query->where('verification_status', 'submitted'),
            'under_review' => $query->whereIn('verification_status', ['under_review', 'review']),
            'suspended' => $query->whereNotNull('suspended_at'),
            'unverified' => $query->whereNull('email_verified_at'),
            'deactivated' => $query->whereNotNull('deactivated_at'),
            default => null,
        };

        if ($filters['seller_status'] !== 'all') {
            $query->where('seller_status', $filters['seller_status']);
        }

        match ($filters['email']) {
            'verified' => $query->whereNotNull('email_verified_at'),
            'unverified' => $query->whereNull('email_verified_at'),
            default => null,
        };

        if ($filters['country'] !== '') {
            $query->where('country', $filters['country']);
        }

        match ($filters['activity']) {
            'active_7d' => $query->where('last_seen_at', '>=', now()->subDays(7)),
            'active_30d' => $query->where('last_seen_at', '>=', now()->subDays(30)),
            'never_seen' => $query->whereNull('last_seen_at'),
            default => null,
        };

        match ($filters['joined']) {
            '7d' => $query->where('created_at', '>=', now()->subDays(7)),
            '30d' => $query->where('created_at', '>=', now()->subDays(30)),
            'month' => $query->where('created_at', '>=', now()->startOfMonth()),
            'year' => $query->where('created_at', '>=', now()->startOfYear()),
            default => null,
        };
    }

    private function applyUserSort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name')->orderBy('email'),
            'last_seen' => $query->orderByDesc('last_seen_at')->latest(),
            'gigs' => $query->orderByDesc('gigs_count')->latest(),
            'buyer_orders' => $query->orderByDesc('buyer_orders_count')->latest(),
            'seller_orders' => $query->orderByDesc('seller_orders_count')->latest(),
            default => $query->latest(),
        };
    }

    private function typeFilters(int $reviewUsers, int $suspendedUsers, int $buyerCount, int $sellerCount): array
    {
        return [
            ['label' => self::TYPE_FILTERS['all'], 'value' => 'all', 'count' => User::count()],
            ['label' => self::TYPE_FILTERS['buyers'], 'value' => 'buyers', 'count' => $buyerCount],
            ['label' => self::TYPE_FILTERS['sellers'], 'value' => 'sellers', 'count' => $sellerCount],
            ['label' => self::TYPE_FILTERS['flagged'], 'value' => 'flagged', 'count' => $reviewUsers + $suspendedUsers],
        ];
    }

    private function statusFilters(): array
    {
        return collect(self::STATUS_FILTERS)
            ->map(fn (string $label, string $value) => [
                'label' => $label,
                'value' => $value,
            ])
            ->values()
            ->all();
    }

    private function countryOptions(): array
    {
        return User::query()
            ->whereNotNull('country')
            ->where('country', '<>', '')
            ->distinct()
            ->orderBy('country')
            ->pluck('country')
            ->all();
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['type'] !== 'all'
            || $filters['status'] !== 'all'
            || $filters['seller_status'] !== 'all'
            || $filters['email'] !== 'all'
            || $filters['country'] !== ''
            || $filters['activity'] !== 'all'
            || $filters['joined'] !== 'all'
            || $filters['sort'] !== 'latest';
    }

    private function adminCanRunBulkAction(Request $request, string $action): bool
    {
        $admin = $request->user('admin');

        if (! $admin) {
            return false;
        }

        return $action === 'verify'
            ? $admin->can('users.verify')
            : $admin->can('users.suspend');
    }

    private function userCanReceiveBulkAction(User $user, string $action): bool
    {
        return match ($action) {
            'verify' => ! $user->suspended_at
                && ! $user->deactivated_at
                && (! $user->email_verified_at || $user->verification_status !== 'verified'),
            'suspend' => ! $user->suspended_at && ! $user->deactivated_at,
            'restore' => (bool) ($user->suspended_at || $user->deactivated_at),
            'deactivate' => ! $user->deactivated_at,
            default => false,
        };
    }

    private function validatedOption(mixed $value, array $allowed, string $fallback): string
    {
        $value = trim((string) $value);

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function canImpersonate(User $user): bool
    {
        return auth('admin')->user()?->can('users.impersonate')
            && ! $user->suspended_at
            && ! $user->deactivated_at;
    }

    private function sellerQuery()
    {
        return tap(User::query(), fn (Builder $query) => $this->applySellerFilter($query));
    }

    private function buyerQuery()
    {
        return tap(User::query(), fn (Builder $query) => $this->applyBuyerFilter($query));
    }

    private function applySellerFilter(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query
                ->where('profile_type', 'seller')
                ->orWhereHas('gigs')
                ->orWhereHas('sellerOrders');
        });
    }

    private function applyBuyerFilter(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query
                ->where('profile_type', 'buyer')
                ->orWhereHas('buyerOrders');
        });
    }

    private function percent(int $value, int $total): int
    {
        return $total === 0 ? 0 : (int) round(($value / $total) * 100);
    }
}
