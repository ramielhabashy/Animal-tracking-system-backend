<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Admin' => [
                'manage_users',
                'manage_animals',
                'manage_devices',
                'manage_geofences',
                'manage_tasks',
                'view_reports',
                'export_data',
                'manage_settings',
                'manage_subscriptions',
            ],
            'Owner' => [
                'manage_animals',
                'manage_devices',
                'manage_geofences',
                'manage_tasks',
                'manage_shepherds',
                'view_reports',
                'manage_subscriptions',
            ],
            'Manager' => [
                'manage_animals',
                'manage_devices',
                'manage_geofences',
                'manage_tasks',
                'view_reports',
            ],
            'Shepherd' => [
                'view_animals',
                'add_animals',
                'update_animals',
                'manage_tasks',
                'log_task_completion',
            ],
            'Doctor' => [
                'view_animals',
                'add_medical_records',
                'view_medical_records',
                'manage_vaccinations',
                'view_vaccinations',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            foreach ($permissions as $permission) {
                $perm = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
                $role->givePermissionTo($perm);
            }
        }
    }
}