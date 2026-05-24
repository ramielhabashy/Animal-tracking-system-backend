<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Animal;
use App\Models\Geofence;
use App\Models\GeofenceAlert;
use App\Models\Task;
use App\Models\Device;
use App\Models\UserSubscription;
use App\Models\User;
use App\Models\Setting;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $ownerId = $this->resolveOwnerId($user);

        $realDataEnabled = Setting::getBoolean('device_real_data_enabled', false);

        $deviceQuery = Device::query();
        $animalQuery = Animal::query();

        if ($ownerId !== null) {
            $deviceQuery->where('owner_id', $ownerId);
            $animalQuery->where('owner_id', $ownerId);
        }

        if ($ownerId === null) {
            $totalAnimals = Animal::count();
            $totalGeofences = Geofence::count();
            $pendingTasks = Task::where('status', 'pending')->count();
            $devicesCount = Device::count();
        } else {
            $totalAnimals = Animal::where('owner_id', $ownerId)->count();
            $totalGeofences = Geofence::where('owner_id', $ownerId)->count();
            $pendingTasks = Task::where('owner_id', $ownerId)
                ->where('status', 'pending')
                ->count();
            $devicesCount = Device::where('owner_id', $ownerId)->count();
        }

        $activeAlerts = GeofenceAlert::when($ownerId, function ($q) use ($ownerId) {
            $q->whereHas('animal', function ($aq) use ($ownerId) {
                $aq->where('owner_id', $ownerId);
            });
        })->where('is_acknowledged', false)->count();

        $animalsWithDevice = Animal::when($ownerId, function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })->whereHas('device')->count();

        $totalDevicesReal = (clone $deviceQuery)->where('data_source', 'real')->count();
        $totalDevicesSimulated = (clone $deviceQuery)->where('data_source', 'simulated')->count();
        $healthyCountReal = Animal::when($ownerId, function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })->whereHas('device', fn ($q) => $q->where('data_source', 'real'))->count();
        $healthyCountSimulated = Animal::when($ownerId, function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })->whereHas('device', fn ($q) => $q->where('data_source', 'simulated'))->count();

        $subscription = null;
        if ($user->hasRole('Admin')) {
            $subscription = [
                'is_admin' => true,
                'active_subscriptions' => UserSubscription::where('status', 'active')->count(),
                'pending_payments' => UserSubscription::where('status', 'pending_payment')->count(),
            ];
        } elseif ($user->hasRole('Owner')) {
            $activeSub = UserSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('tier')
                ->latest()
                ->first();
            if ($activeSub) {
                $isOnTrial = $activeSub->trial_ends_at && now()->lessThan($activeSub->trial_ends_at);
                $subscription = [
                    'is_admin' => false,
                    'tier_name' => $activeSub->tier?->name ?? 'Free',
                    'is_on_trial' => $isOnTrial,
                    'trial_ends_at' => $activeSub->trial_ends_at?->toDateString(),
                ];
            } elseif ($user->subscriptionTier) {
                $subscription = [
                    'is_admin' => false,
                    'tier_name' => $user->subscriptionTier->name,
                    'is_on_trial' => false,
                    'trial_ends_at' => null,
                ];
            }
        }

        return response()->json([
            'total_animals' => $totalAnimals,
            'active_alerts' => $activeAlerts,
            'total_geofences' => $totalGeofences,
            'pending_tasks' => $pendingTasks,
            'healthy_count' => $animalsWithDevice,
            'total_devices' => $devicesCount,
            'total_devices_real' => $totalDevicesReal,
            'total_devices_simulated' => $totalDevicesSimulated,
            'healthy_count_real' => $healthyCountReal,
            'healthy_count_simulated' => $healthyCountSimulated,
            'real_data_enabled' => $realDataEnabled,
            'subscription' => $subscription,
        ]);
    }

    public function ownerStats(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $owners = User::whereHas('roles', function ($q) {
            $q->where('name', 'Owner');
        })->withCount(['animals', 'devices', 'shepherds'])
          ->with('subscriptionTier')
          ->orderBy('name')
          ->get()
          ->map(function ($owner) {
              $gracePeriodDays = (int) (Setting::get('subscription_grace_period_days') ?? 7);
              $latestSub = $owner->subscription()->latest()->first();
              $subStatus = $latestSub?->status;
              $subEndsAt = $latestSub?->ends_at?->toDateString();
              $daysRemaining = null;
              if ($latestSub?->ends_at) {
                  $daysRemaining = max(0, now()->diffInDays($latestSub->ends_at, false));
                  if ($subStatus === 'past_due') {
                      $daysRemaining += $gracePeriodDays;
                  }
              }
              return [
                  'id' => $owner->id,
                  'name' => $owner->name,
                  'email' => $owner->email,
                  'phone' => $owner->phone,
                  'is_active' => (bool) $owner->is_active,
                  'animals_count' => (int) $owner->animals_count,
                  'devices_count' => (int) $owner->devices_count,
                  'team_count' => (int) $owner->shepherds_count,
                  'tier_name' => $owner->subscriptionTier?->name ?? 'Free',
                  'tier_id' => $owner->subscription_tier_id,
                  'has_active_subscription' => $subStatus === 'active',
                  'subscription_status' => $subStatus,
                  'subscription_ends_at' => $subEndsAt,
                  'subscription_days_remaining' => $daysRemaining,
                  'has_pending_payment' => $subStatus === 'pending_payment',
              ];
          });

        return response()->json([
            'data' => $owners,
            'total_owners' => $owners->count(),
            'total_animals' => $owners->sum('animals_count'),
            'total_devices' => $owners->sum('devices_count'),
            'total_team' => $owners->sum('team_count'),
            'active_subscriptions' => $owners->filter(fn($o) => $o['has_active_subscription'])->count(),
            'pending_payments' => $owners->filter(fn($o) => $o['has_pending_payment'])->count(),
        ]);
    }

    private function resolveOwnerId($user): ?int
    {
        if ($user->hasRole('Admin')) {
            return null;
        }

        if ($user->hasRole('Owner')) {
            return $user->id;
        }

        if ($user->managed_by) {
            return $user->managed_by;
        }

        return null;
    }
}