<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SubconRbacSeeder extends Seeder
{
    protected string $guard = 'web';

    protected array $perms = [
        'portal.subcon',
        'access.subcon',
        'action.subcon.manage',
    ];

    /** Owner / production management by default. */
    protected array $roleNames = [
        'super_admin', 'Super Admin', 'superadmin', 'admin',
        'general_manager', 'production_manager', 'warehouse',
    ];

    public function run(): void
    {
        foreach ($this->perms as $perm) {
            Permission::findOrCreate($perm, $this->guard);
        }

        foreach ($this->roleNames as $name) {
            $role = Role::where('name', $name)->where('guard_name', $this->guard)->first();
            if ($role) {
                $role->givePermissionTo($this->perms);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
