<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Map each native `users.role` enum to a Spatie role so Filament
     * Shield can authorize against the generated permissions.
     */
    public function run(): void
    {
        $dailyReportPermissions = [
            'view_any_daily::report',
            'view_daily::report',
            'create_daily::report',
            'update_daily::report',
            'delete_daily::report',
            'delete_any_daily::report',
        ];

        $engineer = Role::findOrCreate('site_engineer', 'web');
        $engineer->syncPermissions(
            Permission::whereIn('name', $dailyReportPermissions)->pluck('id')->all()
        );

        $client = Role::findOrCreate('client', 'web');
        $client->syncPermissions(
            Permission::whereIn('name', array_slice($dailyReportPermissions, 0, 2))->pluck('id')->all()
        );

        User::query()->each(function (User $user): void {
            $roleName = match ($user->role) {
                UserRole::Admin => 'super_admin',
                UserRole::SiteEngineer => 'site_engineer',
                UserRole::Client => 'client',
            };

            $user->assignRole($roleName);
        });
    }
}
