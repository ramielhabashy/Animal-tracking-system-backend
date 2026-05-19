<?php

namespace App\Http\Controllers\Api\Resources;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Http\Controllers\Traits\OwnableAuthorization;
use App\Models\Animal;
use App\Models\Device;
use App\Models\Geofence;
use App\Models\LocationHistory;
use App\Models\User;
use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    use OwnableAuthorization, ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Device::query()->with('owner');
        $query = $this->filterByOwner($request, $query);

        $perPage = $request->integer('per_page', 15);
        $devices = $query->paginate($perPage);

        return $this->paginated($devices);
    }

    public function show(Request $request, Device $device): JsonResponse
    {
        if (!$this->canAccessOwner($request, $device->owner_id)) {
            return $this->forbidden('Unauthorized');
        }

        return $this->success($device);
    }

    public function store(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser && !$authUser->can('device_create')) {
            return $this->forbidden('Unauthorized to create devices');
        }

        if (!$this->canCreateAsOwner($request)) {
            return $this->forbidden('Unauthorized to create devices');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:online,offline,low_signal',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'firmware_version' => 'nullable|string|max:50',
            'update_interval' => 'nullable|integer|min:1|max:1440',
            'advanced_tracking' => 'nullable|boolean',
            'last_seen' => 'nullable|date',
            'owner_id' => 'nullable|exists:users,id',
            'animal_id' => 'nullable|exists:animals,id',
        ]);

        $data['device_id'] = 'IOT-' . str_pad(Device::count() + 1, 3, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5(time()), 0, 1));

        $userRole = $this->getUserRole($request);
        $userId = $this->getUserId($request);

        if ($userRole === 'Admin' && empty($data['owner_id'])) {
            $data['owner_id'] = null;
        } elseif ($userRole === 'Owner' && $userId && empty($data['owner_id'])) {
            $data['owner_id'] = $userId;
        }

        if ($userRole === 'Manager') {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                $data['owner_id'] = $user->managed_by;
            }
        }

        if (empty($data['gps_lat']) || empty($data['gps_lng'])) {
            $coords = $this->resolveInitialCoords($data);
            $data['gps_lat'] = $coords[0];
            $data['gps_lng'] = $coords[1];
        }
        if (empty($data['last_ping'])) {
            $data['last_ping'] = now();
        }

        $device = Device::create($data);

        if (!empty($data['animal_id'])) {
            $this->initLocationHistory($device, $data['gps_lat'], $data['gps_lng']);
        }

        return $this->created($device, 'Device created successfully');
    }

    public function provision(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser && !$authUser->can('device_create')) {
            return $this->forbidden('Unauthorized to create devices');
        }

        if (!$this->canCreateAsOwner($request)) {
            return $this->forbidden('Unauthorized to create devices');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'owner_id' => 'nullable|exists:users,id',
            'animal_id' => 'required|exists:animals,id',
            'geofence_id' => 'nullable|exists:geofences,id',
        ]);

        $data['device_id'] = 'IOT-' . str_pad(Device::count() + 1, 3, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5(time()), 0, 1));
        $data['status'] = 'online';
        $data['battery_level'] = 100;
        $data['last_ping'] = now();

        $userRole = $this->getUserRole($request);
        $userId = $this->getUserId($request);

        if ($userRole === 'Admin' && empty($data['owner_id'])) {
            $data['owner_id'] = null;
        } elseif ($userRole === 'Owner' && $userId && empty($data['owner_id'])) {
            $data['owner_id'] = $userId;
        }

        if ($userRole === 'Manager') {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                $data['owner_id'] = $user->managed_by;
            }
        }

        $coords = $this->resolveProvisionCoords($data);
        $data['gps_lat'] = $coords[0];
        $data['gps_lng'] = $coords[1];

        $device = Device::create($data);

        $this->initLocationHistory($device, $data['gps_lat'], $data['gps_lng']);

        $device->load('animal');

        return $this->created($device, 'Device provisioned and assigned to animal');
    }

    public function batchStore(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser && !$authUser->can('device_create')) {
            return $this->forbidden('Unauthorized to create devices');
        }

        if (!$this->canCreateAsOwner($request)) {
            return $this->forbidden('Unauthorized to create devices');
        }

        $validated = $request->validate([
            'count' => 'required|integer|min:1|max:50',
            'owner_id' => 'nullable|exists:users,id',
            'assign_to_unassigned' => 'nullable|boolean',
        ]);

        $userRole = $this->getUserRole($request);
        $userId = $this->getUserId($request);
        $ownerId = $validated['owner_id'] ?? null;

        if ($userRole === 'Admin' && empty($ownerId)) {
            $ownerId = null;
        } elseif ($userRole === 'Owner' && $userId && empty($ownerId)) {
            $ownerId = $userId;
        } elseif ($userRole === 'Manager') {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                $ownerId = $user->managed_by;
            }
        }

        $unassignedAnimals = [];
        if (!empty($validated['assign_to_unassigned'])) {
            $unassignedAnimals = Animal::whereDoesntHave('device')
                ->when($ownerId, fn($q) => $q->where('owner_id', $ownerId))
                ->limit($validated['count'])
                ->get();
        }

        $coords = $this->resolveInitialCoords(['owner_id' => $ownerId]);
        $created = [];

        for ($i = 0; $i < $validated['count']; $i++) {
            $deviceData = [
                'device_id' => 'IOT-' . str_pad(Device::count() + $i + 1, 3, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5(time() + $i), 0, 1)),
                'name' => 'Batch Device ' . ($i + 1),
                'type' => 'collar',
                'status' => 'online',
                'battery_level' => rand(50, 100),
                'firmware_version' => 'v4.2.1-stable',
                'update_interval' => 15,
                'last_ping' => now(),
                'gps_lat' => $coords[0] + (rand(-100, 100) / 10000),
                'gps_lng' => $coords[1] + (rand(-100, 100) / 10000),
                'owner_id' => $ownerId,
            ];

            if ($i < count($unassignedAnimals)) {
                $deviceData['animal_id'] = $unassignedAnimals[$i]->id;
                $deviceData['name'] = 'Device for ' . ($unassignedAnimals[$i]->name ?? $unassignedAnimals[$i]->animal_id);
            }

            $device = Device::create($deviceData);

            if (!empty($deviceData['animal_id'])) {
                $this->initLocationHistory($device, $deviceData['gps_lat'], $deviceData['gps_lng']);
            }

            $created[] = $device;
        }

        return response()->json([
            'message' => "Created {$validated['count']} devices (" . count($unassignedAnimals) . ' assigned to animals)',
            'count' => $validated['count'],
            'assigned_count' => min(count($unassignedAnimals), $validated['count']),
            'data' => $created,
        ]);
    }

    protected function resolveInitialCoords(array $data): array
    {
        $owner = !empty($data['owner_id']) ? User::find($data['owner_id']) : null;
        if ($owner && $owner->location) {
            $coords = array_map('floatval', array_filter(explode(',', $owner->location), 'is_numeric'));
            if (count($coords) >= 2) {
                return [$coords[0], $coords[1]];
            }
        }

        if (!empty($data['animal_id'])) {
            $geofence = Geofence::where('is_active', true)
                ->whereHas('animals', fn($q) => $q->where('animals.id', $data['animal_id']))
                ->orWhere(function ($q) use ($data) {
                    $animal = Animal::find($data['animal_id']);
                    if ($animal) {
                        $q->where('owner_id', $animal->owner_id);
                    }
                })
                ->where('is_active', true)
                ->first();
            if ($geofence) {
                $center = $geofence->getCenter();
                if ($center) {
                    return [$center[0], $center[1]];
                }
            }
        }

        return [24.7136, 46.6753];
    }

    protected function resolveProvisionCoords(array $data): array
    {
        if (!empty($data['geofence_id'])) {
            $geofence = Geofence::find($data['geofence_id']);
            if ($geofence) {
                $center = $geofence->getCenter();
                if ($center) {
                    return [$center[0], $center[1]];
                }
            }
        }

        if (!empty($data['animal_id'])) {
            $animal = Animal::with('owner')->find($data['animal_id']);
            if ($animal && $animal->owner) {
                $geofence = Geofence::where('owner_id', $animal->owner_id)
                    ->where('is_active', true)
                    ->first();
                if ($geofence) {
                    $center = $geofence->getCenter();
                    if ($center) {
                        return [$center[0], $center[1]];
                    }
                }
            }
        }

        $owner = !empty($data['owner_id']) ? User::find($data['owner_id']) : null;
        if ($owner && $owner->location) {
            $coords = array_map('floatval', array_filter(explode(',', $owner->location), 'is_numeric'));
            if (count($coords) >= 2) {
                return [$coords[0], $coords[1]];
            }
        }

        return [24.7136, 46.6753];
    }

    protected function initLocationHistory(Device $device, float $lat, float $lng): void
    {
        $now = now();
        for ($i = 5; $i >= 0; $i--) {
            LocationHistory::create([
                'device_id' => $device->id,
                'animal_id' => $device->animal_id,
                'latitude' => $lat + (rand(-50, 50) / 10000),
                'longitude' => $lng + (rand(-50, 50) / 10000),
                'speed' => rand(1, 15),
                'heading' => rand(0, 360),
                'recorded_at' => (clone $now)->subMinutes($i * 5),
            ]);
        }
    }

    public function update(Request $request, Device $device): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser && !$authUser->can('device_edit')) {
            return $this->forbidden('Unauthorized');
        }

        if (!$this->canModifyOwner($request, $device->owner_id)) {
            return $this->forbidden('Unauthorized to modify device');
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:online,offline,low_signal',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'last_seen' => 'nullable|date',
            'update_interval' => 'nullable|integer|min:1|max:1440',
            'advanced_tracking' => 'nullable|boolean',
            'animal_id' => 'nullable|exists:animals,id',
            'owner_id' => 'nullable|exists:users,id',
        ]);

        $device->update($data);

        return $this->updated($device, 'Device updated successfully');
    }

    public function destroy(Request $request, Device $device): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser && !$authUser->can('device_delete')) {
            return $this->forbidden('Unauthorized to delete device');
        }

        if (!$this->canAccessOwner($request, $device->owner_id)) {
            return $this->forbidden('Unauthorized to delete device');
        }

        $device->delete();

        return $this->deleted('Device deleted successfully');
    }
}
