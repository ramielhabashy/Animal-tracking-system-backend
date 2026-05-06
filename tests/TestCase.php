<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createUser(array $attributes = []): \App\Models\User
    {
        $user = \App\Models\User::factory()->create(array_merge([
            'password' => Hash::make('password'),
            'is_active' => true,
        ], $attributes));

        if (!isset($attributes['subscription_tier_id'])) {
            $freeTier = \App\Models\SubscriptionTier::where('slug', 'free')->first();
            if ($freeTier) {
                $user->update(['subscription_tier_id' => $freeTier->id]);
            }
        }

        return $user;
    }

    protected function authenticateUser(\App\Models\User $user = null): \App\Models\User
    {
        $user = $user ?: $this->createUser();
        $token = $user->createToken('test-token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer ' . $token);
        return $user;
    }
}
