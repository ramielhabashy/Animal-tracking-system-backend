<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    protected $model = Device::class;
    
    public function definition(): array
    {
        return [
            'device_id' => 'IOT-' . rand(100, 999) . '-' . strtoupper(substr(md5(time()), 0, 1)),
            'name' => 'Tracker ' . $this->faker->word(),
            'type' => 'gps_tracker',
            'status' => 'online',
            'battery_level' => $this->faker->numberBetween(0, 100),
            'owner_id' => null,
        ];
    }
}
