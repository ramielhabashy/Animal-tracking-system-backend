<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;

class TaskTest extends TestCase
{
    public function test_user_can_list_tasks()
    {
        $user = $this->authenticateUser();

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200);
    }

    public function test_user_can_create_task()
    {
        $user = $this->authenticateUser();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Feed the animals',
            'description' => 'Morning feeding routine',
            'assigned_to' => $user->id,
            'due_date' => now()->addDay()->toISOString(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', ['title' => 'Feed the animals']);
    }

    public function test_user_can_view_task()
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $task->id]);
    }

    public function test_user_can_update_task()
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Task Title',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Updated Task Title']);
    }

    public function test_user_can_complete_task()
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->create(['assigned_to' => $user->id, 'status' => 'pending']);

        $response = $this->postJson("/api/tasks/{$task->id}/complete");

        $response->assertStatus(200);
    }

    public function test_user_can_delete_task()
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
    }

    public function test_user_can_view_my_tasks()
    {
        $user = $this->authenticateUser();

        $response = $this->getJson('/api/tasks/my');

        $response->assertStatus(200);
    }

    public function test_user_can_view_task_stats()
    {
        $user = $this->authenticateUser();

        $response = $this->getJson('/api/tasks/stats');

        $response->assertStatus(200);
    }
}
