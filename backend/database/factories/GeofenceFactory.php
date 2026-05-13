<?php

namespace Database\Factories;

use App\Models\Geofence;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeofenceFactory extends Factory
{
    protected $model = Geofence::class;
    
    public function definition(): array
    {
        return [
            'owner_id' => null,
            'name' => $this->faker->word(),
            'coordinates' => json_encode([['lat' => 25.0, 'lng' => 55.0]]),
            'is_active' => true,
        ];
    }
}
