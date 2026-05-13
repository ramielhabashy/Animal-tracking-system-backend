<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Geofence;

class GeofenceTest extends TestCase
{
    public function test_user_can_list_geofences()
    {
        $user = $this->authenticateUser();

        Geofence::factory()->count(3)->create(['owner_id' => $user->id]);

        $response = $this->getJson('/api/geofences');

        $response->assertStatus(200);
    }

    public function test_user_can_create_geofence()
    {
        $user = $this->authenticateUser();

        $response = $this->postJson('/api/geofences', [
            'name' => 'Farm Area A',
            'coordinates' => json_encode([['lat' => 40.7128, 'lng' => -74.0060]]),
            'radius' => 100,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('geofences', ['name' => 'Farm Area A', 'owner_id' => $user->id]);
    }

    public function test_user_can_view_geofence()
    {
        $user = $this->authenticateUser();
        $geofence = Geofence::factory()->create(['owner_id' => $user->id]);

        $response = $this->getJson("/api/geofences/{$geofence->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $geofence->id]);
    }

    public function test_user_can_update_geofence()
    {
        $user = $this->authenticateUser();
        $geofence = Geofence::factory()->create(['owner_id' => $user->id]);

        $response = $this->putJson("/api/geofences/{$geofence->id}", [
            'name' => 'Updated Geofence Name',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('geofences', ['id' => $geofence->id, 'name' => 'Updated Geofence Name']);
    }

    public function test_user_can_delete_geofence()
    {
        $user = $this->authenticateUser();
        $geofence = Geofence::factory()->create(['owner_id' => $user->id]);

        $response = $this->deleteJson("/api/geofences/{$geofence->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('geofences', ['id' => $geofence->id]);
    }

    public function test_geofence_alerts_endpoint_works()
    {
        $user = $this->authenticateUser();

        $response = $this->getJson('/api/geofence-alerts');

        $response->assertStatus(200);
    }
}
