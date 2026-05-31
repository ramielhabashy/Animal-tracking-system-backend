<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\MedicalRecord;
use App\Models\Animal;

class MedicalRecordTest extends TestCase
{
    public function test_user_can_list_medical_records()
    {
        $user = $this->authenticateUser();

        $response = $this->getJson('/api/medical-records');

        $response->assertStatus(200);
    }

    public function test_user_can_create_medical_record()
    {
        $user = $this->authenticateUser();
        $animal = Animal::factory()->create(['owner_id' => $user->id]);

        $response = $this->postJson('/api/medical-records', [
            'animal_id' => $animal->id,
            'record_type' => 'vaccination',
            'title' => 'Annual Vaccination',
            'description' => 'Routine vaccination',
            'record_date' => now()->toISOString(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('medical_records', ['title' => 'Annual Vaccination']);
    }

    public function test_user_can_view_medical_record()
    {
        $user = $this->authenticateUser();
        $animal = Animal::factory()->create(['owner_id' => $user->id]);
        $record = MedicalRecord::factory()->create(['animal_id' => $animal->id, 'owner_id' => $user->id]);

        $response = $this->getJson("/api/medical-records/{$record->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['id' => $record->id]]);
    }

    public function test_user_can_update_medical_record()
    {
        $user = $this->authenticateUser();
        $animal = Animal::factory()->create(['owner_id' => $user->id]);
        $record = MedicalRecord::factory()->create(['animal_id' => $animal->id, 'owner_id' => $user->id]);

        $response = $this->putJson("/api/medical-records/{$record->id}", [
            'title' => 'Updated Medical Record',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('medical_records', ['id' => $record->id, 'title' => 'Updated Medical Record']);
    }

    public function test_user_can_delete_medical_record()
    {
        $user = $this->authenticateUser();
        $animal = Animal::factory()->create(['owner_id' => $user->id]);
        $record = MedicalRecord::factory()->create(['animal_id' => $animal->id, 'owner_id' => $user->id]);

        $response = $this->deleteJson("/api/medical-records/{$record->id}");

        $response->assertStatus(200);
    }

    public function test_user_can_view_medical_record_stats()
    {
        $user = $this->authenticateUser();

        $response = $this->getJson('/api/medical-records/stats');

        $response->assertStatus(200);
    }
}
