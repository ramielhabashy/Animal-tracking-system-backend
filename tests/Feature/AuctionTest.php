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
            'ends_at' => now()->addDays(7)->toISOString(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('auctions', ['title' => 'Premium Cattle Auction']);
    }

    public function test_user_can_view_auction()
    {
        $user = $this->authenticateUser();
        $auction = Auction::factory()->create();

        $response = $this->getJson("/api/auctions/{$auction->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $auction->id]);
    }

    public function test_user_can_update_own_auction()
    {
        $user = $this->authenticateUser();
        $auction = Auction::factory()->create(['seller_id' => $user->id]);

        $response = $this->putJson("/api/auctions/{$auction->id}", [
            'title' => 'Updated Auction Title',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('auctions', ['id' => $auction->id, 'title' => 'Updated Auction Title']);
    }

    public function test_user_can_place_bid()
    {
        $user = $this->authenticateUser();
        $auction = Auction::factory()->create(['status' => 'active']);

        $response = $this->postJson("/api/auctions/{$auction->id}/bid", [
            'amount' => 1500,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('bids', ['auction_id' => $auction->id, 'bidder_id' => $user->id]);
    }

    public function test_user_can_view_my_auctions()
    {
        $user = $this->authenticateUser();
        Auction::factory()->count(2)->create(['seller_id' => $user->id]);

        $response = $this->getJson('/api/auctions/my');

        $response->assertStatus(200);
    }

    public function test_user_can_cancel_own_auction()
    {
        $user = $this->authenticateUser();
        $auction = Auction::factory()->create(['seller_id' => $user->id, 'status' => 'active']);

        $response = $this->postJson("/api/auctions/{$auction->id}/cancel");

        $response->assertStatus(200);
    }
}
