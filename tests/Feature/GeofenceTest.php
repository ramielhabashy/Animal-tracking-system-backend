<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Geofence;
use App\Models\Animal;
use App\Models\User;

class GeofenceTest extends TestCase
{
    public function test_user_can_list_geofences()
    {
        $user = $this->authenticateUser();
        Geofence::factory()->count(3)->create(['owner_id' => $user->id]);

        $response = $this->getJson('/api/geofences');

        $response->assertStatus(200);
    }

    public function test_user_cannot_see_other_users_geofences()
    {
        $user = $this->authenticateUser();
        $other = $this->createUser();
        Geofence::factory()->create(['owner_id' => $other->id, 'name' => 'Other Fence']);
        Geofence::factory()->create(['owner_id' => $user->id, 'name' => 'My Fence']);

        $response = $this->getJson('/api/geofences');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('My Fence', $names);
        $this->assertNotContains('Other Fence', $names);
    }

    public function test_user_cannot_access_other_users_geofence()
    {
        $user = $this->authenticateUser();
        $other = $this->createUser();
        $geofence = Geofence::factory()->create(['owner_id' => $other->id]);

        $response = $this->getJson("/api/geofences/{$geofence->id}");

        $response->assertStatus(403);
    }

    public function test_user_cannot_modify_other_users_geofence()
    {
        $user = $this->authenticateUser();
        $other = $this->createUser();
        $geofence = Geofence::factory()->create(['owner_id' => $other->id]);

        $response = $this->putJson("/api/geofences/{$geofence->id}", ['name' => 'Hacked']);
        $response->assertStatus(403);

        $response = $this->deleteJson("/api/geofences/{$geofence->id}");
        $response->assertStatus(403);
    }

    public function test_user_can_create_geofence()
    {
        $user = $this->authenticateUser();
        $response = $this->postJson('/api/geofences', [
            'name' => 'Farm Area A',
            'coordinates' => json_encode([[25.0, 55.0], [25.1, 55.0], [25.1, 55.1], [25.0, 55.1]]),
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

    public function test_contains_point_inside_polygon()
    {
        $geofence = Geofence::factory()->create([
            'coordinates' => [[25.0, 55.0], [25.1, 55.0], [25.1, 55.1], [25.0, 55.1]],
        ]);

        $this->assertTrue($geofence->containsPoint(25.05, 55.05));
    }

    public function test_contains_point_outside_polygon()
    {
        $geofence = Geofence::factory()->create([
            'coordinates' => [[25.0, 55.0], [25.1, 55.0], [25.1, 55.1], [25.0, 55.1]],
        ]);

        $this->assertFalse($geofence->containsPoint(26.0, 56.0));
    }

    public function test_contains_point_with_invalid_coordinates_returns_false()
    {
        $geofence = Geofence::factory()->create([
            'coordinates' => 'invalid',
        ]);

        $this->assertFalse($geofence->containsPoint(25.0, 55.0));
    }

    public function test_contains_point_with_less_than_3_points_returns_false()
    {
        $geofence = Geofence::factory()->create([
            'coordinates' => [[25.0, 55.0], [25.1, 55.0]],
        ]);

        $this->assertFalse($geofence->containsPoint(25.05, 55.05));
    }

    public function test_header_auth_spoofing_rejected()
    {
        $user = $this->createUser();
        $this->authenticateUser($user);
        $other = $this->createUser();
        $geofence = Geofence::factory()->create(['owner_id' => $other->id]);

        $response = $this->withHeaders([
            'X-User-Id' => $other->id,
            'X-User-Role' => 'Owner',
        ])->getJson("/api/geofences/{$geofence->id}");

        $response->assertStatus(403);
    }

    public function test_header_auth_spoofing_rejected_for_modification()
    {
        $user = $this->createUser();
        $this->authenticateUser($user);
        $other = $this->createUser();
        $geofence = Geofence::factory()->create(['owner_id' => $other->id]);

        $response = $this->withHeaders([
            'X-User-Id' => $other->id,
            'X-User-Role' => 'Owner',
        ])->deleteJson("/api/geofences/{$geofence->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_see_all_geofences()
    {
        $admin = $this->createUser(['role' => 'Admin']);
        $this->authenticateUser($admin);
        $other = $this->createUser();
        Geofence::factory()->create(['owner_id' => $other->id, 'name' => 'Other Fence']);

        $response = $this->getJson('/api/geofences');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Other Fence', $names);
    }
}
