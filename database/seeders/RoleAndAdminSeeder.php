<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'activity-log-view',
            'system-manager',
            'google indexing request',
            'biodata-manage',
            'user-manage',
            'designation-manage',
            'role-manage',
            'permission-manage',
            'work-manage',
            'work-vote',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);

        $adminRole->syncPermissions(Permission::all());
        $memberRole->givePermissionTo('work-vote');

        $admin = User::updateOrCreate(
            ['email' => 'shaangi.com@gmail.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }

        $admin->profile()->updateOrCreate(
            ['user_id' => $admin->id],
            ['status' => 'active', 'priority' => 1]
        );

        $this->command->info('Foundation roles, permissions, and admin are ready.');
    }
}
