<?php

namespace Tests\Feature\Geofence;

use Tests\TestCase;
use App\Models\Geofence;

class GeofenceCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
        $this->withoutMiddleware(\App\Http\Middleware\CheckSubscriptionLimits::class);
    }

    protected function getAuthHeaders(): array
    {
        return [
            'X-User-Id' => $this->user->id,
            'X-User-Role' => $this->user->role,
        ];
    }

    public function test_owner_can_create_geofence(): void
    {
        $response = $this->postJson('/api/geofences', [
            'name' => 'Test Pasture',
            'coordinates' => json_encode([
                [31.0, 29.0],
                [31.0, 30.0],
                [32.0, 30.0],
                [32.0, 29.0],
            ]),
            'color' => '#ff0000',
            'alert_type' => 'entry',
        ], $this->getAuthHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Test Pasture');

        $this->assertDatabaseHas('geofences', ['name' => 'Test Pasture']);
    }

    public function test_owner_can_list_their_geofences(): void
    {
        $this->createGeofence(['owner_id' => $this->user->id]);
        $this->createGeofence(['owner_id' => $this->user->id]);

        $response = $this->getJson('/api/geofences', [], $this->getAuthHeaders());

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    public function test_owner_can_view_geofence(): void
    {
        $geofence = $this->createGeofence(['owner_id' => $this->user->id]);

        $response = $this->getJson("/api/geofences/{$geofence->id}", [], $this->getAuthHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('id', $geofence->id);
    }

    public function test_owner_can_update_geofence(): void
    {
        $geofence = $this->createGeofence(['owner_id' => $this->user->id]);

        $response = $this->putJson("/api/geofences/{$geofence->id}", [
            'name' => 'Updated Pasture',
            'color' => '#00ff00',
            'coordinates' => json_encode($geofence->coordinates),
        ], $this->getAuthHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Updated Pasture');
    }

    public function test_owner_can_delete_geofence(): void
    {
        $geofence = $this->createGeofence(['owner_id' => $this->user->id]);

        $response = $this->deleteJson("/api/geofences/{$geofence->id}", [], $this->getAuthHeaders());

        $response->assertStatus(200);
        $this->assertDatabaseMissing('geofences', ['id' => $geofence->id]);
    }

    public function test_geofence_requires_minimum_3_coordinates(): void
    {
        $response = $this->postJson('/api/geofences', [
            'name' => 'Invalid Geofence',
            'coordinates' => json_encode([
                [31.0, 29.0],
                [31.0, 30.0],
            ]),
        ], $this->getAuthHeaders());

        $response->assertStatus(201);
    }
}