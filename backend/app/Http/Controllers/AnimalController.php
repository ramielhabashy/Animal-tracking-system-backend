<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\User;
use App\Models\Device;
use App\Http\Requests\StoreAnimalRequest;
use App\Http\Requests\UpdateAnimalRequest;
use App\Http\Resources\AnimalResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Traits\OwnableAuthorization;

class AnimalController extends Controller
{
    use OwnableAuthorization;

    public function index(Request $request)
    {
        $query = Animal::with(['owner','device', 'groups', 'geofences']);
        $query = $this->filterByOwner($request, $query);
        
        $perPage = $request->input('per_page', 100);
        $animals = $query->paginate($perPage);
        return AnimalResource::collection($animals);
    }

    public function store(StoreAnimalRequest $request): JsonResponse
    {
        $userId = $request->header('X-User-Id');
        $userRole = $request->header('X-User-Role');
        $authUser = $request->user();
        
        if ($authUser) {
            $userId = $authUser->id;
            $userRole = $authUser->role;
        }
        
        if (!in_array($userRole, ['Admin', 'Owner', 'Manager'])) {
            return response()->json(['message' => 'Unauthorized to create animals', 'error' => 'unauthorized'], 403);
        }
        
        $data = $request->validated();
        
        if (!empty($data['device_id'])) {
            $device = Device::find($data['device_id']);
            if ($device && $device->animal_id) {
                return response()->json([
                    'message' => 'This device is already assigned to another animal',
                    'error' => 'device_already_assigned',
                    'errors' => ['device_id' => ['This device is already assigned to animal ' . $device->animal->animal_id]]
                ], 422);
            }
        }
        
        $data['animal_id'] = 'OA-' . date('Y') . '-' . str_pad(Animal::count() + 1, 4, '0', STR_PAD_LEFT);
        
        if ($userRole === 'Owner' && $userId && empty($data['owner_id'])) {
            $data['owner_id'] = $userId;
        }
        
        if ($userRole === 'Manager') {
            $user = $authUser ?: User::find($userId);
            if ($user && $user->managed_by) {
                $data['owner_id'] = $user->managed_by;
            }
        }
        
        if ($userRole === 'Admin' && empty($data['owner_id'])) {
            $data['owner_id'] = null;
        }
        
        if ($request->hasFile('identification_photo')) {
            $file = $request->file('identification_photo');
            $filename = 'animal_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/images', $filename, 'local');
            $data['identification_photo'] = '/storage/' . str_replace('public/', '', $path);
        }
        
        $animal = Animal::create($data);
        $animal->load(['owner', 'device']);

        return response()->json([
            'message' => 'Animal created successfully',
            'data' => new AnimalResource($animal),
        ], 201);
    }

    public function show(Request $request, Animal $animal)
    {
        if (!$this->canAccessOwner($request, $animal->owner_id)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        
        $animal->load(['owner','device']);
        return new AnimalResource($animal);
    }

    public function update(UpdateAnimalRequest $request, Animal $animal): JsonResponse
    {
        if (!$this->canModifyOwner($request, $animal->owner_id)) {
            return response()->json(['message' => 'Unauthorized to modify animal', 'error' => 'unauthorized'], 403);
        }
        
        $data = $request->validated();
        
        if (isset($data['device_id'])) {
            if ($data['device_id']) {
                $device = Device::find($data['device_id']);
                if ($device && $device->animal_id && $device->animal_id !== $animal->id) {
                    return response()->json([
                        'message' => 'This device is already assigned to another animal',
                        'error' => 'device_already_assigned',
                        'errors' => ['device_id' => ['This device is already assigned to animal ' . $device->animal->animal_id]]
                    ], 422);
                }
                if ($device) {
                    $device->update(['animal_id' => $animal->id]);
                }
            } else {
                Device::where('animal_id', $animal->id)->update(['animal_id' => null]);
            }
            unset($data['device_id']);
        }
        
        if ($request->hasFile('identification_photo')) {
            if ($animal->identification_photo && Storage::disk('local')->exists(str_replace('/storage/', '', $animal->identification_photo))) {
                Storage::disk('local')->delete(str_replace('/storage/', '', $animal->identification_photo));
            }
            
            $file = $request->file('identification_photo');
            $filename = 'animal_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/images', $filename, 'local');
            $data['identification_photo'] = '/storage/' . str_replace('public/', '', $path);
        }
        
        $animal->update($data);
        $animal->load(['owner', 'device']);

        return response()->json([
            'message' => 'Animal updated successfully',
            'data' => new AnimalResource($animal),
        ]);
    }

    public function destroy(Request $request, Animal $animal): JsonResponse
    {
        if (!$this->canModifyOwner($request, $animal->owner_id)) {
            return response()->json(['message' => 'Unauthorized to delete animal', 'error' => 'unauthorized'], 403);
        }
        
        if ($animal->identification_photo && Storage::disk('local')->exists(str_replace('/storage/', '', $animal->identification_photo))) {
            Storage::disk('local')->delete(str_replace('/storage/', '', $animal->identification_photo));
        }
        
        $animal->delete();

        return response()->json(['message' => 'Animal deleted successfully']);
    }
}
