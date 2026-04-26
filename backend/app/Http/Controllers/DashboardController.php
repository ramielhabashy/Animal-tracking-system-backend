<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Geofence;
use App\Models\Task;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $totalAnimals = Animal::where('owner_id', $user->id)->count();
        $activeAlerts = 0;
        $totalGeofences = Geofence::where('owner_id', $user->id)->count();
        $pendingTasks = Task::where('assigned_to', $user->id)
            ->where('status', 'pending')
            ->count();
        $healthyCount = Animal::where('owner_id', $user->id)
            ->where('health_status', 'healthy')
            ->count();
        $devices = Device::where('owner_id', $user->id)->count();
        
        return response()->json([
            'total_animals' => $totalAnimals,
            'active_alerts' => $activeAlerts,
            'total_geofences' => $totalGeofences,
            'pending_tasks' => $pendingTasks,
            'healthy_count' => $healthyCount,
            'total_devices' => $devices,
        ]);
    }
}