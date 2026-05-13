<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Animal;
use App\Models\LocationHistory;
use App\Models\Geofence;
use App\Models\GeofenceAlert;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SimulatorController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function devices(): JsonResponse
    {
        $devices = Device::with(['animal' => function ($q) {
            $q->select('id', 'animal_id', 'name', 'species', 'owner_id');
        }])->whereNotNull('animal_id')->get();

        return response()->json(['data' => $devices]);
    }

    public function move(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|between:0,360',
            'recorded_at' => 'nullable|date',
            'battery_drain' => 'nullable|numeric|min:0|max:100',
        ]);

        $device = Device::findOrFail($validated['device_id']);

        if (!$device->animal_id) {
            return response()->json(['message' => 'No animal assigned to this device'], 422);
        }

        $animal = Animal::find($device->animal_id);

        LocationHistory::create([
            'device_id' => $validated['device_id'],
            'animal_id' => $animal->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'speed' => $validated['speed'] ?? null,
            'heading' => $validated['heading'] ?? null,
            'recorded_at' => $validated['recorded_at'] ?? now(),
        ]);

        $batteryDrain = $validated['battery_drain'] ?? 0;
        $newBattery = max(0, ($device->battery_level ?? 100) - $batteryDrain);

        $device->update([
            'gps_lat' => $validated['latitude'],
            'gps_lng' => $validated['longitude'],
            'battery_level' => $newBattery,
            'last_ping' => now(),
            'status' => $newBattery > 0 ? 'online' : 'offline',
        ]);

        $alert = $this->checkGeofences($animal, $validated['latitude'], $validated['longitude']);

        LocationHistory::where('animal_id', $animal->id)
            ->where('recorded_at', '<', Carbon::now()->subHours(5))
            ->delete();

        return response()->json([
            'message' => 'Location recorded',
            'alert_triggered' => $alert ? true : false,
            'alert_type' => $alert?->type,
            'battery_level' => $newBattery,
        ]);
    }

    public function batch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'moves' => 'required|array|max:50',
            'moves.*.device_id' => 'required|exists:devices,id',
            'moves.*.latitude' => 'required|numeric|between:-90,90',
            'moves.*.longitude' => 'required|numeric|between:-180,180',
            'moves.*.speed' => 'nullable|numeric|min:0',
            'moves.*.heading' => 'nullable|numeric|between:0,360',
            'moves.*.battery_drain' => 'nullable|numeric|min:0|max:100',
        ]);

        $results = [];

        foreach ($validated['moves'] as $move) {
            $device = Device::find($move['device_id']);
            if (!$device || !$device->animal_id) {
                $results[] = [
                    'device_id' => $move['device_id'],
                    'success' => false,
                    'message' => 'No animal assigned',
                ];
                continue;
            }

            $animal = Animal::find($device->animal_id);

            LocationHistory::create([
                'device_id' => $move['device_id'],
                'animal_id' => $animal->id,
                'latitude' => $move['latitude'],
                'longitude' => $move['longitude'],
                'speed' => $move['speed'] ?? null,
                'heading' => $move['heading'] ?? null,
                'recorded_at' => $move['recorded_at'] ?? now(),
            ]);

            $batteryDrain = $move['battery_drain'] ?? 0;
            $newBattery = max(0, ($device->battery_level ?? 100) - $batteryDrain);

            $device->update([
                'gps_lat' => $move['latitude'],
                'gps_lng' => $move['longitude'],
                'battery_level' => $newBattery,
                'last_ping' => now(),
                'status' => $newBattery > 0 ? 'online' : 'offline',
            ]);

            $alert = $this->checkGeofences($animal, $move['latitude'], $move['longitude']);

            LocationHistory::where('animal_id', $animal->id)
                ->where('recorded_at', '<', Carbon::now()->subHours(5))
                ->delete();

            $results[] = [
                'device_id' => $move['device_id'],
                'success' => true,
                'alert_triggered' => $alert ? true : false,
                'alert_type' => $alert?->type,
                'battery_level' => $newBattery,
            ];
        }

        return response()->json(['results' => $results]);
    }

    protected function checkGeofences(Animal $animal, float $lat, float $lng): ?GeofenceAlert
    {
        $geofences = Geofence::where('is_active', true)->get();

        if ($animal->owner_id) {
            $geofences = $geofences->filter(function ($geofence) use ($animal) {
                return $geofence->owner_id === $animal->owner_id || $geofence->owner_id === null;
            });
        } else {
            $geofences = $geofences->filter(fn($g) => $g->owner_id === null);
        }

        $newAlert = null;

        foreach ($geofences as $geofence) {
            $isInside = $geofence->containsPoint($lat, $lng);
            $wasInsideKey = "geofence_{$geofence->id}_animal_{$animal->id}_inside";

            $wasInside = cache()->get($wasInsideKey, null);
            if ($wasInside === null) {
                $wasInside = $isInside;
                cache()->put($wasInsideKey, $isInside, 86400);
                continue;
            }

            if ($isInside && !$wasInside) {
                if (in_array($geofence->alert_type, ['entry', 'both'])) {
                    $alert = GeofenceAlert::create([
                        'geofence_id' => $geofence->id,
                        'animal_id' => $animal->id,
                        'type' => 'entry',
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'triggered_at' => now(),
                    ]);

                    $this->notificationService->sendGeofenceAlert($alert);
                    $newAlert = $alert;
                }
                cache()->put($wasInsideKey, true, 86400);
            } elseif (!$isInside && $wasInside) {
                if (in_array($geofence->alert_type, ['exit', 'both'])) {
                    $alert = GeofenceAlert::create([
                        'geofence_id' => $geofence->id,
                        'animal_id' => $animal->id,
                        'type' => 'exit',
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'triggered_at' => now(),
                    ]);

                    $this->notificationService->sendGeofenceAlert($alert);
                    $newAlert = $alert;
                }
                cache()->put($wasInsideKey, false, 86400);
            }
        }

        return $newAlert;
    }

    public function recharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'level' => 'nullable|numeric|min:0|max:100',
        ]);

        $level = $validated['level'] ?? 100;
        $device = Device::findOrFail($validated['device_id']);

        $device->update([
            'battery_level' => $level,
            'status' => $level > 0 ? 'online' : 'offline',
        ]);

        return response()->json([
            'message' => "Battery set to {$level}%",
            'battery_level' => $level,
        ]);
    }

    public function teleport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_ids' => 'required|array',
            'device_ids.*' => 'exists:devices,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'battery_drain' => 'nullable|numeric|min:0|max:100',
        ]);

        $batteryDrain = $validated['battery_drain'] ?? 0;
        $results = [];

        foreach ($validated['device_ids'] as $deviceId) {
            $device = Device::find($deviceId);
            if (!$device || !$device->animal_id) {
                $results[] = ['device_id' => $deviceId, 'success' => false, 'message' => 'No animal assigned'];
                continue;
            }

            $animal = Animal::find($device->animal_id);
            $newBattery = max(0, ($device->battery_level ?? 100) - $batteryDrain);

            $device->update([
                'gps_lat' => $validated['latitude'],
                'gps_lng' => $validated['longitude'],
                'battery_level' => $newBattery,
                'last_ping' => now(),
                'status' => $newBattery > 0 ? 'online' : 'offline',
            ]);

            LocationHistory::create([
                'device_id' => $device->id,
                'animal_id' => $animal->id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'speed' => 0,
                'heading' => 0,
                'recorded_at' => now(),
            ]);

            $alert = $this->checkGeofences($animal, $validated['latitude'], $validated['longitude']);

            $results[] = [
                'device_id' => $deviceId,
                'success' => true,
                'alert_triggered' => $alert ? true : false,
                'alert_type' => $alert?->type,
                'battery_level' => $newBattery,
            ];
        }

        return response()->json(['results' => $results]);
    }

    public function demoSeed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'owner_id' => 'nullable|exists:users,id',
        ]);

        $ownerId = $validated['owner_id'] ?? null;

        $unassigned = Animal::whereDoesntHave('device')
            ->when($ownerId, fn($q) => $q->where('owner_id', $ownerId))
            ->limit(10)
            ->get();

        if ($unassigned->isEmpty()) {
            return response()->json(['message' => 'No unassigned animals found', 'count' => 0, 'device_ids' => []]);
        }

        $createdIds = [];
        foreach ($unassigned as $animal) {
            $geofence = Geofence::where('owner_id', $animal->owner_id)
                ->where('is_active', true)
                ->first();
            $lat = 24.7136;
            $lng = 46.6753;
            if ($geofence) {
                $center = $geofence->getCenter();
                if ($center) {
                    $lat = $center[0];
                    $lng = $center[1];
                }
            }

            $device = Device::create([
                'device_id' => 'DEMO-' . str_pad($animal->id, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5($animal->id . time()), 0, 1)),
                'name' => 'Demo ' . ($animal->name ?? $animal->animal_id),
                'type' => 'collar',
                'status' => 'online',
                'battery_level' => rand(60, 100),
                'firmware_version' => 'v4.2.1-stable',
                'update_interval' => 15,
                'gps_lat' => $lat + (rand(-100, 100) / 10000),
                'gps_lng' => $lng + (rand(-100, 100) / 10000),
                'last_ping' => now(),
                'animal_id' => $animal->id,
                'owner_id' => $animal->owner_id,
            ]);

            $now = now();
            for ($i = 5; $i >= 0; $i--) {
                LocationHistory::create([
                    'device_id' => $device->id,
                    'animal_id' => $animal->id,
                    'latitude' => $device->gps_lat + (rand(-50, 50) / 10000),
                    'longitude' => $device->gps_lng + (rand(-50, 50) / 10000),
                    'speed' => rand(1, 15),
                    'heading' => rand(0, 360),
                    'recorded_at' => (clone $now)->subMinutes($i * 5),
                ]);
            }

            $createdIds[] = $device->id;
        }

        return response()->json([
            'message' => 'Demo mode activated with ' . count($createdIds) . ' devices',
            'count' => count($createdIds),
            'device_ids' => $createdIds,
        ]);
    }

    public function demoReset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_ids' => 'required|array',
            'device_ids.*' => 'exists:devices,id',
        ]);

        $count = 0;
        foreach ($validated['device_ids'] as $id) {
            $device = Device::find($id);
            if ($device) {
                LocationHistory::where('device_id', $device->id)->delete();
                $device->delete();
                $count++;
            }
        }

        return response()->json([
            'message' => "Removed {$count} demo devices",
            'deleted' => $count,
        ]);
    }
}
