<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = ['devices.view','devices.create','devices.update','devices.delete','devices.lock','devices.unlock','devices.location','devices.camera','devices.command','sites.manage','teams.manage','users.manage','telegram.manage','audit.view','settings.manage'];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'SUPER_ADMIN', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => 'ADMIN', 'guard_name' => 'web']);
        $admin->syncPermissions(array_diff($permissions, ['settings.manage']));

        $operator = Role::firstOrCreate(['name' => 'OPERATOR', 'guard_name' => 'web']);
        $operator->syncPermissions(['devices.view','devices.lock','devices.unlock','devices.location','devices.camera','devices.command']);

        $viewer = Role::firstOrCreate(['name' => 'VIEWER', 'guard_name' => 'web']);
        $viewer->syncPermissions(['devices.view']);

        $user = User::firstOrCreate(
            ['email' => 'admin@lacaksmbbot.com'],
            ['name' => 'Super Admin', 'password' => bcrypt(env('SMB_ADMIN_PASSWORD', 'ChangeMe!123'))]
        );
        $user->assignRole('SUPER_ADMIN');

        $site = Site::firstOrCreate(['code' => 'HQ'], ['name' => 'Head Office']);
        Team::firstOrCreate(['code' => 'OPS'], ['site_id' => $site->id, 'name' => 'Operations']);
    }
}
