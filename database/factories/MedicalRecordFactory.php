<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalRecordFactory extends Factory
{
    protected $model = MedicalRecord::class;
    
    public function definition(): array
    {
        return [
            'animal_id' => null,
            'owner_id' => null,
            'record_type' => 'checkup',
            'title' => $this->faker->sentence(4),
            'record_date' => $this->faker->date(),
            'status' => 'completed',
        ];
    }
}
