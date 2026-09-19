<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'run-scheduler',
            'view-tenants',
            'manage-tenants',
            'manage-subscriptions',
        ];

        foreach ($permissions as $permissionName) {
            Permission::updateOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['name' => $permissionName, 'guard_name' => 'web'],
            );
        }

        $superAdminRole = Role::updateOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            ['name' => 'super-admin', 'guard_name' => 'web'],
        );

        $superAdminRole->syncPermissions($permissions);

        Role::updateOrCreate(
            ['name' => 'tenant-admin', 'guard_name' => 'web'],
            ['name' => 'tenant-admin', 'guard_name' => 'web'],
        );

        Role::updateOrCreate(
            ['name' => 'tenant-user', 'guard_name' => 'web'],
            ['name' => 'tenant-user', 'guard_name' => 'web'],
        );
    }
}
