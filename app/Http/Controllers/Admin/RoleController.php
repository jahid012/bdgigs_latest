<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends AdminController
{
    public function index()
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

    public function store(Request $request)
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

    public function permissions(Role $role)
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

    public function updatePermissions(Request $request, Role $role)
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

    public function users(Request $request)
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
}
