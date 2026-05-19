<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PlatformSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
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

        $rolePermissions = [
            'super_admin' => $permissions->all(),
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

        foreach ($rolePermissions as $roleName => $rolePermissionNames) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ])->syncPermissions($rolePermissionNames);
        }

        User::updateOrCreate(
            ['email' => config('admin.email')],
            [
                'name' => config('admin.name'),
                'password' => Hash::make(config('admin.password')),
                'email_verified_at' => now(),
            ]
        )->syncRoles(['super_admin']);

        User::updateOrCreate(
            ['email' => 'finance@bdgigs.test'],
            [
                'name' => 'Finance Manager',
                'password' => Hash::make(config('admin.password')),
                'email_verified_at' => now(),
            ]
        )->syncRoles(['finance_manager']);

        User::updateOrCreate(
            ['email' => 'support@bdgigs.test'],
            [
                'name' => 'Support Agent',
                'password' => Hash::make(config('admin.password')),
                'email_verified_at' => now(),
            ]
        )->syncRoles(['support_agent']);

        User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('password'),
        ]);

        PlatformSettings::syncDefinitions();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
