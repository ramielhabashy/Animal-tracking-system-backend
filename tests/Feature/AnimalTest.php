<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Animal;
use App\Models\Species;

class AnimalTest extends TestCase
{
    public function test_user_can_list_animals()
    {
        $user = $this->authenticateUser();

        Animal::factory()->count(3)->create(['owner_id' => $user->id]);

        $response = $this->getJson('/api/animals');

        $response->assertStatus(200);
    }

    public function test_user_can_create_animal()
    {
        $user = $this->authenticateUser();

        $species = Species::first() ?: Species::factory()->create();

        $response = $this->postJson('/api/animals', [
            'name' => 'Bessie',
            'species_id' => $species->id,
            'breed_id' => null,
            'gender' => 'female',
            'birth_date' => '2020-01-01',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('animals', ['name' => 'Bessie', 'owner_id' => $user->id]);
    }

    public function test_user_can_view_animal()
    {
        $user = $this->authenticateUser();
        $animal = Animal::factory()->create(['owner_id' => $user->id]);

        $response = $this->getJson("/api/animals/{$animal->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $animal->id]);
    }

    public function test_user_can_update_animal()
    {
        $user = $this->authenticateUser();
        $animal = Animal::factory()->create(['owner_id' => $user->id]);

        $response = $this->putJson("/api/animals/{$animal->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('animals', ['id' => $animal->id, 'name' => 'Updated Name']);
    }

    public function test_user_can_delete_animal()
    {
        $user = $this->authenticateUser();
        $animal = Animal::factory()->create(['owner_id' => $user->id]);

        $response = $this->deleteJson("/api/animals/{$animal->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('animals', ['id' => $animal->id]);
    }

    public function test_user_cannot_view_other_users_animal()
    {
        $user = $this->authenticateUser();
        $otherUser = $this->createUser();
        $animal = Animal::factory()->create(['owner_id' => $otherUser->id]);

        $response = $this->getJson("/api/animals/{$animal->id}");

        $response->assertStatus(403);
    }
}
