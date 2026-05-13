<?php

namespace Database\Factories;

use App\Models\Animal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AnimalFactory extends Factory
{
    protected $model = Animal::class;
    
    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName(),
            'species' => 'Camel',
            'breed' => 'Majaheim',
            'gender' => 'Male',
            'date_of_birth' => $this->faker->date(),
            'owner_id' => null,
        ];
    }
}
