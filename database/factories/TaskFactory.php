<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;
    
    public function definition(): array
    {
        return [
            'owner_id' => null,
            'assigned_to' => null,
            'title' => $this->faker->sentence(3),
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
        ];
    }
}
