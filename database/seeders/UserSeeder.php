<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SubscriptionTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $freeTier = SubscriptionTier::where('slug', 'free')->first();
        $starterTier = SubscriptionTier::where('slug', 'starter')->first();
        
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@oasis.com',
                'password' => Hash::make('password'),
                'role' => 'Admin',
                'is_active' => true,
                'phone' => '+201066746002',
                'subscription_tier_id' => $starterTier?->id,
            ],
            [
                'name' => 'Ezzeldeen Tantawy',
                'email' => 'e.tantawy@proton.me',
                'password' => Hash::make('password'),
                'role' => 'Admin',
                'is_active' => true,
                'phone' => null,
                'subscription_tier_id' => $starterTier?->id,
            ],
            [
                'name' => 'Khalid Al-Rashid',
                'email' => 'khalid@oasis.com',
                'password' => Hash::make('password'),
                'role' => 'Owner',
                'is_active' => true,
                'phone' => '+201066746002',
                'subscription_tier_id' => $starterTier?->id,
            ],
            [
                'name' => 'Ahmad Hassan',
                'email' => 'ahmad@oasis.com',
                'password' => Hash::make('password'),
                'role' => 'Owner',
                'is_active' => true,
                'phone' => '+201066746002',
                'subscription_tier_id' => $starterTier?->id,
            ],
            [
                'name' => 'Saeed Al-Maktoum',
                'email' => 'saeed@oasis.com',
                'password' => Hash::make('password'),
                'role' => 'Owner',
                'is_active' => true,
                'phone' => '+201066746002',
                'subscription_tier_id' => $starterTier?->id,
            ],
            [
                'name' => 'Fatima Al-Said',
                'email' => 'fatima@oasis.com',
                'password' => Hash::make('password'),
                'role' => 'Doctor',
                'is_active' => true,
                'phone' => '+201066746002',
                'subscription_tier_id' => $freeTier?->id,
            ],
            [
                'name' => 'Zeno Doctor',
                'email' => 'zeno@oasis.com',
                'password' => Hash::make('12345678'),
                'role' => 'Doctor',
                'is_active' => true,
                'phone' => '+201066746009',
                'subscription_tier_id' => $freeTier?->id,
            ],
            [
                'name' => 'Zeno Doctor',
                'email' => 'zeno@oasis.com',
                'password' => Hash::make('12345678'),
                'role' => 'Doctor',
                'is_active' => true,
                'phone' => '+201066746009',
                'subscription_tier_id' => $freeTier?->id,
            ],
            [
                'name' => 'Omar Shepherd',
                'email' => 'omar@oasis.com',
                'password' => Hash::make('password'),
                'role' => 'Shepherd',
                'is_active' => true,
                'phone' => '+201066746003',
                'managed_by' => null,
            ],
            [
                'name' => 'Ali Shepherd',
                'email' => 'ali@oasis.com',
                'password' => Hash::make('password'),
                'role' => 'Shepherd',
                'is_active' => true,
                'phone' => '+201066746004',
                'managed_by' => null,
            ],
            [
                'name' => 'Zeko Shepherd',
                'email' => 'zeko@oasis.com',
                'password' => Hash::make('password'),
                'role' => 'Shepherd',
                'is_active' => true,
                'phone' => '+201066746005',
                'managed_by' => null,
            ],
            [
                'name' => 'Mohsen Al-Owner',
                'email' => 'mohsen@oasis.com',
                'password' => Hash::make('password'),
                'role' => 'Owner',
                'is_active' => true,
                'phone' => '+201066746006',
                'subscription_tier_id' => $starterTier?->id,
            ],
            [
                'name' => 'Zekas Shepherd',
                'email' => 'zekas@oasis.com',
                'password' => Hash::make('password'),
                'role' => 'Shepherd',
                'is_active' => true,
                'phone' => '+201066746007',
                'managed_by' => null,
            ],
            [
                'name' => 'Fokas Shepherd',
                'email' => 'fokas@oasis.com',
                'password' => Hash::make('password'),
                'role' => 'Shepherd',
                'is_active' => true,
                'phone' => '+201066746008',
                'managed_by' => null,
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);
            
            $user = User::updateOrCreate(['email' => $userData['email']], $userData);
            $spatieRole = Role::where('name', $role)->first();
            if ($spatieRole) {
                $user->syncRoles([$spatieRole]);
            }
        }
        
        // Now assign shepherds/vets to the first Owner
        $owner = User::role('Owner')->first();
        if ($owner) {
            User::whereIn('email', [
                'omar@oasis.com', 'ali@oasis.com', 'zeko@oasis.com',
                'zekas@oasis.com', 'fokas@oasis.com', 'fatima@oasis.com',
                'zeno@oasis.com',
            ])->update(['managed_by' => $owner->id]);
        }
    }
}
