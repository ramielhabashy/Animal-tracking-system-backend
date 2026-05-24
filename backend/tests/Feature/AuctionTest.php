<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Auction;
use App\Models\Animal;

class AuctionTest extends TestCase
{
    public function test_user_can_list_auctions()
    {
        $user = $this->authenticateUser();

        $response = $this->getJson('/api/auctions');

        $response->assertStatus(200);
    }

    public function test_user_can_create_auction()
    {
        $user = $this->authenticateUser();
        $animal = Animal::factory()->create(['owner_id' => $user->id]);

        $response = $this->postJson('/api/auctions', [
            'title' => 'Premium Cattle Auction',
            'description' => 'High quality cattle for sale',
            'animal_id' => $animal->id,
            'starting_price' => 1000,
            'min_price' => 800,
            'duration_hours' => 168,
            'ends_at' => now()->addDays(7)->toISOString(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('auctions', ['title' => 'Premium Cattle Auction']);
    }

    public function test_user_can_view_auction()
    {
        $user = $this->authenticateUser();
        $animal = Animal::factory()->create(['owner_id' => $user->id]);
        $auction = Auction::factory()->create(['animal_id' => $animal->id, 'owner_id' => $user->id]);

        $response = $this->getJson("/api/auctions/{$auction->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['id' => $auction->id]]);
    }

    public function test_user_can_update_own_auction()
    {
        $user = $this->authenticateUser();
        $animal = Animal::factory()->create(['owner_id' => $user->id]);
        $auction = Auction::factory()->create(['animal_id' => $animal->id, 'owner_id' => $user->id]);

        $response = $this->putJson("/api/auctions/{$auction->id}", [
            'title' => 'Updated Auction Title',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('auctions', ['id' => $auction->id, 'title' => 'Updated Auction Title']);
    }

    public function test_user_can_place_bid()
    {
        $owner = $this->createUser();
        $bidder = $this->createUser(['email' => 'bidder@test.com']);
        $this->authenticateUser($bidder);
        $animal = Animal::factory()->create(['owner_id' => $owner->id]);
        $auction = Auction::factory()->create(['animal_id' => $animal->id, 'owner_id' => $owner->id, 'status' => 'active']);

        $response = $this->postJson("/api/auctions/{$auction->id}/bid", [
            'amount' => 1500,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bids', ['auction_id' => $auction->id, 'user_id' => $bidder->id]);
    }

    public function test_user_can_view_my_auctions()
    {
        $user = $this->authenticateUser();
        $animal = Animal::factory()->create(['owner_id' => $user->id]);
        Auction::factory()->count(2)->create(['animal_id' => $animal->id, 'owner_id' => $user->id]);

        $response = $this->getJson('/api/auctions/my');

        $response->assertStatus(200);
    }

    public function test_user_can_cancel_own_auction()
    {
        $user = $this->authenticateUser();
        $animal = Animal::factory()->create(['owner_id' => $user->id]);
        $auction = Auction::factory()->create(['animal_id' => $animal->id, 'owner_id' => $user->id, 'status' => 'active']);

        $response = $this->postJson("/api/auctions/{$auction->id}/cancel");

        $response->assertStatus(200);
    }
}
