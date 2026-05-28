<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\SellerApplicationService;
use Illuminate\Http\Request;

class SellerApplicationController extends AdminController
{
    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', 'pending'));
        $allowedStatuses = ['all', 'not_applied', 'pending', 'approved', 'rejected', 'suspended'];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'pending';
        $search = trim((string) $request->query('q', ''));
        $query = User::query()
            ->with('sellerProfile')
            ->where(function ($users) {
                $users->where('profile_type', 'seller')
                    ->orWhere('seller_status', '!=', 'not_applied');
            })
            ->latest();

        if ($status !== 'all') {
            $query->where('seller_status', $status);
        }

        if ($search !== '') {
            $query->where(function ($users) use ($search) {
                $users
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $perPage = 10;
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
            'filters' => collect($allowedStatuses)->map(fn ($filter) => [
                'label' => str($filter)->replace('_', ' ')->title()->toString(),
                'value' => $filter,
                'count' => $filter === 'all' ? User::where('seller_status', '!=', 'not_applied')->count() : User::where('seller_status', $filter)->count(),
            ])->all(),
            'currentStatus' => $status,
            'searchQuery' => $search,
        ]);
    }

    public function show(User $user)
    {
        $user->load([
            'sellerProfile',
            'sellerStatusEvents' => fn ($events) => $events->with('actor')->latest(),
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
        $applications->approve($user, $request->user(), $payload['reason'] ?? null);

        return back()->withNotify('success', 'Seller application approved.', 'Seller approved');
    }

    public function reject(Request $request, User $user, SellerApplicationService $applications)
    {
        $payload = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $applications->reject($user, $request->user(), $payload['reason']);

        return back()->withNotify('success', 'Seller application rejected.', 'Seller rejected');
    }
}
