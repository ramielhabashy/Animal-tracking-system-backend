<?php

namespace App\Http\Controllers\Api\Location;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Animal;
use App\Models\Geofence;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MapController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $request->header('X-User-Id') ?? $user?->id;
        $userRole = $request->header('X-User-Role') ?? $user?->getPrimaryRoleName();

        $animalIds = null;

        if ($userRole !== 'Admin') {
            if ($userRole === 'Owner') {
                $animalIds = Animal::where('owner_id', $userId)->pluck('id')->toArray();
            } elseif ($userRole === 'Manager') {
                $managedUsers = User::where('managed_by', $userId)->pluck('id')->toArray();
                $managedUsers[] = $userId;
                $animalIds = Animal::whereIn('owner_id', $managedUsers)->pluck('id')->toArray();
            } elseif ($userRole === 'Doctor' || $userRole === 'Shepherd') {
                $u = User::find($userId);
                if ($u && $u->managed_by) {
                    $animalIds = Animal::where('owner_id', $u->managed_by)->pluck('id')->toArray();
                } else {
                    $animalIds = [0];
                }
            } else {
                $animalIds = Animal::where('owner_id', $userId)->pluck('id')->toArray();
            }
        }

        $deviceQuery = Device::whereNotNull('gps_lat')
            ->whereNotNull('gps_lng')
            ->with(['animal.owner', 'animal.locationHistory', 'animal.geofences']);

        if ($animalIds !== null) {
            $deviceQuery->whereIn('animal_id', $animalIds);
        }

        $devices = $deviceQuery->get()->map(function ($device) {
            $animal = $device->animal;
            $loc = $animal?->locationHistory;
            return [
                'id' => 'device-' . $device->id,
                'device_id' => $device->device_id,
                'name' => $device->name,
                'type' => $device->type,
                'status' => $device->status,
                'battery_level' => $device->battery_level,
                'signal_strength' => $device->signal_strength,
                'gps_lat' => (float) $device->gps_lat,
                'gps_lng' => (float) $device->gps_lng,
                'last_ping' => $device->last_ping?->toISOString(),
                'animal' => $animal ? [
                    'id' => $animal->id,
                    'animal_id' => $animal->animal_id,
                    'name' => $animal->name,
                    'species' => $animal->species,
                    'breed' => $animal->breed,
                    'gender' => $animal->gender,
                    'current_weight' => $animal->current_weight,
                    'baseline_temperature' => $animal->baseline_temperature,
                    'owner_id' => $animal->owner_id,
                    'owner_name' => $animal->owner?->name,
                    'geofence_ids' => $animal->geofences->pluck('id')->toArray(),
                ] : null,
                'location_history' => $loc ? [
                    'latitude' => (float) $loc->latitude,
                    'longitude' => (float) $loc->longitude,
                    'speed' => $loc->speed,
                    'heading' => $loc->heading,
                    'recorded_at' => $loc->recorded_at?->toISOString(),
                ] : null,
            ];
        });

        $geofenceQuery = Geofence::with(['animals', 'owner']);

        if ($animalIds !== null) {
            $u = User::find($userId);
            $ownerIds = [$userId];
            if (in_array($userRole, ['Doctor', 'Shepherd']) && $u && $u->managed_by) {
                $ownerIds = [$u->managed_by];
            } elseif ($userRole === 'Manager') {
                $managedUsers = User::where('managed_by', $userId)->pluck('id')->toArray();
                $managedUsers[] = $userId;
                $ownerIds = $managedUsers;
            }
            $geofenceQuery->whereIn('owner_id', $ownerIds);
        }

        $geofences = $geofenceQuery->get()->map(function ($geofence) {
            return [
                'id' => $geofence->id,
                'name' => $geofence->name,
                'coordinates' => $geofence->coordinates,
                'color' => $geofence->color,
                'alert_type' => $geofence->alert_type,
                'is_active' => $geofence->is_active,
                'owner_id' => $geofence->owner_id,
                'owner_name' => $geofence->owner?->name,
                'animal_count' => $geofence->animals->count(),
            ];
        });

        $ownerInfo = null;
        if ($userId) {
            $ownerUser = User::with('animals')->find($userId);
            if ($ownerUser) {
                $ownerInfo = [
                    'id' => $ownerUser->id,
                    'name' => $ownerUser->name,
                    'email' => $ownerUser->email,
                    'location' => $ownerUser->location,
                    'animal_count' => $ownerUser->animals->count(),
                ];
            }
        }

        return response()->json([
            'markers' => $devices,
            'geofences' => $geofences,
            'owner' => $ownerInfo,
            'bounds' => [
                'north' => $devices->isNotEmpty() ? $devices->max('gps_lat') : 25.0,
                'south' => $devices->isNotEmpty() ? $devices->min('gps_lat') : 24.0,
                'east' => $devices->isNotEmpty() ? $devices->max('gps_lng') : 56.0,
                'west' => $devices->isNotEmpty() ? $devices->min('gps_lng') : 51.0,
            ],
        ]);
    }
}
