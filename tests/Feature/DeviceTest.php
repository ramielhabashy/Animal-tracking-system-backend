<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Device;

class DeviceTest extends TestCase
{
    public function test_user_can_list_devices()
    {
        $user = $this->authenticateUser();

        Device::factory()->count(3)->create(['owner_id' => $user->id]);

        $response = $this->getJson('/api/devices');

        $response->assertStatus(200);
    }

    public function test_user_can_create_device()
    {
        $user = $this->authenticateUser();

        $response = $this->postJson('/api/devices', [
            'name' => 'Tracker 1',
            'device_id' => 'TRK-001',
            'type' => 'gps_tracker',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('devices', ['name' => 'Tracker 1', 'owner_id' => $user->id]);
    }

    public function test_user_can_view_device()
    {
        $user = $this->authenticateUser();
        $device = Device::factory()->create(['owner_id' => $user->id]);

        $response = $this->getJson("/api/devices/{$device->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['id' => $device->id]]);
    }

    public function test_user_can_update_device()
    {
        $user = $this->authenticateUser();
        $device = Device::factory()->create(['owner_id' => $user->id]);

        $response = $this->putJson("/api/devices/{$device->id}", [
            'name' => 'Updated Device Name',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('devices', ['id' => $device->id, 'name' => 'Updated Device Name']);
    }

    public function test_user_can_delete_device()
    {
        $user = $this->authenticateUser();
        $device = Device::factory()->create(['owner_id' => $user->id]);

        $response = $this->deleteJson("/api/devices/{$device->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('devices', ['id' => $device->id]);
    }
}
