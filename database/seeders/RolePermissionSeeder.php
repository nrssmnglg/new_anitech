<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'manage farmers',
            'manage membership applications',
            'manage renewals',
            'manage mortuary claims',
            'manage queries',
            'view notifications',
            'manage locations',
            'manage advisories',
            'manage fee schedules',
            'view reports',
            'manage users',
            'view audit logs',
        ])->map(fn (string $permission) => Permission::findOrCreate($permission, 'web'));

        $admin = Role::findOrCreate(User::ROLE_ADMIN, 'web');
        $staff = Role::findOrCreate(User::ROLE_STAFF, 'web');
        $farmer = Role::findOrCreate(User::ROLE_FARMER, 'web');

        $admin->syncPermissions($permissions);
        $staff->syncPermissions([
            'manage farmers',
            'manage membership applications',
            'manage renewals',
            'manage mortuary claims',
            'manage queries',
            'manage advisories',
            'view notifications',
        ]);
        $farmer->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
