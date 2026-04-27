<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'total_animals' => 0,
            'active_alerts' => 0,
            'total_geofences' => 0,
            'pending_tasks' => 0,
            'healthy_count' => 0,
            'total_devices' => 0,
        ]);
    }
}