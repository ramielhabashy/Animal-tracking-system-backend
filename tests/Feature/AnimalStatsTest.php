<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Animal;
use App\Models\Device;

class AnimalStatsTest extends TestCase
{
    public function test_stats_endpoint_returns_expected_counts()
    {
        $user = $this->authenticateUser();

        Animal::factory()->count(3)->create(['owner_id' => $user->id]);
        $animalWithDevice = Animal::factory()->create(['owner_id' => $user->id]);
        Device::factory()->create(['owner_id' => $user->id, 'animal_id' => $animalWithDevice->id]);

        $response = $this->getJson('/api/animals/stats');

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 4,
            'assigned' => 1,
            'unassigned' => 3,
        ]);
    }

    public function test_stats_endpoint_respects_owner_filter()
    {
        $user = $this->authenticateUser();
        $other = $this->createUser();
        Animal::factory()->count(5)->create(['owner_id' => $other->id]);

        $response = $this->getJson('/api/animals/stats');

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('total'));
    }

    public function test_stats_endpoint_temperature_thresholds()
    {
        $user = $this->authenticateUser();

        Animal::factory()->create(['owner_id' => $user->id, 'baseline_temperature' => 38.5]);
        Animal::factory()->create(['owner_id' => $user->id, 'baseline_temperature' => 39.2]);
        Animal::factory()->create(['owner_id' => $user->id, 'baseline_temperature' => 39.8]);

        $response = $this->getJson('/api/animals/stats');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('healthy'));
        $this->assertEquals(1, $response->json('warning'));
        $this->assertEquals(1, $response->json('critical'));
    }

    public function test_stats_endpoint_custom_thresholds()
    {
        $user = $this->authenticateUser();

        Animal::factory()->create(['owner_id' => $user->id, 'baseline_temperature' => 38.5]);
        Animal::factory()->create(['owner_id' => $user->id, 'baseline_temperature' => 39.5]);
        Animal::factory()->create(['owner_id' => $user->id, 'baseline_temperature' => 40.0]);

        $response = $this->getJson('/api/animals/stats?healthy_threshold=39.0&warning_threshold=39.8');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('healthy'));
        $this->assertEquals(1, $response->json('warning'));
        $this->assertEquals(1, $response->json('critical'));
    }

    public function test_admin_can_see_all_animal_stats()
    {
        $admin = $this->createUser(['role' => 'Admin']);
        $this->authenticateUser($admin);
        $other = $this->createUser();
        Animal::factory()->count(3)->create(['owner_id' => $other->id]);

        $response = $this->getJson('/api/animals/stats');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('total'));
    }
}
