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
        $userId = $user->id;

        $totalAnimals = Animal::where('owner_id', $userId)->count();
        $totalGeofences = Geofence::where('owner_id', $userId)->count();
        $pendingTasks = Task::where('assigned_to', $userId)
            ->where('status', 'pending')
            ->count();
        $devices = Device::where('owner_id', $userId)->count();

        return response()->json([
            'total_animals' => $totalAnimals,
            'active_alerts' => 0,
            'total_geofences' => $totalGeofences,
            'pending_tasks' => $pendingTasks,
            'healthy_count' => $totalAnimals,
            'total_devices' => $devices,
        ]);
    }
}