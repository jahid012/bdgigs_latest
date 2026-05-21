<?php

namespace Database\Seeders;

use App\Models\User;
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
            'users' => ['users.view', 'users.verify', 'users.suspend'],
            'gigs' => ['gigs.view', 'gigs.review', 'gigs.publish'],
            'orders' => ['orders.view', 'orders.manage'],
            'payments' => ['payments.view', 'payments.release'],
            'disputes' => ['disputes.view', 'disputes.resolve'],
            'reports' => ['reports.view'],
            'settings' => ['settings.view', 'settings.update'],
            'roles' => ['roles.manage'],
        ];
        $permissions = collect($permissionGroups)->flatten()->values();

        $permissions->each(fn (string $permission) => Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        foreach ($this->rolePermissions($permissions->all()) as $roleName => $permissionNames) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
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
                'orders.view', 'orders.manage',
                'payments.view',
                'disputes.view', 'disputes.resolve',
                'reports.view',
                'settings.view',
            ],
            'finance_manager' => [
                'admin.access',
                'payments.view', 'payments.release',
                'reports.view',
            ],
            'support_agent' => [
                'admin.access',
                'users.view', 'users.verify',
                'orders.view', 'orders.manage',
                'disputes.view', 'disputes.resolve',
            ],
            'catalog_moderator' => [
                'admin.access',
                'gigs.view', 'gigs.review', 'gigs.publish',
                'reports.view',
            ],
            'trust_safety' => [
                'admin.access',
                'users.view', 'users.suspend',
                'gigs.view', 'gigs.review',
                'disputes.view', 'disputes.resolve',
                'reports.view',
            ],
        ];
    }

    private function staffUser(string $email, string $name, string $role): void
    {
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(config('admin.password')),
                'email_verified_at' => now(),
                'profile_type' => 'staff',
                'country' => 'Bangladesh',
                'verification_status' => 'verified',
            ],
        )->syncRoles([$role]);
    }
}
