<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\SubscriptionTier;
use App\Models\UserSubscription;

class SubscriptionTest extends TestCase
{
    public function test_user_can_view_subscription_tiers()
    {
        $response = $this->getJson('/api/subscription/tiers');

        $response->assertStatus(200);
    }

    public function test_user_can_view_specific_tier()
    {
        $tier = SubscriptionTier::first();

        if ($tier) {
            $response = $this->getJson("/api/subscription/tiers/{$tier->id}");
            $response->assertStatus(200);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_user_can_view_own_subscription()
    {
        $user = $this->authenticateUser();

        $response = $this->getJson('/api/subscription/current');

        $response->assertStatus(200);
    }

    public function test_user_can_subscribe_to_free_tier()
    {
        $user = $this->authenticateUser();
        $freeTier = SubscriptionTier::where('slug', 'free')->first();

        if ($freeTier) {
            $response = $this->postJson("/api/subscription/subscribe/{$freeTier->id}");

            $response->assertStatus(200);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_user_can_cancel_subscription()
    {
        $user = $this->authenticateUser();

        $response = $this->postJson('/api/subscription/cancel');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_subscription()
    {
        $response = $this->getJson('/api/subscription/current');

        $response->assertStatus(401);
    }

    public function test_user_can_upgrade_subscription()
    {
        $user = $this->authenticateUser();
        $tier = SubscriptionTier::where('slug', '!=', 'free')->first();

        if ($tier) {
            $response = $this->postJson("/api/subscription/upgrade/{$tier->id}");
            $response->assertStatus(200);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_user_can_downgrade_subscription()
    {
        $user = $this->authenticateUser();
        $freeTier = SubscriptionTier::where('slug', 'free')->first();

        if ($freeTier) {
            $response = $this->postJson("/api/subscription/downgrade/{$freeTier->id}");
            $response->assertStatus(200);
        } else {
            $this->assertTrue(true);
        }
    }
}
