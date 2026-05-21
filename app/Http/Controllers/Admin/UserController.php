<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends AdminController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', 'all'));
        $allowedTypes = ['all', 'buyers', 'sellers', 'flagged'];
        $type = in_array($type, $allowedTypes, true) ? $type : 'all';

        $usersQuery = User::query()
            ->with(['roles'])
            ->withCount(['gigs', 'buyerOrders', 'sellerOrders'])
            ->latest();

        if ($search !== '') {
            $usersQuery->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
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
            'pageActions' => [
                ['label' => 'All users', 'route' => 'admin.users', 'meta' => number_format(User::count()).' total'],
                ['label' => 'Verify sellers', 'route' => 'admin.users', 'meta' => number_format($reviewUsers).' pending'],
                ['label' => 'Flagged accounts', 'route' => 'admin.users', 'meta' => number_format($suspendedUsers).' suspended'],
            ],
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
            'searchQuery' => $search,
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
        ];
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
