<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends AdminController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', 'all'));
        $status = trim((string) $request->query('status', 'all'));
        $allowedTypes = ['all', 'buyers', 'sellers', 'flagged'];
        $allowedStatuses = ['all', 'active', 'review', 'suspended', 'unverified', 'deactivated'];
        $type = in_array($type, $allowedTypes, true) ? $type : 'all';
        $status = in_array($status, $allowedStatuses, true) ? $status : 'all';

        $usersQuery = User::query()
            ->with(['roles'])
            ->withCount(['gigs', 'buyerOrders', 'sellerOrders'])
            ->latest();

        if ($search !== '') {
            $usersQuery->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        match ($type) {
            'buyers' => $usersQuery->where(function ($query) {
                $query
                    ->where('profile_type', 'buyer')
                    ->orWhereHas('buyerOrders');
            }),
            'sellers' => $usersQuery->where(function ($query) {
                $query
                    ->where('profile_type', 'seller')
                    ->orWhereHas('gigs')
                    ->orWhereHas('sellerOrders');
            }),
            'flagged' => $usersQuery->where(function ($query) {
                $query
                    ->whereNotNull('suspended_at')
                    ->orWhere('verification_status', 'review');
            }),
            default => null,
        };

        match ($status) {
            'active' => $usersQuery
                ->whereNull('suspended_at')
                ->whereNull('deactivated_at')
                ->where('verification_status', '!=', 'review'),
            'review' => $usersQuery->where('verification_status', 'review'),
            'suspended' => $usersQuery->whereNotNull('suspended_at'),
            'unverified' => $usersQuery->whereNull('email_verified_at'),
            'deactivated' => $usersQuery->whereNotNull('deactivated_at'),
            default => null,
        };

        $perPage = 8;
        $total = (clone $usersQuery)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $users = $usersQuery
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn (User $user) => $this->userRow($user))
            ->all();

        $reviewUsers = User::where('verification_status', 'review')->count();
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
            'filters' => [
                ['label' => 'All', 'value' => 'all', 'count' => User::count()],
                ['label' => 'Buyers', 'value' => 'buyers', 'count' => $buyerCount],
                ['label' => 'Sellers', 'value' => 'sellers', 'count' => $sellerCount],
                ['label' => 'Flagged', 'value' => 'flagged', 'count' => $reviewUsers + $suspendedUsers],
            ],
            'currentFilter' => $type,
            'currentStatus' => $status,
            'searchQuery' => $search,
            'statusFilters' => [
                ['label' => 'Any status', 'value' => 'all'],
                ['label' => 'Active', 'value' => 'active'],
                ['label' => 'Awaiting review', 'value' => 'review'],
                ['label' => 'Suspended', 'value' => 'suspended'],
                ['label' => 'Email unverified', 'value' => 'unverified'],
                ['label' => 'Deactivated', 'value' => 'deactivated'],
            ],
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

    public function show(User $user)
    {
        $user->load([
            'roles',
            'buyerProfile',
            'sellerProfile',
            'billingProfile',
            'identityVerificationSubmissions' => fn ($submissions) => $submissions->latest()->take(3),
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
        abort_unless($request->user()?->can('users.impersonate'), 403);

        if (! $this->canImpersonate($user)) {
            return back()->withNotify('error', 'This account cannot be impersonated from the admin panel.', 'Login as blocked');
        }

        $admin = $request->user();

        $request->session()->put([
            'admin_impersonator_id' => $admin->id,
            'admin_impersonator_name' => $admin->name,
            'admin_impersonation_return_to' => route('admin.users.show', $user),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/dashboard')
            ->withNotify('info', 'You are now viewing bdgigs as '.$user->name.'.', 'Impersonation active');
    }

    public function stopImpersonating(Request $request)
    {
        $adminId = $request->session()->get('admin_impersonator_id');
        $returnTo = $request->session()->get('admin_impersonation_return_to', route('admin.users'));
        $admin = $adminId ? User::find($adminId) : null;

        abort_unless($admin?->can('admin.access') && $admin->can('users.impersonate'), 403);

        Auth::login($admin);
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
        $user->forceFill([
            'verification_status' => 'verified',
            'email_verified_at' => $user->email_verified_at ?: now(),
            'suspended_at' => null,
        ])->save();

        return back()->withNotify('success', $user->name.' is now verified.', 'User verified');
    }

    public function suspend(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->withNotify('error', 'You cannot suspend your own admin account.', 'Action blocked');
        }

        $user->forceFill([
            'verification_status' => 'suspended',
            'suspended_at' => now(),
        ])->save();

        return back()->withNotify('success', $user->name.' has been suspended.', 'User suspended');
    }

    public function restore(User $user)
    {
        $user->forceFill([
            'verification_status' => $user->email_verified_at ? 'verified' : 'active',
            'suspended_at' => null,
        ])->save();

        return back()->withNotify('success', $user->name.' has been restored.', 'User restored');
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
            'verification' => str($user->verification_status ?: 'active')->replace('_', ' ')->title()->toString(),
            'country' => $user->country ?: 'Unknown',
            'status' => $user->suspended_at
                ? 'Suspended'
                : str($user->verification_status ?: 'active')->replace('_', ' ')->title()->toString(),
            'status_class' => $user->suspended_at ? 'is-danger' : (($user->verification_status === 'review') ? 'is-warn' : 'is-good'),
            'joined' => $user->created_at?->format('M Y') ?? 'Unknown',
            'can_suspend' => ! $user->is(auth()->user()) && ! $user->suspended_at,
            'can_restore' => (bool) $user->suspended_at,
            'can_impersonate' => $this->canImpersonate($user),
        ];
    }

    private function canImpersonate(User $user): bool
    {
        return auth()->user()?->can('users.impersonate')
            && ! $user->is(auth()->user())
            && ! $user->can('admin.access')
            && ! $user->suspended_at
            && ! $user->deactivated_at;
    }

    private function sellerQuery()
    {
        return User::query()->where(function ($query) {
            $query
                ->where('profile_type', 'seller')
                ->orWhereHas('gigs')
                ->orWhereHas('sellerOrders');
        });
    }

    private function buyerQuery()
    {
        return User::query()->where(function ($query) {
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
