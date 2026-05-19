<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // First: Create tables that others depend on
            \Database\Seeders\LanguageSeeder::class,
            \Database\Seeders\SubscriptionTierSeeder::class,
            
            // Then: Roles (depends on nothing)
            \Database\Seeders\RoleSeeder::class,
            
            // Users (depends on roles)
            \Database\Seeders\UserSeeder::class,
            
            // User roles (depends on users and roles)
            \Database\Seeders\UserRoleSeeder::class,
            
            // Subscriptions (depends on users and tiers)
            \Database\Seeders\UserSubscriptionSeeder::class,
            
            // Other data
            \Database\Seeders\DeviceSeeder::class,
            \Database\Seeders\AnimalSeeder::class,
            \Database\Seeders\AnimalGroupSeeder::class,
            \Database\Seeders\GeofenceSeeder::class,
            \Database\Seeders\LocationHistorySeeder::class,
            \Database\Seeders\AuctionSeeder::class,
            \Database\Seeders\BidSeeder::class,
            \Database\Seeders\GeofenceAlertSeeder::class,
            \Database\Seeders\TaskSeeder::class,
            \Database\Seeders\SubscriptionOrderSeeder::class,
            \Database\Seeders\BannerSeeder::class,

            // Always last: clears old content data and creates fresh demo data
            \Database\Seeders\DemoDataSeeder::class,
        ]);
    }
}
