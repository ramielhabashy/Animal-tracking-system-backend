<?php

namespace Database\Seeders;

use App\Models\SubscriptionOrder;
use App\Models\User;
use App\Models\SubscriptionTier;
use App\Models\UserSubscription;
use Illuminate\Database\Seeder;

class SubscriptionOrderSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereHas('roles', fn($q) => $q->where('name', 'Owner'))->limit(3)->get();
        $tiers = SubscriptionTier::all();

        if ($users->isEmpty() || $tiers->isEmpty()) return;

        $statuses = ['pending', 'paid', 'paid', 'paid', 'pending'];
        $shippingStatuses = ['pending', 'shipped', 'delivered', 'pending', 'pending'];

        foreach ($users as $i => $user) {
            $tier = $tiers->random();
            $subscription = UserSubscription::where('user_id', $user->id)->first();
            $isPaid = $statuses[$i] === 'paid';

            SubscriptionOrder::create([
                'user_id' => $user->id,
                'tier_id' => $tier->id,
                'user_subscription_id' => $subscription?->id,
                'amount' => $tier->price_monthly > 0 ? $tier->price_monthly : 49.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'shipping_address' => [
                    'full_name' => $user->name,
                    'street' => '123 King Fahd Road',
                    'city' => 'Riyadh',
                    'state' => '',
                    'zip' => '12345',
                    'country' => 'Saudi Arabia',
                ],
                'shipping_status' => $shippingStatuses[$i],
                'tracking_number' => $isPaid ? 'TRK-' . strtoupper(substr(md5($user->id . time()), 0, 8)) : null,
                'shipped_at' => $shippingStatuses[$i] === 'shipped' || $shippingStatuses[$i] === 'delivered' ? now()->subDays(3) : null,
                'delivered_at' => $shippingStatuses[$i] === 'delivered' ? now()->subDays(1) : null,
                'payment_method' => $isPaid ? 'card' : 'bank_transfer',
                'payment_status' => $statuses[$i],
                'stripe_session_id' => $isPaid ? 'cs_test_' . bin2hex(random_bytes(16)) : null,
                'payment_reference' => $isPaid ? 'PAY-' . strtoupper(uniqid()) : null,
                'notes' => $i === 0 ? 'First order - priority processing' : null,
            ]);
        }
    }
}
