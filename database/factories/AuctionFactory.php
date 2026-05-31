<?php

namespace Database\Factories;

use App\Models\Auction;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuctionFactory extends Factory
{
    protected $model = Auction::class;
    
    public function definition(): array
    {
        return [
            'animal_id' => null,
            'owner_id' => null,
            'title' => $this->faker->sentence(3),
            'starting_price' => $this->faker->randomFloat(2, 10, 1000),
            'current_price' => $this->faker->randomFloat(2, 10, 1000),
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
        ];
    }
}
