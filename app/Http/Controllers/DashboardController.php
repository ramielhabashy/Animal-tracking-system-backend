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

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $ownerId = $this->resolveOwnerId($user);

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
            }
        }

        return response()->json([
            'total_animals' => $totalAnimals,
            'active_alerts' => $activeAlerts,
            'total_geofences' => $totalGeofences,
            'pending_tasks' => $pendingTasks,
            'healthy_count' => $animalsWithDevice,
            'total_devices' => $devicesCount,
            'subscription' => $subscription,
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