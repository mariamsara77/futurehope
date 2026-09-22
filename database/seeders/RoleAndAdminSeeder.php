<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // স্প্যাটির ক্যাশ ক্লিয়ার করা
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ১. প্রয়োজনীয় পারমিশনগুলো তৈরি করা (রাউটে ব্যবহৃত পারমিশন অনুযায়ী)
        $permissions = [
            'activity-log-view',
            'system-manager',
            'google indexing request',
            'biodata-manage',
            'user-manage',
            'designation-manage',
            'role-manage',
            'permission-manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ২. Roles তৈরি করা
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);

        // অ্যাডমিন রোলকে সব পারমিশন দিয়ে দেওয়া
        $adminRole->givePermissionTo(Permission::all());

        // ৩. Admin User তৈরি করা
        $admin = User::updateOrCreate(
            ['email' => 'shaangi.com@gmail.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'), 
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // ৪. Admin Role অ্যাসাইন করা
        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }

        // ৫. অ্যাডমিনের জন্য Profile তৈরি করা
        $admin->profile()->firstOrCreate([
            'user_id' => $admin->id
        ]);

        $this->command->info('Roles, Permissions, and Admin user created successfully!');
    }
}