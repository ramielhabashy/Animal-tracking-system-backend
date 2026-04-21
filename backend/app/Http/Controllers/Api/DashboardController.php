<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Device;
use App\Models\User;
use App\Models\GeofenceAlert;
use App\Models\Auction;
use App\Models\SubscriptionTier;
use App\Models\UserSubscription;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->header('X-User-Id');
        $userRole = $request->header('X-User-Role');

        $isAdmin = $userRole === 'Admin';

        $query = Animal::query();
        if (!$isAdmin && $userRole === 'Owner' && $userId) {
            $query->where('owner_id', $userId);
        }
        $totalAnimals = $query->count();

        $deviceQuery = Device::query();
        if (!$isAdmin && $userRole === 'Owner' && $userId) {
            $deviceQuery->where('owner_id', $userId);
        }
        $totalDevices = $deviceQuery->count();
        
        $activeDeviceQuery = Device::query();
        if (!$isAdmin && $userRole === 'Owner' && $userId) {
            $activeDeviceQuery->where('owner_id', $userId);
        }
        $activeDevices = $activeDeviceQuery->where('status', 'online')->count();

        $alertQuery = GeofenceAlert::where('is_acknowledged', false);
        if (!$isAdmin && $userRole === 'Owner' && $userId) {
            $alertQuery->whereHas('geofence', function ($q) use ($userId) {
                $q->where('owner_id', $userId);
            });
        }
        $alertCount = $alertQuery->count();

        $auctionQuery = Auction::where('status', 'active');
        if (!$isAdmin && $userRole === 'Owner' && $userId) {
            $auctionQuery->where('owner_id', $userId);
        }
        $activeAuctions = $auctionQuery->count();

        $avgTemp = $query->whereNotNull('baseline_temperature')
            ->avg('baseline_temperature') ?? 38.5;

        $recentAlerts = GeofenceAlert::with(['animal', 'geofence'])
            ->orderBy('triggered_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->type,
                    'animal_id' => $alert->animal?->animal_id,
                    'geofence_name' => $alert->geofence?->name,
                    'time' => $alert->triggered_at?->diffForHumans(),
                ];
            });

        $subscriptionData = null;

        if ($userRole === 'Admin') {
            $tiers = SubscriptionTier::where('is_active', true)->get();
            $tierStats = [];
            foreach ($tiers as $tier) {
                $tierStats[] = [
                    'name' => $tier->name,
                    'slug' => $tier->slug,
                    'user_count' => User::where('subscription_tier_id', $tier->id)->count(),
                    'price_monthly' => $tier->price_monthly,
                ];
            }
            
            $pendingPayments = UserSubscription::where('status', 'pending_payment')->count();
            $activeSubscriptions = UserSubscription::where('status', 'active')->count();
            
            $subscriptionData = [
                'is_admin' => true,
                'tier_stats' => $tierStats,
                'pending_payments' => $pendingPayments,
                'active_subscriptions' => $activeSubscriptions,
            ];
        } elseif (in_array($userRole, ['Owner', 'Manager'])) {
            $user = User::find($userId);
            if ($user) {
                $userSubscription = UserSubscription::where('user_id', $userId)
                    ->where('status', 'active')
                    ->latest()
                    ->first();
                
                $subscriptionData = [
                    'is_admin' => false,
                    'tier_name' => $userSubscription?->tier?->name ?? 'Free',
                    'tier_slug' => $userSubscription?->tier?->slug ?? 'free',
                    'status' => $userSubscription?->status ?? 'inactive',
                    'ends_at' => $userSubscription?->ends_at?->toIso8601String(),
                    'trial_ends_at' => $userSubscription?->trial_ends_at?->toIso8601String(),
                    'is_on_trial' => $userSubscription?->isOnTrial() ?? false,
                ];
            }
        }

        return response()->json([
            'stats' => [
                'total_animals' => $totalAnimals,
                'total_devices' => $totalDevices,
                'active_devices' => $activeDevices,
                'critical_alerts' => $alertCount,
                'active_auctions' => $activeAuctions,
            ],
            'subscription' => $subscriptionData,
            'recent_alerts' => $recentAlerts,
            'health_trends' => [
                'vitality_index' => $totalAnimals > 0 ? round(85 + (($avgTemp >= 38 && $avgTemp <= 39) ? 10 : 0)) : 85,
                'avg_temperature' => round($avgTemp, 1),
            ],
        ]);
    }
}
