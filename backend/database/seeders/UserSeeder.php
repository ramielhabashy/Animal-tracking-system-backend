<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SubscriptionTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
                'role' => 'Manager',
                'is_active' => true,
                'phone' => '+201066746002',
                'subscription_tier_id' => $freeTier?->id,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user);
        }
    }
}
