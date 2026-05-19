<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Support\PlatformSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminPanelController extends Controller
{
    public function dashboard()
    {
        return $this->panelView('admin.dashboard', [
            'pageTitle' => 'Marketplace overview',
            'pageEyebrow' => 'Admin dashboard',
            'pageDescription' => 'Live operational snapshot for orders, moderation, payouts, and marketplace trust.',
            'searchPlaceholder' => 'Search users, gigs, orders',
            'pageActions' => $this->defaultActions(),
            'briefing' => [
                ['label' => 'Today revenue pace', 'value' => '$6,420', 'meta' => '78% of daily target'],
                ['label' => 'Trust risk', 'value' => 'Medium', 'meta' => '6 urgent cases'],
                ['label' => 'SLA health', 'value' => '92%', 'meta' => '23 late-risk orders'],
            ],
            'health' => [
                ['label' => 'Payments', 'value' => 'Stable', 'tone' => 'good'],
                ['label' => 'Messaging', 'value' => 'Healthy', 'tone' => 'good'],
                ['label' => 'Moderation', 'value' => 'Backlog', 'tone' => 'warn'],
                ['label' => 'Support', 'value' => 'Busy', 'tone' => 'warn'],
            ],
            'stats' => [
                ['label' => 'Gross sales', 'value' => '$42,860', 'meta' => '+12.8% this month'],
                ['label' => 'Open orders', 'value' => '386', 'meta' => '42 due today'],
                ['label' => 'Pending gigs', 'value' => '74', 'meta' => 'Need review'],
                ['label' => 'Support tickets', 'value' => '19', 'meta' => '6 urgent'],
            ],
            'orders' => $this->ordersData(),
            'pagination' => $this->paginationMeta(386, 4),
            'activities' => [
                'Three new seller verification requests are waiting.',
                'A buyer opened a dispute for order #BD-1017.',
                'Six gig images were flagged for manual review.',
                'Payout batch for local bank transfer is ready.',
            ],
        ]);
    }

    public function users()
    {
        return $this->panelView('admin.pages.users', [
            'pageTitle' => 'Users',
            'pageEyebrow' => 'People operations',
            'pageDescription' => 'Manage buyer and seller lifecycle, verification, account health, and trust signals.',
            'searchPlaceholder' => 'Search buyers, sellers, emails',
            'pageActions' => [
                ['label' => 'Add user', 'route' => 'admin.users', 'meta' => 'Manual invite'],
                ['label' => 'Verify sellers', 'route' => 'admin.users', 'meta' => '18 pending'],
                ['label' => 'Export users', 'route' => 'admin.users', 'meta' => 'CSV'],
            ],
            'stats' => [
                ['label' => 'Total users', 'value' => '18,420', 'meta' => '+420 this month'],
                ['label' => 'Active sellers', 'value' => '2,860', 'meta' => '81 awaiting review'],
                ['label' => 'New buyers', 'value' => '734', 'meta' => 'Last 7 days'],
                ['label' => 'Flagged accounts', 'value' => '16', 'meta' => 'Needs attention'],
            ],
            'users' => [
                ['name' => 'Hasan', 'email' => 'hasan@example.com', 'profile_type' => 'Seller', 'seller_level' => 'Level 2', 'verification' => 'Verified', 'country' => 'Bangladesh', 'status' => 'Verified', 'joined' => 'Nov 2019'],
                ['name' => 'Ahmad', 'email' => 'ahmad@example.com', 'profile_type' => 'Seller', 'seller_level' => 'Level 1', 'verification' => 'Review', 'country' => 'Pakistan', 'status' => 'Review', 'joined' => 'Aug 2020'],
                ['name' => 'Nadia Islam', 'email' => 'nadia@example.com', 'profile_type' => 'Buyer', 'seller_level' => 'Buyer profile', 'verification' => 'Active', 'country' => 'Bangladesh', 'status' => 'Active', 'joined' => 'Jan 2024'],
                ['name' => 'Hamza Saleem', 'email' => 'hamza@example.com', 'profile_type' => 'Seller', 'seller_level' => 'New Seller', 'verification' => 'Paused', 'country' => 'Pakistan', 'status' => 'Paused', 'joined' => 'May 2022'],
            ],
            'pagination' => $this->paginationMeta(18420, 4),
        ]);
    }

    public function gigs()
    {
        return $this->panelView('admin.pages.gigs', [
            'pageTitle' => 'Gigs',
            'pageEyebrow' => 'Catalog moderation',
            'pageDescription' => 'Review gig quality, publishing readiness, category fit, and content safety.',
            'searchPlaceholder' => 'Search gigs, categories, sellers',
            'pageActions' => [
                ['label' => 'Review queue', 'route' => 'admin.gigs', 'meta' => '74 pending'],
                ['label' => 'Featured rotation', 'route' => 'admin.gigs', 'meta' => '28 active'],
                ['label' => 'Category audit', 'route' => 'admin.gigs', 'meta' => 'Weekly'],
            ],
            'stats' => [
                ['label' => 'Published gigs', 'value' => '2,402', 'meta' => '+36 today'],
                ['label' => 'Pending review', 'value' => '74', 'meta' => 'Oldest 3h ago'],
                ['label' => 'Rejected', 'value' => '12', 'meta' => 'Last 24 hours'],
                ['label' => 'Featured gigs', 'value' => '28', 'meta' => 'Homepage rotation'],
            ],
            'gigs' => [
                ['title' => 'AI website and chatbot software', 'seller' => 'Wiznic Solution', 'category' => 'AI Development', 'price' => '$150', 'status' => 'Pending'],
                ['title' => 'Install codecanyon PHP script', 'seller' => 'Biswajit N', 'category' => 'Script Development', 'price' => '$10', 'status' => 'Published'],
                ['title' => 'WordPress website redesign', 'seller' => 'Mark', 'category' => 'Website Development', 'price' => '$80', 'status' => 'Needs edit'],
                ['title' => 'Mobile app UI design', 'seller' => 'Tecbeck', 'category' => 'Mobile Apps', 'price' => '$115', 'status' => 'Published'],
            ],
            'pagination' => $this->paginationMeta(2402, 4),
        ]);
    }

    public function orders()
    {
        return $this->panelView('admin.pages.orders', [
            'pageTitle' => 'Orders',
            'pageEyebrow' => 'Delivery operations',
            'pageDescription' => 'Track order status, due dates, revision risk, cancellations, and buyer experience.',
            'searchPlaceholder' => 'Search orders, buyers, sellers',
            'pageActions' => [
                ['label' => 'Late-risk queue', 'route' => 'admin.orders', 'meta' => '23 orders'],
                ['label' => 'Message buyers', 'route' => 'admin.orders', 'meta' => 'Requirements'],
                ['label' => 'Export orders', 'route' => 'admin.orders', 'meta' => 'CSV'],
            ],
            'stats' => [
                ['label' => 'Orders today', 'value' => '186', 'meta' => '+18% vs yesterday'],
                ['label' => 'Late risk', 'value' => '23', 'meta' => 'Needs follow-up'],
                ['label' => 'Delivered', 'value' => '92', 'meta' => 'Today'],
                ['label' => 'Cancelled', 'value' => '5', 'meta' => 'Under target'],
            ],
            'orders' => $this->ordersData(),
            'pagination' => $this->paginationMeta(386, 4),
        ]);
    }

    public function payments()
    {
        return $this->panelView('admin.pages.payments', [
            'pageTitle' => 'Payments',
            'pageEyebrow' => 'Finance desk',
            'pageDescription' => 'Monitor platform balance, payout readiness, holds, refunds, and transaction health.',
            'searchPlaceholder' => 'Search payouts, invoices, transactions',
            'pageActions' => [
                ['label' => 'Release payouts', 'route' => 'admin.payments', 'meta' => '$86.4k ready'],
                ['label' => 'Review holds', 'route' => 'admin.payments', 'meta' => '8 holds'],
                ['label' => 'Finance report', 'route' => 'admin.payments', 'meta' => 'Monthly'],
            ],
            'stats' => [
                ['label' => 'Available balance', 'value' => '$86,420', 'meta' => 'Ready to release'],
                ['label' => 'Pending payouts', 'value' => '$18,905', 'meta' => '43 sellers'],
                ['label' => 'Marketplace fees', 'value' => '$7,820', 'meta' => 'This month'],
                ['label' => 'Refunds', 'value' => '$1,260', 'meta' => '8 transactions'],
            ],
            'payments' => [
                ['id' => 'PAY-9201', 'seller' => 'Wiznic Solution', 'method' => 'Bank transfer', 'amount' => '$540', 'status' => 'Ready'],
                ['id' => 'PAY-9200', 'seller' => 'Ahmad', 'method' => 'Payoneer', 'amount' => '$1,120', 'status' => 'Processing'],
                ['id' => 'PAY-9199', 'seller' => 'Deal With Code', 'method' => 'Bank transfer', 'amount' => '$260', 'status' => 'Held'],
            ],
            'pagination' => $this->paginationMeta(43, 3),
        ]);
    }

    public function disputes()
    {
        return $this->panelView('admin.pages.disputes', [
            'pageTitle' => 'Disputes',
            'pageEyebrow' => 'Resolution center',
            'pageDescription' => 'Prioritize buyer and seller conflicts with evidence, SLA, and refund visibility.',
            'searchPlaceholder' => 'Search disputes, orders, users',
            'pageActions' => [
                ['label' => 'Assign cases', 'route' => 'admin.disputes', 'meta' => '19 open'],
                ['label' => 'Urgent queue', 'route' => 'admin.disputes', 'meta' => '6 cases'],
                ['label' => 'Refund review', 'route' => 'admin.disputes', 'meta' => 'Finance'],
            ],
            'stats' => [
                ['label' => 'Open cases', 'value' => '19', 'meta' => '6 urgent'],
                ['label' => 'Awaiting buyer', 'value' => '7', 'meta' => 'Reply requested'],
                ['label' => 'Awaiting seller', 'value' => '9', 'meta' => 'Evidence due'],
                ['label' => 'Resolved', 'value' => '31', 'meta' => 'This week'],
            ],
            'disputes' => [
                ['case' => 'DSP-103', 'order' => '#BD-1017', 'reason' => 'Delivery not as described', 'owner' => 'Trust team', 'priority' => 'Urgent'],
                ['case' => 'DSP-102', 'order' => '#BD-1014', 'reason' => 'Late delivery', 'owner' => 'Support', 'priority' => 'Normal'],
                ['case' => 'DSP-101', 'order' => '#BD-1009', 'reason' => 'Refund request', 'owner' => 'Finance', 'priority' => 'High'],
            ],
            'pagination' => $this->paginationMeta(19, 3),
        ]);
    }

    public function reports()
    {
        return $this->panelView('admin.pages.reports', [
            'pageTitle' => 'Reports',
            'pageEyebrow' => 'Marketplace analytics',
            'pageDescription' => 'Understand growth, conversion, repeat purchase behavior, and category performance.',
            'searchPlaceholder' => 'Search reports and segments',
            'stats' => [
                ['label' => 'Visitors today', 'value' => '1,284', 'meta' => '+18.2% vs yesterday'],
                ['label' => 'Profile completion', 'value' => '78%', 'meta' => '+6.4% this month'],
                ['label' => 'Profile verification', 'value' => '59%', 'meta' => '42 pending reviews'],
                ['label' => 'Published gigs', 'value' => '2,402', 'meta' => '+61 this period'],
            ],
            'marketplaceGrowth' => [
                'title' => 'Marketplace growth',
                'description' => 'Orders and revenue movement across the selected reporting range.',
                'controls' => [
                    ['label' => 'From', 'type' => 'date', 'name' => 'growth_from', 'value' => '2026-04-16'],
                    ['label' => 'To', 'type' => 'date', 'name' => 'growth_to', 'value' => '2026-05-16'],
                ],
                'labels' => ['Apr 16', 'Apr 20', 'Apr 24', 'Apr 28', 'May 02', 'May 06', 'May 10', 'May 14', 'May 16'],
                'datasets' => [
                    ['label' => 'Orders', 'values' => [42, 48, 62, 58, 76, 81, 70, 92, 104], 'color' => '#4f46e5', 'fill' => 'rgba(79, 70, 229, 0.12)'],
                    ['label' => 'Revenue index', 'values' => [35, 44, 52, 64, 68, 83, 78, 108, 118], 'color' => '#10b981', 'fill' => 'rgba(16, 185, 129, 0.10)'],
                ],
                'summary' => [
                    ['label' => 'Revenue growth', 'value' => '+14.6%'],
                    ['label' => 'Order growth', 'value' => '+11.2%'],
                    ['label' => 'Best day', 'value' => 'May 16'],
                ],
            ],
            'visitorAnalytics' => [
                'title' => 'Hourly visitor analytics',
                'description' => 'Unique visitors and total page views for the selected day.',
                'controls' => [
                    ['label' => 'Day', 'type' => 'date', 'name' => 'visitor_day', 'value' => '2026-05-02'],
                ],
                'labels' => ['00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'],
                'max' => 7,
                'datasets' => [
                    ['label' => 'Unique visitors', 'values' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0], 'color' => '#6366f1', 'fill' => 'rgba(99, 102, 241, 0.12)'],
                    ['label' => 'Total page views', 'values' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0], 'color' => '#10b981', 'fill' => 'rgba(16, 185, 129, 0.12)'],
                ],
                'summary' => [
                    ['label' => 'Peak hour', 'value' => '12:00'],
                    ['label' => 'Unique visitors', 'value' => '3'],
                    ['label' => 'Total page views', 'value' => '9'],
                ],
            ],
            'profileActivityGrowth' => [
                'title' => 'Profile readiness & gig publishing',
                'description' => 'Users register once, then switch buyer or seller profile activity from the dashboard.',
                'controls' => [
                    ['label' => 'From', 'type' => 'date', 'name' => 'profile_from', 'value' => '2026-04-16'],
                    ['label' => 'To', 'type' => 'date', 'name' => 'profile_to', 'value' => '2026-05-16'],
                ],
                'labels' => ['Apr 16', 'Apr 20', 'Apr 24', 'Apr 28', 'May 02', 'May 06', 'May 10', 'May 14', 'May 16'],
                'max' => 100,
                'datasets' => [
                    ['label' => 'Profile completion rate (%)', 'values' => [48, 52, 55, 59, 64, 67, 71, 74, 78], 'color' => '#2563eb', 'fill' => 'rgba(37, 99, 235, 0.10)'],
                    ['label' => 'Profile verification rate (%)', 'values' => [24, 28, 32, 36, 42, 45, 49, 54, 59], 'color' => '#10b981', 'fill' => 'rgba(16, 185, 129, 0.10)'],
                    ['label' => 'Gig publish growth (new gigs)', 'values' => [18, 21, 24, 32, 36, 43, 47, 55, 61], 'color' => '#f59e0b', 'fill' => 'rgba(245, 158, 11, 0.14)'],
                ],
                'summary' => [
                    ['label' => 'Completion rate', 'value' => '78%'],
                    ['label' => 'Verification rate', 'value' => '59%'],
                    ['label' => 'New published gigs', 'value' => '+61'],
                ],
            ],
            'segments' => [
                ['name' => 'Programming & Tech', 'sales' => '$18,400', 'growth' => '+14%'],
                ['name' => 'Digital Marketing', 'sales' => '$8,920', 'growth' => '+8%'],
                ['name' => 'Graphics & Design', 'sales' => '$7,140', 'growth' => '+5%'],
                ['name' => 'Video & Animation', 'sales' => '$4,860', 'growth' => '+11%'],
            ],
        ]);
    }

    public function roles()
    {
        $sensitivePermissions = $this->sensitivePermissions();
        $roles = $this->mappedRoles();

        return $this->panelView('admin.pages.roles', [
            'pageTitle' => 'Access Control',
            'pageEyebrow' => 'Roles & permissions',
            'pageDescription' => 'Manage staff access with Spatie roles and permissions while keeping seller levels separate from marketplace benefits.',
            'searchPlaceholder' => 'Search admin settings',
            'stats' => [
                ['label' => 'Total roles', 'value' => number_format($roles->count()), 'meta' => 'Admin access groups'],
                ['label' => 'Total permissions', 'value' => number_format(Permission::count()), 'meta' => 'Action-level controls'],
                ['label' => 'Admin users', 'value' => number_format(User::permission('admin.access')->count()), 'meta' => 'Can enter admin panel'],
                ['label' => 'Sensitive permissions', 'value' => number_format(Permission::whereIn('name', $sensitivePermissions)->count()), 'meta' => 'Extra review required'],
            ],
            'roles' => $roles,
            'sensitivePermissions' => $sensitivePermissions,
            'levelGuidance' => [
                ['label' => 'Roles', 'description' => 'Grant admin panel access and staff capabilities.'],
                ['label' => 'Permissions', 'description' => 'Control exact actions such as releasing payouts or resolving disputes.'],
                ['label' => 'Seller levels', 'description' => 'Stay separate and only affect marketplace benefits, badges, limits, and visibility.'],
            ],
        ]);
    }

    public function rolePermissions(Role $role)
    {
        $roles = $this->mappedRoles();
        $selectedRole = $roles->firstWhere('id', $role->id);
        $selectedPermissionNames = $role->permissions->pluck('name');
        $sensitivePermissions = $this->sensitivePermissions();

        return $this->panelView('admin.pages.role-permissions', [
            'pageTitle' => 'Assign Permissions',
            'pageEyebrow' => 'Access control',
            'pageDescription' => 'Choose exactly which admin actions this role can perform.',
            'searchPlaceholder' => 'Search permissions',
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'permissionGroups' => collect($this->accessControlModules())->map(function (array $permissions, string $module) use ($selectedPermissionNames, $sensitivePermissions) {
                return [
                    'module' => $module,
                    'label' => str($module)->replace('_', ' ')->title()->toString(),
                    'permissions' => collect($permissions)->map(fn (string $permission) => [
                        'name' => $permission,
                        'label' => str($permission)->after('.')->replace('_', ' ')->title()->toString(),
                        'assigned' => $selectedPermissionNames->contains($permission),
                        'sensitive' => in_array($permission, $sensitivePermissions, true),
                    ])->values()->all(),
                ];
            })->values()->all(),
            'sensitivePermissions' => $sensitivePermissions,
        ]);
    }

    public function roleUsers(Request $request)
    {
        $roles = $this->mappedRoles();
        $search = trim((string) $request->query('q', ''));
        $roleFilter = trim((string) $request->query('role', ''));
        $usersQuery = User::query()
            ->with('roles')
            ->orderBy('name')
            ->orderBy('email');

        if ($search !== '') {
            $usersQuery->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter !== '' && ! Role::where('name', $roleFilter)->exists()) {
            $roleFilter = '';
        }

        if ($roleFilter !== '') {
            $usersQuery->role($roleFilter);
        }

        $perPage = 8;
        $total = (clone $usersQuery)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $users = $usersQuery
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function (User $user) {
                $roleNames = $user->roles->pluck('name')->sort()->values();

                return [
                    'id' => $user->id,
                    'name' => $user->name ?: $user->email,
                    'email' => $user->email,
                    'roles' => $roleNames->all(),
                    'role_labels' => $roleNames->map(fn (string $role) => str($role)->replace('_', ' ')->title()->toString())->all(),
                    'can_admin' => $user->can('admin.access'),
                    'joined' => optional($user->created_at)->format('M j, Y'),
                ];
            });

        return $this->panelView('admin.pages.role-users', [
            'pageTitle' => 'Assign Roles to Users',
            'pageEyebrow' => 'Access control',
            'pageDescription' => 'Find existing users and assign one or more admin roles to grant panel access.',
            'searchPlaceholder' => 'Search users by name or email',
            'roles' => $roles,
            'assignableUsers' => $users,
            'searchQuery' => $search,
            'roleFilter' => $roleFilter,
            'pagination' => $pagination,
        ]);
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'include_admin_access' => ['nullable', 'boolean'],
        ]);

        $roleName = Str::of($data['label'])
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        if ($roleName === '') {
            return back()
                ->withErrors(['label' => 'Please use at least one letter or number for the role name.'])
                ->withInput();
        }

        if (Role::where('name', $roleName)->where('guard_name', 'web')->exists()) {
            return back()
                ->withErrors(['label' => 'A role with this name already exists.'])
                ->withInput();
        }

        $role = Role::create([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        if ($request->boolean('include_admin_access', true) && Permission::where('name', 'admin.access')->exists()) {
            $role->givePermissionTo('admin.access');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.permissions', $role)
            ->withNotify('success', 'Role created. You can now assign permissions and users to it.', 'Role created');
    }

    public function updateRolePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $permissionNames = collect($request->input('permissions', []))
            ->filter()
            ->unique()
            ->values();

        if ($role->name === 'super_admin') {
            $permissionNames = Permission::query()->pluck('name')->sort()->values();
        } elseif (Permission::where('name', 'admin.access')->exists() && ! $permissionNames->contains('admin.access')) {
            $permissionNames->prepend('admin.access');
        }

        $role->syncPermissions($permissionNames->all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.permissions', $role)
            ->withNotify('success', 'Permissions saved for '.str($role->name)->replace('_', ' ')->title().'.', 'Permissions saved');
    }

    public function updateUserRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $roleNames = collect($request->input('roles', []))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $user->syncRoles($roleNames);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $message = $user->can('admin.access')
            ? 'Access updated. '.$user->name.' can now use the admin panel according to their role permissions.'
            : 'Roles updated. This user still cannot access the admin panel until a role includes admin.access.';

        return back()->withNotify('success', $message, 'User roles updated');
    }

    public function settings()
    {
        $settingGroups = PlatformSettings::definitionsWithValues();

        return $this->panelView('admin.pages.settings', [
            'pageTitle' => 'Settings',
            'pageEyebrow' => 'Admin configuration',
            'pageDescription' => 'Configure marketplace safeguards, finance behavior, and operational defaults.',
            'searchPlaceholder' => 'Search settings',
            'stats' => [
                ['label' => 'Platform commission', 'value' => appSetting('platform_commission', 20).'%', 'meta' => 'Default seller fee'],
                ['label' => 'Referral commission', 'value' => appSetting('referral_commission', 5).'%', 'meta' => appSetting('referral_duration', 'First paid order')],
                ['label' => 'Payout hold', 'value' => appSetting('payout_hold_period', 7).' days', 'meta' => 'After order approval'],
                ['label' => 'Gig approval', 'value' => appSetting('manual_gig_approval', true) ? 'Manual' : 'Auto', 'meta' => 'New and edited gigs'],
            ],
            'settingGroups' => $settingGroups,
            'settingsSidebar' => [
                'systemInfo' => [
                    ['label' => 'Admin name', 'value' => config('admin.name')],
                    ['label' => 'Admin email', 'value' => config('admin.email')],
                    ['label' => 'Password env', 'value' => 'ADMIN_PASSWORD'],
                    ['label' => 'Settings cache', 'value' => config('platform_settings.cache_key')],
                ],
                'reviewQueue' => [
                    ['label' => 'Seller documents', 'value' => '18'],
                    ['label' => 'Gig edits', 'value' => '74'],
                    ['label' => 'Payout holds', 'value' => '8'],
                ],
                'checklist' => [
                    ['label' => 'Replace demo admin password', 'status' => 'Required'],
                    ['label' => 'Role middleware is active', 'status' => 'Done'],
                    ['label' => 'Connect settings to database', 'status' => 'Done'],
                ],
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $values = $request->input('settings', []);
        $errors = PlatformSettings::validateInput($values);

        if ($errors !== []) {
            return back()
                ->withErrors($errors)
                ->withInput();
        }

        PlatformSettings::setMany($values);

        return back()->withNotify('success', 'Platform settings were saved and the settings cache was refreshed.', 'Settings saved');
    }

    private function panelView(string $view, array $data = [])
    {
        if (! auth()->check()) {
            return redirect()->route('admin.login');
        }

        if (! auth()->user()->can('admin.access')) {
            abort(403);
        }

        $data['healthSummary'] = $data['healthSummary'] ?? [
            ['label' => 'Orders SLA', 'value' => '92%'],
            ['label' => 'Dispute load', 'value' => '19'],
            ['label' => 'Payout queue', 'value' => '$18.9k'],
        ];

        return view($view, $data);
    }

    private function mappedRoles()
    {
        $roleMeta = collect($this->accessControlRoleMeta())->keyBy('name');

        return Role::query()
            ->with('permissions')
            ->get()
            ->sortBy(fn (Role $role) => $roleMeta->get($role->name)['order'] ?? 99)
            ->values()
            ->map(function (Role $role) use ($roleMeta) {
                $meta = $roleMeta->get($role->name, []);

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $meta['label'] ?? str($role->name)->replace('_', ' ')->title()->toString(),
                    'description' => $meta['description'] ?? 'Custom access role for the admin panel.',
                    'tone' => $meta['tone'] ?? 'neutral',
                    'users_count' => User::role($role->name)->count(),
                    'permission_count' => $role->permissions->count(),
                    'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
                ];
            });
    }

    private function accessControlRoleMeta(): array
    {
        return [
            ['name' => 'super_admin', 'label' => 'Super Admin', 'description' => 'Full platform access, including roles, permissions, finance, settings, and all admin operations.', 'tone' => 'critical', 'order' => 1],
            ['name' => 'admin', 'label' => 'Admin', 'description' => 'Broad operations access without changing role permissions or sensitive platform security.', 'tone' => 'strong', 'order' => 2],
            ['name' => 'finance_manager', 'label' => 'Finance Manager', 'description' => 'Manages payouts, payment reports, refunds, and finance-related reporting.', 'tone' => 'finance', 'order' => 3],
            ['name' => 'support_agent', 'label' => 'Support Agent', 'description' => 'Handles users, orders, disputes, and buyer or seller support workflows.', 'tone' => 'support', 'order' => 4],
            ['name' => 'catalog_moderator', 'label' => 'Catalog Moderator', 'description' => 'Reviews gigs, categories, package quality, and public marketplace listings.', 'tone' => 'catalog', 'order' => 5],
            ['name' => 'trust_safety', 'label' => 'Trust & Safety', 'description' => 'Reviews flagged accounts, reports, external contact risks, and dispute safety signals.', 'tone' => 'trust', 'order' => 6],
        ];
    }

    private function accessControlModules(): array
    {
        return [
            'admin' => ['admin.access'],
            'users' => ['users.view', 'users.verify', 'users.suspend'],
            'gigs' => ['gigs.view', 'gigs.review', 'gigs.publish'],
            'orders' => ['orders.view', 'orders.manage'],
            'payments' => ['payments.view', 'payments.release'],
            'disputes' => ['disputes.view', 'disputes.resolve'],
            'reports' => ['reports.view'],
            'settings' => ['settings.view', 'settings.update'],
            'roles' => ['roles.manage'],
        ];
    }

    private function sensitivePermissions(): array
    {
        return [
            'payments.release',
            'settings.update',
            'roles.manage',
            'users.suspend',
        ];
    }

    private function defaultActions(): array
    {
        return [
            ['label' => 'Review gigs', 'route' => 'admin.gigs', 'meta' => '74 pending'],
            ['label' => 'Open disputes', 'route' => 'admin.disputes', 'meta' => '19 cases'],
            ['label' => 'Release payouts', 'route' => 'admin.payments', 'meta' => '$86.4k ready'],
        ];
    }

    private function paginationMeta(int $total, int $perPage): array
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

    private function paginationWindow(int $currentPage, int $lastPage): array
    {
        $start = max(1, $currentPage - 2);
        $end = min($lastPage, $start + 4);
        $start = max(1, $end - 4);

        return range($start, $end);
    }

    private function ordersData(): array
    {
        return [
            ['id' => '#BD-1024', 'buyer' => 'Jahid Hasan', 'seller' => 'Wiznic Solution', 'service' => 'AI website development', 'status' => 'In progress', 'amount' => '$150'],
            ['id' => '#BD-1023', 'buyer' => 'Nadia Islam', 'seller' => 'Ahmad', 'service' => 'Full stack web app', 'status' => 'Delivered', 'amount' => '$420'],
            ['id' => '#BD-1022', 'buyer' => 'Rafiq Ahmed', 'seller' => 'Deal With Code', 'service' => 'Codecanyon install', 'status' => 'Revision', 'amount' => '$35'],
            ['id' => '#BD-1021', 'buyer' => 'Sarah Khan', 'seller' => 'Tecbeck', 'service' => 'Mobile app design', 'status' => 'Pending', 'amount' => '$115'],
        ];
    }
}
