<?php

namespace Database\Factories;

use App\Models\SubscriptionTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionTierFactory extends Factory
{
    protected $model = SubscriptionTier::class;
    
    public function definition(): array
    {
        return [
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Free tier',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'trial_days' => 0,
            'max_animals' => 10,
            'max_devices' => 5,
            'max_users' => 1,
            'has_geofencing' => false,
            'has_auctions' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
