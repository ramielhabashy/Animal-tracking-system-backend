<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Animal;
use App\Models\Device;
use App\Models\Geofence;
use App\Models\LocationHistory;

class LocationHistoryTest extends TestCase
{
    protected Animal $animal;
    protected Device $device;

    protected function setUp(): void
    {
        parent::setUp();
        $user = $this->createUser();
        $this->animal = Animal::factory()->create(['owner_id' => $user->id]);
        $this->device = Device::factory()->create([
            'owner_id' => $user->id,
            'animal_id' => $this->animal->id,
        ]);
    }

    public function test_user_can_submit_location_for_own_device()
    {
        $this->authenticateUser($this->animal->owner);

        $response = $this->postJson('/api/location-history', [
            'device_id' => $this->device->id,
            'latitude' => 25.05,
            'longitude' => 55.05,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('location_history', [
            'device_id' => $this->device->id,
            'animal_id' => $this->animal->id,
        ]);
    }

    public function test_user_cannot_submit_location_for_other_users_device()
    {
        $user = $this->authenticateUser();
        $other = $this->createUser();
        $otherAnimal = Animal::factory()->create(['owner_id' => $other->id]);
        $otherDevice = Device::factory()->create([
            'owner_id' => $other->id,
            'animal_id' => $otherAnimal->id,
        ]);

        $response = $this->postJson('/api/location-history', [
            'device_id' => $otherDevice->id,
            'latitude' => 25.05,
            'longitude' => 55.05,
        ]);

        $response->assertStatus(403);
    }

    public function test_submitting_location_updates_device_gps()
    {
        $this->authenticateUser($this->animal->owner);

        $this->postJson('/api/location-history', [
            'device_id' => $this->device->id,
            'latitude' => 25.12345,
            'longitude' => 55.67890,
        ]);

        $this->device->refresh();
        $this->assertEquals(25.12345, $this->device->gps_lat);
        $this->assertEquals(55.67890, $this->device->gps_lng);
        $this->assertEquals('online', $this->device->status);
    }

    public function test_submitting_location_triggers_geofence_alert_on_entry()
    {
        $this->authenticateUser($this->animal->owner);

        Geofence::factory()->create([
            'owner_id' => $this->animal->owner_id,
            'is_active' => true,
            'alert_type' => 'entry',
            'coordinates' => [[24.9, 54.9], [25.2, 54.9], [25.2, 55.2], [24.9, 55.2]],
        ]);

        // First submission outside the geofence to set baseline
        $this->postJson('/api/location-history', [
            'device_id' => $this->device->id,
            'latitude' => 26.0,
            'longitude' => 56.0,
        ]);

        // Second submission inside the geofence triggers entry alert
        $response = $this->postJson('/api/location-history', [
            'device_id' => $this->device->id,
            'latitude' => 25.05,
            'longitude' => 55.05,
        ]);

        $response->assertStatus(201);
        $data = $response->json();
        $this->assertTrue($data['alert_triggered']);

        $this->assertDatabaseHas('geofence_alerts', [
            'animal_id' => $this->animal->id,
            'type' => 'entry',
        ]);
    }

    public function test_submitting_location_without_animal_on_device_returns_404()
    {
        $user = $this->createUser();
        $unassignedDevice = Device::factory()->create([
            'owner_id' => $user->id,
            'animal_id' => null,
        ]);
        $this->authenticateUser($user);

        $response = $this->postJson('/api/location-history', [
            'device_id' => $unassignedDevice->id,
            'latitude' => 25.05,
            'longitude' => 55.05,
        ]);

        $response->assertStatus(404);
    }

    public function test_header_auth_spoofing_rejected_for_location_submission()
    {
        $user = $this->createUser();
        $this->authenticateUser($user);
        $other = $this->createUser();
        $otherAnimal = Animal::factory()->create(['owner_id' => $other->id]);
        $otherDevice = Device::factory()->create([
            'owner_id' => $other->id,
            'animal_id' => $otherAnimal->id,
        ]);

        $response = $this->withHeaders([
            'X-User-Id' => $other->id,
            'X-User-Role' => 'Owner',
        ])->postJson('/api/location-history', [
            'device_id' => $otherDevice->id,
            'latitude' => 25.05,
            'longitude' => 55.05,
        ]);

        $response->assertStatus(403);
    }

    public function test_location_history_index_filtered_by_owner()
    {
        $this->authenticateUser($this->animal->owner);

        LocationHistory::create([
            'animal_id' => $this->animal->id,
            'device_id' => $this->device->id,
            'latitude' => 25.05,
            'longitude' => 55.05,
            'recorded_at' => now(),
        ]);

        $response = $this->getJson("/api/animals/{$this->animal->id}/location-history");

        $response->assertStatus(200);
        $response->assertJsonPath('animal_id', $this->animal->id);
    }

    public function test_user_cannot_view_other_users_location_history()
    {
        $user = $this->authenticateUser();
        $other = $this->createUser();
        $otherAnimal = Animal::factory()->create(['owner_id' => $other->id]);

        $response = $this->getJson("/api/animals/{$otherAnimal->id}/location-history");

        $response->assertStatus(403);
    }
}
