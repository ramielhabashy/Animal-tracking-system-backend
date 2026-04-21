<?php

namespace Tests\Feature\Auction;

use Tests\TestCase;
use App\Models\Auction;
use App\Models\Animal;
use App\Models\Bid;

class AuctionCrudTest extends TestCase
{
    protected $owner;
    protected $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->createUser(['email' => 'owner@test.com', 'role' => 'Owner']);
        $this->otherUser = $this->createUser(['email' => 'other@test.com', 'role' => 'Owner']);
    }

    protected function authAsOwner(): array
    {
        return [
            'X-User-Id' => (string) $this->owner->id,
            'X-User-Role' => $this->owner->role,
        ];
    }

    protected function createAuction(array $overrides = []): \App\Models\Auction
    {
        $animal = $overrides['animal'] ?? $this->createAnimal(['owner_id' => $this->owner->id]);
        return \App\Models\Auction::create([
            'animal_id' => $animal->id,
            'owner_id' => $overrides['owner_id'] ?? $this->owner->id,
            'title' => $overrides['title'] ?? 'Test Auction ' . uniqid(),
            'starting_price' => $overrides['starting_price'] ?? 100,
            'current_price' => $overrides['current_price'] ?? 100,
            'reserve_price' => $overrides['reserve_price'] ?? null,
            'status' => $overrides['status'] ?? 'active',
            'description' => $overrides['description'] ?? null,
            'starts_at' => $overrides['starts_at'] ?? now(),
            'ends_at' => $overrides['ends_at'] ?? now()->addHours(24),
        ]);
    }

    protected function createBid(array $overrides = []): \App\Models\Bid
    {
        $auction = $overrides['auction'] ?? null;
        if (!$auction) {
            $animal = $this->createAnimal(['owner_id' => $this->otherUser->id]);
            $auction = $this->createAuction(['animal' => $animal]);
        }
        return \App\Models\Bid::create([
            'auction_id' => $auction->id,
            'user_id' => $overrides['user_id'] ?? $this->otherUser->id,
            'amount' => $overrides['amount'] ?? 150,
            'bidder_name' => $overrides['bidder_name'] ?? 'Bidder',
            'bid_at' => $overrides['bid_at'] ?? now(),
        ]);
    }

    public function test_owner_can_create_auction(): void
    {
        $animal = $this->createAnimal(['owner_id' => $this->owner->id]);
        $response = $this->postJson('/api/auctions', [
            'animal_id' => $animal->id,
            'title' => 'Test Auction',
            'starting_price' => 100,
            'duration_hours' => 24,
        ], $this->authAsOwner());

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Auction');

        $this->assertDatabaseHas('auctions', ['title' => 'Test Auction']);
    }

    public function test_owner_cannot_auction_other_users_animal(): void
    {
        $otherAnimal = $this->createAnimal(['owner_id' => $this->otherUser->id]);

        $response = $this->postJson('/api/auctions', [
            'animal_id' => $otherAnimal->id,
            'title' => 'Test Auction',
            'starting_price' => 100,
            'duration_hours' => 24,
        ], $this->authAsOwner());

        $response->assertStatus(403);
    }

    public function test_owner_can_view_their_auctions(): void
    {
        $this->createAuction();

        $response = $this->getJson('/api/auctions', $this->authAsOwner());

        $response->assertStatus(200);
    }

    public function test_owner_can_view_own_auction_details(): void
    {
        $auction = $this->createAuction();

        $response = $this->getJson("/api/auctions/{$auction->id}", $this->authAsOwner());

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $auction->id);
    }

    public function test_owner_can_update_auction_without_bids(): void
    {
        $auction = $this->createAuction(['status' => 'draft']);

        $response = $this->putJson("/api/auctions/{$auction->id}", [
            'title' => 'Updated Title',
            'starting_price' => 200,
        ], $this->authAsOwner());

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_owner_cannot_update_auction_with_bids(): void
    {
        $auction = $this->createAuction([
            'owner_id' => $this->owner->id,
            'status' => 'active',
        ]);

        $this->createBid(['auction' => $auction]);

        $response = $this->putJson("/api/auctions/{$auction->id}", [
            'title' => 'Updated Title',
        ], $this->authAsOwner());

        $response->assertStatus(400);
    }

    public function test_owner_can_delete_auction_without_bids(): void
    {
        $auction = $this->createAuction(['status' => 'draft']);

        $response = $this->deleteJson("/api/auctions/{$auction->id}", [], $this->authAsOwner());

        $response->assertStatus(200);
        $this->assertDatabaseMissing('auctions', ['id' => $auction->id]);
    }

    public function test_owner_cannot_delete_auction_with_bids(): void
    {
        $auction = $this->createAuction(['status' => 'active']);

        $this->createBid(['auction' => $auction]);

        $response = $this->deleteJson("/api/auctions/{$auction->id}", [], $this->authAsOwner());

        $response->assertStatus(400);
    }

    public function test_auction_requires_required_fields(): void
    {
        $response = $this->postJson('/api/auctions', [], $this->authAsOwner());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['animal_id', 'title', 'starting_price', 'duration_hours']);
    }

    public function test_auction_requires_valid_duration(): void
    {
        $animal = $this->createAnimal(['owner_id' => $this->owner->id]);
        $response = $this->postJson('/api/auctions', [
            'animal_id' => $animal->id,
            'title' => 'Test',
            'starting_price' => 100,
            'duration_hours' => 200,
        ], $this->authAsOwner());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['duration_hours']);
    }
}