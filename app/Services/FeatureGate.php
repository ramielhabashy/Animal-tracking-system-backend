<?php

namespace App\Services;

use App\Models\SubscriptionTier;
use App\Models\User;

class FeatureGate
{
    public static function hasFeature(?User $user, string $feature): bool
    {
        if (!$user) {
            return false;
        }

        $tier = self::getUserTier($user);

        if (! $tier) {
            return false;
        }

        return match ($feature) {
            'geofencing' => $tier->has_geofencing,
            'auctions' => $tier->has_auctions,
            'advanced_reports' => $tier->has_advanced_reports,
            'api_access' => $tier->has_api_access,
            'ai_assistant' => $tier->has_ai_assistant,
            'medical_records' => $tier->has_medical_records,
            'tasks' => $tier->has_tasks,
            default => false,
        };
    }

    public static function canAccessFeature(?User $user, string $feature): array
    {
        if (!$user) {
            return ['allowed' => false, 'message' => 'Authentication required.'];
        }

        $hasAccess = self::hasFeature($user, $feature);

        if ($hasAccess) {
            return ['allowed' => true];
        }

        $tier = self::getUserTier($user);
        $tierName = $tier?->name ?? 'No subscription';

        return [
            'allowed' => false,
            'message' => 'This feature requires a higher subscription tier.',
            'upgrade_required' => $tierName,
            'feature' => $feature,
        ];
    }

    public static function getUserTier(User $user): ?SubscriptionTier
    {
        $tier = $user->subscriptionTier;

        if (! $tier) {
            return SubscriptionTier::where('slug', 'free')->first();
        }

        if ($tier->isFree()) {
            return $tier;
        }

        $activeSubscription = $user->activeSubscription();

        if ($activeSubscription) {
            return $tier;
        }

        // Check for past_due subscriptions (within grace period)
        $pastDueSubscription = $user->subscription()
            ->where('status', 'past_due')
            ->latest()
            ->first();

        if ($pastDueSubscription) {
            $gracePeriodDays = (int) (\App\Models\Setting::get('subscription_grace_period_days') ?? 7);
            if ($pastDueSubscription->ends_at && now()->diffInDays($pastDueSubscription->ends_at, false) <= $gracePeriodDays) {
                return $tier;
            }
        }

        $latestSubscription = $user->subscription()->latest()->first();

        if ($latestSubscription && $latestSubscription->isOnTrial()) {
            return $tier;
        }

        return SubscriptionTier::where('slug', 'free')->first();
    }

    public static function getUserFeatures(User $user): array
    {
        $tier = self::getUserTier($user);

        return [
            'tier_name' => $tier?->name ?? 'Unknown',
            'tier_slug' => $tier?->slug ?? 'free',
            'has_geofencing' => $tier?->has_geofencing ?? false,
            'has_auctions' => $tier?->has_auctions ?? false,
            'has_advanced_reports' => $tier?->has_advanced_reports ?? false,
            'has_api_access' => $tier?->has_api_access ?? false,
            'has_ai_assistant' => $tier?->has_ai_assistant ?? false,
            'has_medical_records' => $tier?->has_medical_records ?? false,
            'has_tasks' => $tier?->has_tasks ?? false,
            'max_animals' => $tier?->max_animals ?? 5,
            'max_devices' => $tier?->max_devices ?? 5,
            'max_users' => $tier?->max_users ?? 1,
        ];
    }

    public static function checkLimit(User $user, string $resource, int $currentCount): array
    {
        $tier = self::getUserTier($user);

        $limit = match ($resource) {
            'animals' => $tier?->max_animals ?? 5,
            'devices' => $tier?->max_devices ?? 5,
            'users' => $tier?->max_users ?? 1,
            default => 0,
        };

        if ($limit === 0) {
            return ['allowed' => true, 'limit' => 'unlimited'];
        }

        return [
            'allowed' => $currentCount < $limit,
            'current' => $currentCount,
            'limit' => $limit,
            'remaining' => max(0, $limit - $currentCount),
        ];
    }
}
