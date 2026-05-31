<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'user_view', 'user_create', 'user_edit', 'user_delete', 'user_assign_role',
            'animal_view', 'animal_create', 'animal_edit', 'animal_delete', 'animal_view_health',
            'device_view', 'device_create', 'device_edit', 'device_delete',
            'geofence_view', 'geofence_create', 'geofence_edit', 'geofence_delete',
            'geofence_alert_view', 'geofence_alert_configure',
            'task_view', 'task_create', 'task_complete', 'task_delete',
            'report_view', 'report_export',
            'settings_view', 'settings_edit',
            'medical_record_view', 'medical_record_create', 'medical_record_edit',
            'vaccination_view', 'vaccination_create', 'vaccination_edit',
            'auction_view', 'auction_create', 'auction_edit', 'auction_bid',
            'support_ticket_view', 'support_ticket_respond', 'support_ticket_resolve',
            'billing_invoice_view', 'billing_payment_view', 'billing_refund_process',
            'cs_user_view', 'cs_user_message', 'cs_subscription_modify',
            'platform_report_view', 'platform_audit_view', 'platform_announcement',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // --- Staff roles (type = admin) ---

        $adminPerms = Permission::whereIn('name', $permissions)->get();
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions($adminPerms);

        $supportPerms = Permission::whereIn('name', [
            'support_ticket_view', 'support_ticket_respond', 'support_ticket_resolve',
            'user_view',
            'animal_view', 'animal_view_health',
            'device_view',
            'geofence_view',
            'task_view',
            'report_view',
        ])->get();
        $role = Role::firstOrCreate(['name' => 'Support', 'guard_name' => 'web']);
        $role->syncPermissions($supportPerms);

        $accountantPerms = Permission::whereIn('name', [
            'billing_invoice_view', 'billing_payment_view', 'billing_refund_process',
            'report_view', 'report_export',
            'user_view',
            'platform_report_view',
        ])->get();
        $role = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);
        $role->syncPermissions($accountantPerms);

        $csPerms = Permission::whereIn('name', [
            'cs_user_view', 'cs_user_message', 'cs_subscription_modify',
            'user_view', 'user_create', 'user_edit',
            'animal_view',
            'device_view',
            'report_view',
        ])->get();
        $role = Role::firstOrCreate(['name' => 'Customer Service', 'guard_name' => 'web']);
        $role->syncPermissions($csPerms);

        // --- Farm roles (type = user) ---

        $ownerPerms = Permission::whereIn('name', [
            'animal_view', 'animal_create', 'animal_edit', 'animal_delete',
            'device_view', 'device_create', 'device_edit', 'device_delete',
            'geofence_view', 'geofence_create', 'geofence_edit', 'geofence_delete',
            'task_view', 'task_create', 'task_complete', 'task_delete',
            'report_view', 'report_export',
            'medical_record_view', 'medical_record_create', 'medical_record_edit',
            'vaccination_view', 'vaccination_create', 'vaccination_edit',
        ])->get();
        $owner = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);
        $owner->syncPermissions($ownerPerms);

        $managerPerms = Permission::whereIn('name', [
            'animal_view', 'animal_edit',
            'device_view',
            'geofence_view', 'geofence_create', 'geofence_edit',
            'task_view', 'task_create', 'task_complete',
            'report_view',
            'medical_record_view',
            'vaccination_view',
        ])->get();
        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manager->syncPermissions($managerPerms);

        $shepherdPerms = Permission::whereIn('name', [
            'animal_view', 'animal_create',
            'device_view',
            'geofence_view',
            'task_view', 'task_create', 'task_complete',
        ])->get();
        $shepherd = Role::firstOrCreate(['name' => 'Shepherd', 'guard_name' => 'web']);
        $shepherd->syncPermissions($shepherdPerms);

        $doctorPerms = Permission::whereIn('name', [
            'user_view',
            'animal_view', 'animal_create', 'animal_edit', 'animal_delete',
            'medical_record_view', 'medical_record_create', 'medical_record_edit',
            'vaccination_view', 'vaccination_create', 'vaccination_edit',
            'task_view', 'task_complete',
        ])->get();
        $doctor = Role::firstOrCreate(['name' => 'Doctor', 'guard_name' => 'web']);
        $doctor->syncPermissions($doctorPerms);

        // Set types on all roles
        $admin->update(['type' => 'admin']);
        Role::whereIn('name', ['Support', 'Accountant', 'Customer Service'])->update(['type' => 'admin']);
        Role::whereIn('name', ['Owner', 'Manager', 'Shepherd', 'Doctor'])->update(['type' => 'user']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        echo "RoleSeeder completed. Created 9 roles with permissions.\n";
    }
}
