<?php

namespace App\Http\Controllers\Api\Resources;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Http\Controllers\Traits\OwnableAuthorization;
use App\Models\Device;
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

        if ($authUser && !$authUser->can('manage_devices')) {
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

        $device = Device::create($data);

        return $this->created($device, 'Device created successfully');
    }

    public function update(Request $request, Device $device): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser && !$authUser->can('manage_devices')) {
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

        if ($authUser && !$authUser->can('manage_devices')) {
            return $this->forbidden('Unauthorized');
        }

        if (!$this->canAccessOwner($request, $device->owner_id)) {
            return $this->forbidden('Unauthorized to delete device');
        }

        $device->delete();

        return $this->deleted('Device deleted successfully');
    }
}
