<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Animal;
use App\Models\Geofence;
use App\Models\Task;
use App\Models\Device;

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
            $devices = Device::count();
        } else {
            $totalAnimals = Animal::where('owner_id', $ownerId)->count();
            $totalGeofences = Geofence::where('owner_id', $ownerId)->count();
            $pendingTasks = Task::where('owner_id', $ownerId)
                ->where('status', 'pending')
                ->count();
            $devices = Device::where('owner_id', $ownerId)->count();
        }

        return response()->json([
            'total_animals' => $totalAnimals,
            'active_alerts' => 0,
            'total_geofences' => $totalGeofences,
            'pending_tasks' => $pendingTasks,
            'healthy_count' => $totalAnimals,
            'total_devices' => $devices,
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