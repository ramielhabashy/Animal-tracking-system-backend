<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Console\Command;

class CheckSubscriptionExpirations extends Command
{
    protected $signature = 'subscriptions:check-expirations';
    protected $description = 'Check subscription expirations and apply grace period or auto-cancel';

    public function handle()
    {
        $gracePeriodDays = (int) (Setting::get('subscription_grace_period_days') ?? 7);
        $autoCancel = Setting::getBoolean('subscription_auto_cancel_after_grace', true);

        // Find subscriptions that have passed their ends_at but are still active
        $expiredSubs = UserSubscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($expiredSubs as $sub) {
            $daysSinceExpiry = now()->diffInDays($sub->ends_at, false);

            if ($daysSinceExpiry <= $gracePeriodDays) {
                // Still within grace period - leave as active but mark as past_due
                if ($sub->status !== 'past_due') {
                    $sub->update(['status' => 'past_due']);
                    $this->info("Subscription #{$sub->id} moved to past_due (grace period)");
                }
            } elseif ($autoCancel) {
                // Past grace period - cancel
                $freeTier = \App\Models\SubscriptionTier::where('slug', 'free')->first();
                $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);

                if ($freeTier) {
                    $sub->user()->update(['subscription_tier_id' => $freeTier->id]);
                }

                Notification::create([
                    'user_id' => $sub->user_id,
                    'type' => 'subscription_expired',
                    'title' => 'Subscription Expired',
                    'body' => 'Your subscription has expired. Please renew to regain access to premium features.',
                    'data' => ['link' => '/subscription/select'],
                ]);

                $this->info("Subscription #{$sub->id} cancelled after grace period");
            }
        }

        // Find past_due subs that are now past grace period
        if ($autoCancel) {
            $pastDueSubs = UserSubscription::where('status', 'past_due')
                ->whereNotNull('ends_at')
                ->where('ends_at', '<', now()->subDays($gracePeriodDays))
                ->get();

            foreach ($pastDueSubs as $sub) {
                $freeTier = \App\Models\SubscriptionTier::where('slug', 'free')->first();
                $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);

                if ($freeTier) {
                    $sub->user()->update(['subscription_tier_id' => $freeTier->id]);
                }

                $this->info("Subscription #{$sub->id} auto-cancelled after grace period");
            }
        }

        $this->info('Subscription expiration check completed.');
        return Command::SUCCESS;
    }
}
