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
use App\Models\Setting;
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

    protected function guardSimulator(Request $request, ?Device $device = null): bool
    {
        if ($request->user()?->getPrimaryRoleName() !== 'Admin') {
            return false;
        }

        if (!Setting::getBoolean('device_simulator_enabled', true)) {
            return false;
        }

        if ($device && $device->data_source !== 'simulated') {
            return false;
        }

        return true;
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
        if (!$this->guardSimulator($request)) {
            return response()->json(['error' => 'Simulator is disabled or forbidden'], 403);
        }

        $validated = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|between:0,360',
            'recorded_at' => 'nullable|date',
            'battery_drain' => 'nullable|numeric|min:0|max:100',
            'temperature' => 'nullable|numeric|min:20|max:45',
        ]);

        $device = Device::findOrFail($validated['device_id']);

        if (!$this->guardSimulator($request, $device)) {
            return response()->json(['error' => 'Cannot control real devices from simulator'], 403);
        }

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

        $updateData = [
            'gps_lat' => $validated['latitude'],
            'gps_lng' => $validated['longitude'],
            'battery_level' => $newBattery,
            'last_ping' => now(),
            'status' => $newBattery > 0 ? 'online' : 'offline',
        ];

        if (isset($validated['speed'])) {
            $updateData['speed'] = $validated['speed'];
        }

        if (isset($validated['temperature'])) {
            $updateData['temperature'] = $validated['temperature'];
            $updateData['last_temperature_update'] = now();
        }

        $device->update($updateData);

        $alert = $this->checkGeofences($animal, $validated['latitude'], $validated['longitude']);

        LocationHistory::where('animal_id', $animal->id)
            ->where('recorded_at', '<', Carbon::now()->subHours(5))
            ->delete();

        return response()->json([
            'message' => 'Location recorded',
            'alert_triggered' => $alert ? true : false,
            'alert_type' => $alert?->type,
            'battery_level' => $newBattery,
            'temperature' => $device->fresh()->temperature,
            'speed' => $device->fresh()->speed,
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
            'moves.*.temperature' => 'nullable|numeric|min:20|max:45',
        ]);

        if (!$this->guardSimulator($request)) {
            return response()->json(['error' => 'Simulator is disabled or forbidden'], 403);
        }

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

            if ($device->data_source !== 'simulated') {
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

            $updateData = [
                'gps_lat' => $move['latitude'],
                'gps_lng' => $move['longitude'],
                'battery_level' => $newBattery,
                'last_ping' => now(),
                'status' => $newBattery > 0 ? 'online' : 'offline',
            ];

            if (isset($move['speed'])) {
                $updateData['speed'] = $move['speed'];
            }

            if (isset($move['temperature'])) {
                $updateData['temperature'] = $move['temperature'];
                $updateData['last_temperature_update'] = now();
            }

            $device->update($updateData);

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

        if (!$this->guardSimulator($request)) {
            return response()->json(['error' => 'Simulator is disabled or forbidden'], 403);
        }

        $level = $validated['level'] ?? 100;
        $device = Device::findOrFail($validated['device_id']);

        if (!$this->guardSimulator($request, $device)) {
            return response()->json(['error' => 'Cannot control real devices from simulator'], 403);
        }

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
            'speed' => 'nullable|numeric|min:0',
            'temperature' => 'nullable|numeric|min:20|max:45',
        ]);

        if (!$this->guardSimulator($request)) {
            return response()->json(['error' => 'Simulator is disabled or forbidden'], 403);
        }

        $batteryDrain = $validated['battery_drain'] ?? 0;
        $results = [];

        foreach ($validated['device_ids'] as $deviceId) {
            $device = Device::find($deviceId);
            if (!$device) {
                $results[] = ['device_id' => $deviceId, 'success' => false, 'message' => 'Device not found'];
                continue;
            }

            if ($device->data_source !== 'simulated') {
                $results[] = ['device_id' => $deviceId, 'success' => false, 'message' => 'Cannot control real devices from simulator'];
                continue;
            }

            if (!$device->animal_id) {
                $results[] = ['device_id' => $deviceId, 'success' => false, 'message' => 'No animal assigned'];
                continue;
            }

            $animal = Animal::find($device->animal_id);
            $newBattery = max(0, ($device->battery_level ?? 100) - $batteryDrain);

            $updateData = [
                'gps_lat' => $validated['latitude'],
                'gps_lng' => $validated['longitude'],
                'battery_level' => $newBattery,
                'last_ping' => now(),
                'status' => $newBattery > 0 ? 'online' : 'offline',
            ];

            if (isset($validated['speed'])) {
                $updateData['speed'] = $validated['speed'];
            }

            if (isset($validated['temperature'])) {
                $updateData['temperature'] = $validated['temperature'];
                $updateData['last_temperature_update'] = now();
            }

            $device->update($updateData);

            LocationHistory::create([
                'device_id' => $device->id,
                'animal_id' => $animal->id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'speed' => $validated['speed'] ?? 0,
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

        if (!$this->guardSimulator($request)) {
            return response()->json(['error' => 'Simulator is disabled or forbidden'], 403);
        }

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

            $species = strtolower($animal->species ?? 'camel');
            $baseTemp = match ($species) {
                'camel' => 37.0 + (rand(-5, 5) / 10),
                'goat' => 38.5 + (rand(-5, 5) / 10),
                'sheep' => 38.5 + (rand(-5, 5) / 10),
                default => 38.0 + (rand(-5, 5) / 10),
            };

            $device = Device::create([
                'device_id' => 'DEMO-' . str_pad($animal->id, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5($animal->id . time()), 0, 1)),
                'name' => 'Demo ' . ($animal->name ?? $animal->animal_id),
                'type' => 'collar',
                'status' => 'online',
                'battery_level' => rand(60, 100),
                'temperature' => $baseTemp,
                'speed' => 0,
                'is_lost' => false,
                'firmware_version' => 'v4.2.1-stable',
                'update_interval' => 15,
                'gps_lat' => $lat + (rand(-100, 100) / 10000),
                'gps_lng' => $lng + (rand(-100, 100) / 10000),
                'last_ping' => now(),
                'last_temperature_update' => now(),
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

        if (!$this->guardSimulator($request)) {
            return response()->json(['error' => 'Simulator is disabled or forbidden'], 403);
        }

        $count = 0;
        foreach ($validated['device_ids'] as $id) {
            $device = Device::find($id);
            if (!$device || $device->data_source !== 'simulated') {
                continue;
            }
            LocationHistory::where('device_id', $device->id)->delete();
            $device->delete();
            $count++;
        }

        return response()->json([
            'message' => "Removed {$count} demo devices",
            'deleted' => $count,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'temperature' => 'nullable|numeric|min:20|max:45',
            'speed' => 'nullable|numeric|min:0|max:120',
            'is_lost' => 'nullable|boolean',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'signal_strength' => 'nullable|integer|min:0|max:100',
        ]);

        if (!$this->guardSimulator($request)) {
            return response()->json(['error' => 'Simulator is disabled or forbidden'], 403);
        }

        $device = Device::findOrFail($validated['device_id']);

        if (!$this->guardSimulator($request, $device)) {
            return response()->json(['error' => 'Cannot control real devices from simulator'], 403);
        }

        $updateData = [];

        if (isset($validated['temperature'])) {
            $updateData['temperature'] = $validated['temperature'];
            $updateData['last_temperature_update'] = now();
        }

        if (isset($validated['speed'])) {
            $updateData['speed'] = $validated['speed'];
        }

        if (isset($validated['is_lost'])) {
            $updateData['is_lost'] = $validated['is_lost'];
        }

        if (isset($validated['battery_level'])) {
            $updateData['battery_level'] = $validated['battery_level'];
            $updateData['status'] = $validated['battery_level'] > 0 ? 'online' : 'offline';
        }

        if (isset($validated['signal_strength'])) {
            $updateData['signal_strength'] = $validated['signal_strength'];
        }

        $device->update($updateData);

        return response()->json([
            'message' => 'Device updated',
            'device' => $device->fresh(),
        ]);
    }

    public function toggleLost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'is_lost' => 'required|boolean',
        ]);

        if (!$this->guardSimulator($request)) {
            return response()->json(['error' => 'Simulator is disabled or forbidden'], 403);
        }

        $device = Device::findOrFail($validated['device_id']);

        if (!$this->guardSimulator($request, $device)) {
            return response()->json(['error' => 'Cannot control real devices from simulator'], 403);
        }

        $device->update(['is_lost' => $validated['is_lost']]);

        if ($validated['is_lost']) {
            GeofenceAlert::create([
                'geofence_id' => null,
                'animal_id' => $device->animal_id,
                'device_id' => $device->id,
                'type' => 'lost',
                'latitude' => $device->gps_lat,
                'longitude' => $device->gps_lng,
                'is_acknowledged' => false,
                'triggered_at' => now(),
            ]);
        }

        return response()->json([
            'message' => $validated['is_lost'] ? 'Animal marked as lost' : 'Animal unmarked as lost',
            'is_lost' => $validated['is_lost'],
        ]);
    }

    public function setTemperature(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'temperature' => 'required|numeric|min:20|max:45',
        ]);

        if (!$this->guardSimulator($request)) {
            return response()->json(['error' => 'Simulator is disabled or forbidden'], 403);
        }

        $device = Device::findOrFail($validated['device_id']);

        if (!$this->guardSimulator($request, $device)) {
            return response()->json(['error' => 'Cannot control real devices from simulator'], 403);
        }

        $device->update([
            'temperature' => $validated['temperature'],
            'last_temperature_update' => now(),
        ]);

        return response()->json([
            'message' => 'Temperature set',
            'temperature' => (float) $validated['temperature'],
        ]);
    }

    public function batchSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_ids' => 'nullable|array',
            'device_ids.*' => 'exists:devices,id',
            'temperature' => 'nullable|numeric|min:20|max:45',
            'speed' => 'nullable|numeric|min:0|max:120',
            'is_lost' => 'nullable|boolean',
            'battery_level' => 'nullable|integer|min:0|max:100',
        ]);

        if (!$this->guardSimulator($request)) {
            return response()->json(['error' => 'Simulator is disabled or forbidden'], 403);
        }

        $query = Device::whereNotNull('animal_id')->where('data_source', 'simulated');
        if (!empty($validated['device_ids'])) {
            $query->whereIn('id', $validated['device_ids']);
        }

        $updateData = [];
        if (isset($validated['temperature'])) {
            $updateData['temperature'] = $validated['temperature'];
            $updateData['last_temperature_update'] = now();
        }
        if (isset($validated['speed'])) {
            $updateData['speed'] = $validated['speed'];
        }
        if (isset($validated['is_lost'])) {
            $updateData['is_lost'] = $validated['is_lost'];
        }
        if (isset($validated['battery_level'])) {
            $updateData['battery_level'] = $validated['battery_level'];
            $updateData['status'] = $validated['battery_level'] > 0 ? 'online' : 'offline';
        }

        $count = $query->update($updateData);

        return response()->json([
            'message' => "Updated {$count} devices",
            'count' => $count,
        ]);
    }
}
