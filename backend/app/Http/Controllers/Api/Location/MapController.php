<?php

namespace App\Http\Controllers\Api\Location;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\AnimalGroup;
use App\Models\Device;
use App\Models\Geofence;
use App\Models\GeofenceAlert;
use App\Models\LocationHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user?->id;
        $userRole = $user?->getPrimaryRoleName();

        $animalIds = null;
        $ownerIds = null;

        if ($userRole !== 'Admin') {
            $ownerIds = match ($userRole) {
                'Owner' => [$userId],
                'Manager' => array_merge(
                    User::where('managed_by', $userId)->pluck('id')->toArray(),
                    [$userId]
                ),
                'Doctor', 'Shepherd' => $user?->managed_by ? [$user->managed_by] : [0],
                default => [$userId],
            };

            if ($userRole === 'Shepherd') {
                $assignedGroupIds = $user?->assignedGroups()->pluck('animal_groups.id') ?? collect();
                if ($assignedGroupIds->isNotEmpty()) {
                    $animalIds = Animal::whereIn('id', function ($q) use ($assignedGroupIds) {
                        $q->select('animal_id')
                            ->from('animal_group_member')
                            ->whereIn('animal_group_id', $assignedGroupIds);
                    })->pluck('id')->toArray();
                } else {
                    $animalIds = Animal::whereIn('owner_id', $ownerIds)->pluck('id')->toArray();
                }
            } else {
                $animalIds = Animal::whereIn('owner_id', $ownerIds)->pluck('id')->toArray();
            }
        }

        $deviceQuery = Device::whereNotNull('animal_id')
            ->with(['animal.owner', 'animal.geofences', 'animal.groups']);

        if ($animalIds !== null) {
            $deviceQuery->whereIn('animal_id', $animalIds);
        }

        $hours = (int) $request->input('hours', 48);

        $devices = $deviceQuery->get();
        $animalIds = $devices->pluck('animal_id')->filter()->unique()->toArray();
        $locationHistories = collect();
        if (!empty($animalIds)) {
            $locationHistories = LocationHistory::whereIn('animal_id', $animalIds)
                ->where('recorded_at', '>=', Carbon::now()->subHours($hours))
                ->orderBy('recorded_at', 'asc')
                ->get(['animal_id', 'latitude', 'longitude', 'speed', 'heading', 'recorded_at'])
                ->groupBy('animal_id');
        }

        $devices = $devices->map(function ($device) use ($locationHistories) {
            $animal = $device->animal;
            $locationHistory = $animal ? ($locationHistories[$animal->id] ?? collect()) : collect();

            $hasGps = !is_null($device->gps_lat) && !is_null($device->gps_lng);

            return [
                'id' => 'device-'.$device->id,
                'device_id' => $device->device_id,
                'name' => $device->name,
                'type' => $device->type,
                'status' => $device->status,
                'battery_level' => $device->battery_level,
                'signal_strength' => $device->signal_strength,
                'temperature' => $device->temperature ? (float) $device->temperature : null,
                'speed' => $device->speed ? (float) $device->speed : null,
                'is_lost' => $device->is_lost ?? false,
                'has_gps' => $hasGps,
                'gps_lat' => $hasGps ? (float) $device->gps_lat : null,
                'gps_lng' => $hasGps ? (float) $device->gps_lng : null,
                'last_ping' => $device->last_ping?->toISOString(),
                'animal' => $animal ? [
                    'id' => $animal->id,
                    'animal_id' => $animal->animal_id,
                    'name' => $animal->name,
                    'species' => $animal->species,
                    'breed' => $animal->breed,
                    'gender' => $animal->gender,
                    'date_of_birth' => $animal->date_of_birth?->format('Y-m-d'),
                    'color_markings' => $animal->color_markings,
                    'current_weight' => $animal->current_weight ? (float) $animal->current_weight : null,
                    'baseline_temperature' => $animal->baseline_temperature ? (float) $animal->baseline_temperature : null,
                    'normal_heart_rate' => $animal->normal_heart_rate,
                    'owner_id' => $animal->owner_id,
                    'owner_name' => $animal->owner?->name,
                    'geofence_ids' => $animal->geofences->pluck('id')->toArray(),
                    'groups' => $animal->groups->map(fn ($g) => [
                        'id' => $g->id,
                        'name' => $g->name,
                        'color' => $g->color,
                    ]),
                ] : null,
                'location_history' => $locationHistory->map(fn ($loc) => [
                    'latitude' => (float) $loc->latitude,
                    'longitude' => (float) $loc->longitude,
                    'speed' => $loc->speed,
                    'heading' => $loc->heading,
                    'recorded_at' => $loc->recorded_at?->toISOString(),
                ]),
                'last_temperature_update' => $device->last_temperature_update?->toISOString(),
            ];
        });

        $geofences = Geofence::withCount('animals')
            ->with('owner:id,name')
            ->when($animalIds !== null, function ($q) use ($userRole, $userId, $user) {
                $ownerIds = match ($userRole) {
                    'Doctor', 'Shepherd' => $user?->managed_by ? [$user->managed_by] : [0],
                    'Manager' => array_merge(
                        User::where('managed_by', $userId)->pluck('id')->toArray(),
                        [$userId]
                    ),
                    default => [$userId],
                };
                $q->whereIn('owner_id', $ownerIds);
            })
            ->get()
            ->map(fn ($geofence) => [
                'id' => $geofence->id,
                'name' => $geofence->name,
                'coordinates' => $geofence->coordinates,
                'color' => $geofence->color,
                'alert_type' => $geofence->alert_type,
                'is_active' => $geofence->is_active,
                'owner_id' => $geofence->owner_id,
                'owner_name' => $geofence->owner?->name,
                'animal_count' => $geofence->animals_count,
            ]);

        $alerts = GeofenceAlert::where('is_acknowledged', false)
            ->when($animalIds !== null, fn ($q) => $q->whereIn('animal_id', $animalIds))
            ->count();

        $groups = AnimalGroup::query()
            ->when($ownerIds !== null, fn ($q) => $q->whereIn('owner_id', $ownerIds))
            ->get(['id', 'name', 'color', 'owner_id']);

        $users = User::query()
            ->role('Owner')
            ->when($userRole !== 'Admin', function ($q) use ($userRole, $userId, $user) {
                if ($userRole === 'Owner') {
                    $q->where(function ($sq) use ($userId) {
                        $sq->where('managed_by', $userId)->orWhere('id', $userId);
                    });
                } elseif ($userRole === 'Manager' || $userRole === 'Doctor' || $userRole === 'Shepherd') {
                    if ($user?->managed_by) {
                        $q->where(function ($sq) use ($user) {
                            $sq->where('id', $user->managed_by)
                                ->orWhere('managed_by', $user->managed_by)
                                ->orWhere('id', $user->id);
                        });
                    } else {
                        $q->where('id', $user->id);
                    }
                } else {
                    $q->where('id', $userId);
                }
            })
            ->get(['id', 'name', 'email']);

        $ownerInfo = $userId ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'location' => $user->location,
            'animal_count' => $user->animals()->count(),
        ] : null;

        $devicesWithGps = $devices->filter(fn ($d) => $d['has_gps']);

        return response()->json([
            'markers' => $devices,
            'geofences' => $geofences,
            'alerts' => ['unacknowledged_count' => $alerts],
            'groups' => $groups,
            'users' => $users,
            'owner' => $ownerInfo,
            'bounds' => [
                'north' => $devicesWithGps->isNotEmpty() ? $devicesWithGps->max('gps_lat') : 25.0,
                'south' => $devicesWithGps->isNotEmpty() ? $devicesWithGps->min('gps_lat') : 24.0,
                'east' => $devicesWithGps->isNotEmpty() ? $devicesWithGps->max('gps_lng') : 56.0,
                'west' => $devicesWithGps->isNotEmpty() ? $devicesWithGps->min('gps_lng') : 51.0,
            ],
        ]);
    }

    public function filters(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user?->id;
        $userRole = $user?->getPrimaryRoleName();

        $ownerIds = null;
        if ($userRole !== 'Admin') {
            $ownerIds = match ($userRole) {
                'Owner' => [$userId],
                'Manager' => array_merge(
                    User::where('managed_by', $userId)->pluck('id')->toArray(),
                    [$userId]
                ),
                'Doctor', 'Shepherd' => $user?->managed_by ? [$user->managed_by] : [0],
                default => [$userId],
            };
        }

        $animalsQuery = Animal::query();
        if ($ownerIds !== null) {
            $animalsQuery->whereIn('owner_id', $ownerIds);
        }

        $species = (clone $animalsQuery)
            ->whereHas('species')
            ->with('species:id,name')
            ->get()
            ->pluck('species')
            ->unique('id')
            ->values();

        $statuses = ['online', 'offline', 'low_signal', 'healthy', 'warning', 'critical', 'lost'];

        return response()->json([
            'species' => $species,
            'statuses' => $statuses,
            'groups' => [],
        ]);
    }
}
