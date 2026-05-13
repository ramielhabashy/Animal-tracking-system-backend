<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Animal;
use App\Models\LocationHistory;
use App\Models\Geofence;
use App\Models\GeofenceAlert;
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

        $device->update([
            'gps_lat' => $validated['latitude'],
            'gps_lng' => $validated['longitude'],
            'last_ping' => now(),
            'status' => 'online',
        ]);

        $alert = $this->checkGeofences($animal, $validated['latitude'], $validated['longitude']);

        LocationHistory::where('animal_id', $animal->id)
            ->where('recorded_at', '<', Carbon::now()->subHours(5))
            ->delete();

        return response()->json([
            'message' => 'Location recorded',
            'alert_triggered' => $alert ? true : false,
            'alert_type' => $alert?->type,
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

            $device->update([
                'gps_lat' => $move['latitude'],
                'gps_lng' => $move['longitude'],
                'last_ping' => now(),
                'status' => 'online',
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
}
