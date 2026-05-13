<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;

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
        $task = Task::factory()->create(['owner_id' => $user->id, 'assigned_to' => $user->id]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
    }

    public function test_user_can_update_task()
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->create(['owner_id' => $user->id, 'assigned_to' => $user->id]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Task Title',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Updated Task Title']);
    }

    public function test_user_can_complete_task()
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->create(['owner_id' => $user->id, 'assigned_to' => $user->id, 'status' => 'in_progress']);

        $response = $this->postJson("/api/tasks/{$task->id}/complete");

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'completed']);
    }

    public function test_user_can_delete_task()
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->create(['owner_id' => $user->id, 'assigned_to' => $user->id]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
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

    public function test_user_can_deliver_task()
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->create(['owner_id' => $user->id, 'assigned_to' => $user->id, 'status' => 'in_progress']);

        $response = $this->postJson("/api/tasks/{$task->id}/deliver", [
            'notes' => 'Task completed, please review',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'delivered']);
    }

    public function test_user_cannot_deliver_already_delivered_task()
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->create(['owner_id' => $user->id, 'assigned_to' => $user->id, 'status' => 'delivered']);

        $response = $this->postJson("/api/tasks/{$task->id}/deliver");

        $response->assertStatus(400);
    }

    public function test_owner_can_approve_delivered_task()
    {
        $owner = $this->authenticateUser();
        $task = Task::factory()->create(['owner_id' => $owner->id, 'assigned_to' => $owner->id, 'status' => 'delivered']);

        $response = $this->postJson("/api/tasks/{$task->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'completed']);
    }

    public function test_owner_can_reject_delivered_task()
    {
        $owner = $this->authenticateUser();
        $task = Task::factory()->create(['owner_id' => $owner->id, 'assigned_to' => $owner->id, 'status' => 'delivered']);

        $response = $this->postJson("/api/tasks/{$task->id}/reject", [
            'notes' => 'Needs more work',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'in_progress']);
    }

    public function test_reject_requires_notes()
    {
        $owner = $this->authenticateUser();
        $task = Task::factory()->create(['owner_id' => $owner->id, 'assigned_to' => $owner->id, 'status' => 'delivered']);

        $response = $this->postJson("/api/tasks/{$task->id}/reject", []);

        $response->assertStatus(422);
    }

    public function test_shepherd_cannot_approve_task()
    {
        $shepherd = $this->createUser(['role' => 'Shepherd']);
        $owner = $this->createUser();
        $task = Task::factory()->create(['owner_id' => $owner->id, 'assigned_to' => $shepherd->id, 'status' => 'delivered']);

        $this->authenticateUser($shepherd);
        $response = $this->postJson("/api/tasks/{$task->id}/approve");

        $response->assertStatus(403);
    }

    public function test_shepherd_cannot_reject_task()
    {
        $shepherd = $this->createUser(['role' => 'Shepherd']);
        $owner = $this->createUser();
        $task = Task::factory()->create(['owner_id' => $owner->id, 'assigned_to' => $shepherd->id, 'status' => 'delivered']);

        $this->authenticateUser($shepherd);
        $response = $this->postJson("/api/tasks/{$task->id}/reject", ['notes' => 'Not good']);

        $response->assertStatus(403);
    }

    public function test_shepherd_can_deliver_assigned_task()
    {
        $shepherd = $this->createUser(['role' => 'Shepherd']);
        $owner = $this->createUser();
        $task = Task::factory()->create(['owner_id' => $owner->id, 'assigned_to' => $shepherd->id, 'status' => 'in_progress']);

        $this->authenticateUser($shepherd);
        $response = $this->postJson("/api/tasks/{$task->id}/deliver", ['notes' => 'Done']);

        $response->assertStatus(200);
    }

    public function test_shepherd_cannot_reassign_task()
    {
        $shepherd = $this->createUser(['role' => 'Shepherd']);
        $owner = $this->createUser();
        $otherShepherd = $this->createUser(['role' => 'Shepherd']);

        $task = Task::factory()->create(['owner_id' => $owner->id, 'assigned_to' => $shepherd->id]);

        $this->authenticateUser($shepherd);
        $response = $this->postJson("/api/tasks/{$task->id}/reassign", [
            'assigned_to' => $otherShepherd->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_reassign_task()
    {
        $owner = $this->authenticateUser();
        $otherUser = $this->createUser();
        $otherUser->managed_by = $owner->id;
        $otherUser->save();

        $task = Task::factory()->create(['owner_id' => $owner->id, 'assigned_to' => $owner->id]);

        $response = $this->postJson("/api/tasks/{$task->id}/reassign", [
            'assigned_to' => $otherUser->id,
        ]);

        $response->assertStatus(200);
    }

    public function test_shepherd_can_only_assign_to_self()
    {
        $shepherd = $this->createUser(['role' => 'Shepherd']);

        $this->authenticateUser($shepherd);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Test task',
            'assigned_to' => $shepherd->id,
            'due_date' => now()->addDay()->toISOString(),
        ]);

        $response->assertStatus(201);
    }

    public function test_shepherd_cannot_assign_to_others()
    {
        $shepherd = $this->createUser(['role' => 'Shepherd']);

        $otherUser = $this->createUser();

        $this->authenticateUser($shepherd);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Test task',
            'assigned_to' => $otherUser->id,
            'due_date' => now()->addDay()->toISOString(),
        ]);

        $response->assertStatus(403);
    }

    public function test_task_logs_only_for_recurring_tasks()
    {
        $owner = $this->authenticateUser();
        $task = Task::factory()->create(['owner_id' => $owner->id, 'assigned_to' => $owner->id, 'is_recurring' => false]);

        $response = $this->postJson('/api/task-logs', [
            'task_id' => $task->id,
            'log_type' => 'note',
            'description' => 'Test log',
        ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('recurring', $response->json('message'));
    }

    public function test_task_logs_allowed_for_recurring_tasks()
    {
        $owner = $this->authenticateUser();
        $task = Task::factory()->create(['owner_id' => $owner->id, 'assigned_to' => $owner->id, 'is_recurring' => true]);

        $response = $this->postJson('/api/task-logs', [
            'task_id' => $task->id,
            'log_type' => 'note',
            'description' => 'Test log for recurring task',
        ]);

        $response->assertStatus(201);
    }
}
