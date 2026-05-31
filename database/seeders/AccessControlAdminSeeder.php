<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Support\PlatformSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlAdminSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionGroups = [
            'admin' => ['admin.access'],
            'users' => ['users.view', 'users.verify', 'users.suspend', 'users.impersonate'],
            'gigs' => ['gigs.view', 'gigs.review', 'gigs.publish'],
            'categories' => ['categories.manage'],
            'content' => ['content.manage'],
            'orders' => ['orders.view', 'orders.manage'],
            'payments' => ['payments.view', 'payments.release'],
            'manual_payments' => ['manual-payments.view', 'manual-payments.approve'],
            'withdrawals' => ['withdrawals.view', 'withdrawals.review', 'withdrawals.pay'],
            'disputes' => ['disputes.view', 'disputes.resolve'],
            'reports' => ['reports.view', 'reports.manage'],
            'security' => ['security.view', 'security.review'],
            'emails' => ['emails.manage'],
            'settings' => ['settings.view', 'settings.update'],
            'roles' => ['roles.manage'],
        ];
        $permissions = collect($permissionGroups)->flatten()->values();

        $permissions->each(fn (string $permission) => Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ]));

        foreach ($this->rolePermissions($permissions->all()) as $roleName => $permissionNames) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'admin',
            ])->syncPermissions($permissionNames);
        }

        $this->staffUser(
            config('admin.email'),
            config('admin.name'),
            'super_admin',
        );
        $this->staffUser('finance@bdgigs.test', 'Finance Manager', 'finance_manager');
        $this->staffUser('support@bdgigs.test', 'Support Agent', 'support_agent');

        PlatformSettings::syncDefinitions();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function rolePermissions(array $permissions): array
    {
        return [
            'super_admin' => $permissions,
            'admin' => [
                'admin.access',
                'users.view', 'users.verify',
                'gigs.view', 'gigs.review', 'gigs.publish',
                'categories.manage', 'content.manage',
                'orders.view', 'orders.manage',
                'payments.view',
                'manual-payments.view',
                'withdrawals.view', 'withdrawals.review',
                'disputes.view', 'disputes.resolve',
                'reports.view', 'reports.manage',
                'security.view', 'security.review',
                'emails.manage',
                'settings.view',
            ],
            'finance_manager' => [
                'admin.access',
                'payments.view', 'payments.release',
                'manual-payments.view', 'manual-payments.approve',
                'withdrawals.view', 'withdrawals.review', 'withdrawals.pay',
                'reports.view',
                'emails.manage',
            ],
            'support_agent' => [
                'admin.access',
                'users.view', 'users.verify',
                'orders.view', 'orders.manage',
                'disputes.view', 'disputes.resolve',
                'emails.manage',
            ],
            'catalog_moderator' => [
                'admin.access',
                'gigs.view', 'gigs.review', 'gigs.publish',
                'categories.manage', 'content.manage',
                'reports.view', 'reports.manage',
                'security.view', 'security.review',
            ],
            'trust_safety' => [
                'admin.access',
                'users.view', 'users.suspend',
                'gigs.view', 'gigs.review',
                'disputes.view', 'disputes.resolve',
                'reports.view', 'reports.manage',
                'security.view', 'security.review',
            ],
        ];
    }

    private function staffUser(string $email, string $name, string $role): void
    {
        Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(config('admin.password')),
                'email_verified_at' => now(),
                'department' => str($role)->before('_')->replace('_', ' ')->title()->toString(),
                'status' => 'active',
            ],
        )->syncRoles([$role]);
    }
}
