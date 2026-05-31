<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary roles for testing
        $this->createRoles();
        
        // Create subscription tiers
        $this->createSubscriptionTiers();
    }
    
    protected function createRoles(): void
    {
        // Create permissions first
        $permissions = [
            'animal_create', 'animal_edit', 'animal_delete', 'animal_view',
            'device_create', 'device_edit', 'device_delete', 'device_view',
            'geofence_create', 'geofence_edit', 'geofence_delete', 'geofence_view',
            'task_create', 'task_edit', 'task_view', 'task_complete', 'task_delete',
            'medical_record_create', 'medical_record_edit', 'medical_record_view',
            'auction_create', 'auction_edit', 'auction_view',
        ];
        
        foreach ($permissions as $permission) {
            if (!\Spatie\Permission\Models\Permission::where('name', $permission)->exists()) {
                \Spatie\Permission\Models\Permission::create(['name' => $permission, 'guard_name' => 'web']);
            }
        }
        
        if (!\Spatie\Permission\Models\Role::where('name', 'Owner')->exists()) {
            $ownerRole = \Spatie\Permission\Models\Role::create(['name' => 'Owner', 'guard_name' => 'web']);
            $ownerRole->givePermissionTo([
                'animal_create', 'animal_edit', 'animal_delete', 'animal_view',
                'device_create', 'device_edit', 'device_delete', 'device_view',
                'geofence_create', 'geofence_edit', 'geofence_delete', 'geofence_view',
                'task_create', 'task_edit', 'task_view', 'task_complete', 'task_delete',
            ]);
        }
        if (!\Spatie\Permission\Models\Role::where('name', 'Admin')->exists()) {
            $adminRole = \Spatie\Permission\Models\Role::create(['name' => 'Admin', 'guard_name' => 'web']);
            $adminRole->givePermissionTo(\Spatie\Permission\Models\Permission::all());
        }
        if (!\Spatie\Permission\Models\Role::where('name', 'Shepherd')->exists()) {
            $shepherdRole = \Spatie\Permission\Models\Role::create(['name' => 'Shepherd', 'guard_name' => 'web']);
            $shepherdRole->givePermissionTo([
                'task_view', 'task_create', 'task_complete',
            ]);
        }
        if (!\Spatie\Permission\Models\Role::where('name', 'Doctor')->exists()) {
            $doctorRole = \Spatie\Permission\Models\Role::create(['name' => 'Doctor', 'guard_name' => 'web']);
            $doctorRole->givePermissionTo([
                'task_view', 'task_complete',
            ]);
        }
        if (!\Spatie\Permission\Models\Role::where('name', 'Manager')->exists()) {
            $managerRole = \Spatie\Permission\Models\Role::create(['name' => 'Manager', 'guard_name' => 'web']);
            $managerRole->givePermissionTo([
                'task_create', 'task_edit', 'task_view', 'task_complete',
            ]);
        }
    }
    
    protected function createSubscriptionTiers(): void
    {
        if (!\App\Models\SubscriptionTier::where('slug', 'free')->exists()) {
            \App\Models\SubscriptionTier::create([
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Free tier for testing',
                'price_monthly' => 0.00,
                'price_yearly' => 0.00,
                'trial_days' => 0,
                'max_animals' => 5,
                'max_devices' => 2,
                'max_users' => 1,
                'has_geofencing' => true,
                'has_auctions' => true,
                'has_advanced_reports' => true,
                'has_api_access' => true,
                'has_medical_records' => true,
                'has_tasks' => true,
                'has_ai_assistant' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }
        if (!\App\Models\SubscriptionTier::where('slug', 'basic')->exists()) {
            \App\Models\SubscriptionTier::create([
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Basic tier for testing',
                'price_monthly' => 9.99,
                'price_yearly' => 99.99,
                'trial_days' => 7,
                'max_animals' => 50,
                'max_devices' => 10,
                'max_users' => 3,
                'has_geofencing' => true,
                'has_auctions' => true,
                'has_advanced_reports' => true,
                'has_api_access' => true,
                'has_medical_records' => true,
                'has_tasks' => true,
                'has_ai_assistant' => true,
                'is_active' => true,
                'sort_order' => 2,
            ]);
        }
    }

    protected function createUser(array $attributes = []): \App\Models\User
    {
        $role = $attributes['role'] ?? 'Owner';
        unset($attributes['role']);

        $user = \App\Models\User::factory()->create(array_merge([
            'password' => Hash::make('password'),
            'is_active' => true,
        ], $attributes));
        
        if (!isset($attributes['subscription_tier_id'])) {
            $freeTier = \App\Models\SubscriptionTier::where('slug', 'free')->first();
            if ($freeTier) {
                $user->update(['subscription_tier_id' => $freeTier->id]);
            }
        }
        
        // Assign role
        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }

    protected function authenticateUser(\App\Models\User $user = null): \App\Models\User
    {
        $user = $user ?: $this->createUser();
        $token = $user->createToken('test-token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer ' . $token);
        return $user;
    }
}
