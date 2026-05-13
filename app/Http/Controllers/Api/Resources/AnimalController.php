<?php

namespace App\Http\Controllers\Api\Resources;

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
use App\Http\Controllers\Controller;

/**
 * Animal Management Controller
 * 
 * Handles CRUD operations for animals (sheep, cattle, etc.)
 * Uses OwnableAuthorization trait for role-based access control
 * 
 * Authorization logic (from OwnableAuthorization trait):
 * - Admin: Can see/manage all animals
 * - Owner: Can see/manage animals they own (owner_id = user id)
 * - Manager/Veterinarian: Can see animals of managed owners
 * - Shepherd: Can see animals assigned to them
 * 
 * Device assignment: Each animal can have one device (tracker)
 * Photo upload: Supports both file upload and base64 encoded images
 */
class AnimalController extends Controller
{
    use OwnableAuthorization;  // Adds filterByOwner, canAccessOwner, canModifyOwner methods

    /**
     * List all animals with role-based filtering
     * 
     * GET /api/animals
     * Query params: per_page (default 100)
     * 
     * @param Request $request
     * @return AnimalResource collection (paginated)
     */
    public function index(Request $request)
    {
        // Start query with related data
        $query = Animal::with(['owner','device', 'groups', 'geofences']);
        
        // Apply role-based filtering (from OwnableAuthorization trait)
        $query = $this->filterByOwner($request, $query);
        
        $query = $query->orderBy('created_at', 'desc');
        
        $perPage = $request->input('per_page', 100);
        $animals = $query->paginate($perPage);
        
        // Return using API Resource for consistent formatting
        return AnimalResource::collection($animals);
    }

    /**
     * Create new animal
     * 
     * POST /api/animals
     * Requires: manage_animals permission
     * 
     * Auto-generates animal_id in format: OA-YYYY-NNNN
     * 
     * @param StoreAnimalRequest $request Validated request (uses FormRequest)
     * @return JsonResponse Created animal or error
     */
    public function store(StoreAnimalRequest $request): JsonResponse
    {
        $authUser = $request->user();
        
        // Check permission using Sanctum or fallback to header
        if ($authUser) {
            if (!$authUser->can('animal_create')) {
                return response()->json(['message' => 'Unauthorized to create animals', 'error' => 'unauthorized'], 403);
            }
        } else {
            // Flutter mobile uses header-based auth
            $userRole = $request->header('X-User-Role');
            if (!in_array($userRole, ['Admin', 'Owner', 'Manager', 'Doctor'])) {
                return response()->json(['message' => 'Unauthorized to create animals', 'error' => 'unauthorized'], 403);
            }
        }
        
        $userId = $request->header('X-User-Id');
        $data = $request->validated();
        
        // Check if device is already assigned to another animal
        $deviceToAssign = null;
        if (!empty($data['device_id'])) {
            $deviceToAssign = Device::where('id', $data['device_id'])->orWhere('device_id', $data['device_id'])->first();
            if ($deviceToAssign && $deviceToAssign->animal_id) {
                return response()->json([
                    'message' => 'This device is already assigned to another animal',
                    'error' => 'device_already_assigned',
                    'errors' => ['device_id' => ['This device is already assigned to animal ' . ($deviceToAssign->animal->animal_id ?? $deviceToAssign->animal_id)]]
                ], 422);
            }
        }
        
        unset($data['device_id']);
        
        // Auto-generate unique animal ID (OA = Ovine Animal)
        $year = date('Y');
        $lastAnimal = Animal::where('animal_id', 'like', "OA-{$year}-%")
            ->orderByRaw('CAST(SUBSTRING(animal_id, -4) AS UNSIGNED) DESC')
            ->first();
        $nextNumber = $lastAnimal ? (int) substr($lastAnimal->animal_id, -4) + 1 : 1;
        $data['animal_id'] = 'OA-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        // Role-based owner assignment logic
        if ($authUser && $authUser->hasRole('Owner') && empty($data['owner_id'])) {
            $data['owner_id'] = $authUser->id;
        }
        
        if ($authUser && $authUser->hasRole('Manager')) {
            // Manager creating animal: set owner to their manager (managed_by)
            $user = $authUser ? User::find($userId) : null;
            if ($user && $user->managed_by) {
                $data['owner_id'] = $user->managed_by;
            }
        }
        
        if ($authUser && $authUser->hasRole('Admin') && empty($data['owner_id'])) {
            // Admin creating animal without owner: set to null (unassigned)
            $data['owner_id'] = null;
        }

        if ($authUser && $authUser->hasRole('Doctor')) {
            // Doctor creating animal: set owner to their managed_by owner
            if ($authUser->managed_by && empty($data['owner_id'])) {
                $data['owner_id'] = $authUser->managed_by;
            }
        }
        
        // Handle photo upload (file upload)
        if ($request->hasFile('identification_photo')) {
            $file = $request->file('identification_photo');
            $filename = 'animal_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/images', $filename, 'local');
            $data['identification_photo'] = '/storage/' . str_replace('public/', '', $path);
        } elseif ($request->has('identification_photo') && is_string($request->identification_photo) && strpos($request->identification_photo, 'data:image') === 0) {
            // Handle base64 encoded image (from mobile app or React)
            $base64Data = $request->identification_photo;
            $imageData = base64_decode(explode(',', $base64Data)[1] ?? $base64Data);
            $filename = 'animal_' . time() . '_' . uniqid() . '.png';
            $path = 'public/images/' . $filename;
            Storage::disk('local')->put($path, $imageData);
            $data['identification_photo'] = '/storage/images/' . $filename;
        }
        
        $animal = Animal::create($data);

        if ($deviceToAssign) {
            $deviceToAssign->update(['animal_id' => $animal->id]);
        }

        $animal->load(['owner', 'device']);

        return response()->json([
            'message' => 'Animal created successfully',
            'data' => new AnimalResource($animal),
        ], 201);
    }

    /**
     * Show single animal details
     * 
     * GET /api/animals/{id}
     * Requires: canAccessOwner permission check
     * 
     * @param Request $request
     * @param Animal $animal (route model binding)
     * @return AnimalResource|JsonResponse
     */
    public function show(Request $request, Animal $animal)
    {
        // Check if user can access this animal's owner
        if (!$this->canAccessOwner($request, $animal->owner_id)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        
        $animal->load(['owner','device']);
        return new AnimalResource($animal);
    }

    /**
     * Update animal
     * 
     * PUT /api/animals/{id}
     * Requires: manage_animals permission + canModifyOwner check
     * 
     * @param UpdateAnimalRequest $request Validated request
     * @param Animal $animal
     * @return JsonResponse Updated animal or error
     */
    public function update(UpdateAnimalRequest $request, Animal $animal): JsonResponse
    {
        $authUser = $request->user();
        
        // Check permission
        if ($authUser && !$authUser->can('animal_edit')) {
            return response()->json(['message' => 'Unauthorized to modify animal', 'error' => 'unauthorized'], 403);
        }
        
        // Check if user can modify this animal's owner
        if (!$this->canModifyOwner($request, $animal->owner_id)) {
            return response()->json(['message' => 'Unauthorized to modify animal', 'error' => 'unauthorized'], 403);
        }
        
        $data = $request->validated();
        
        // Handle device assignment changes
        if (isset($data['device_id'])) {
            if ($data['device_id']) {
                // Assigning a device - check if it's already assigned elsewhere
                $device = Device::where('id', $data['device_id'])->orWhere('device_id', $data['device_id'])->first();
                if ($device && $device->animal_id && $device->animal_id !== $animal->id) {
                    return response()->json([
                        'message' => 'This device is already assigned to another animal',
                        'error' => 'device_already_assigned',
                        'errors' => ['device_id' => ['This device is already assigned to animal ' . ($device->animal->animal_id ?? $device->animal_id)]]
                    ], 422);
                }
                // Update device's animal_id
                if ($device) {
                    $device->update(['animal_id' => $animal->id]);
                }
            } else {
                // Removing device (device_id = null or empty)
                Device::where('animal_id', $animal->id)->update(['animal_id' => null]);
            }
            unset($data['device_id']);  // Already handled above
        }
        
        // Handle photo update (file upload)
        if ($request->hasFile('identification_photo')) {
            // Delete old photo if exists
            if ($animal->identification_photo) {
                $oldPath = 'public/' . str_replace('/storage/', '', $animal->identification_photo);
                if (Storage::disk('local')->exists($oldPath)) {
                    Storage::disk('local')->delete($oldPath);
                }
            }
            
            $file = $request->file('identification_photo');
            $filename = 'animal_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/images', $filename, 'local');
            $data['identification_photo'] = '/storage/' . str_replace('public/', '', $path);
        } elseif ($request->has('identification_photo') && is_string($request->identification_photo) && strpos($request->identification_photo, 'data:image') === 0) {
            // Handle base64 image update
            if ($animal->identification_photo) {
                $oldPath = 'public/' . str_replace('/storage/', '', $animal->identification_photo);
                if (Storage::disk('local')->exists($oldPath)) {
                    Storage::disk('local')->delete($oldPath);
                }
            }
            
            $base64Data = $request->identification_photo;
            $imageData = base64_decode(explode(',', $base64Data)[1] ?? $base64Data);
            $filename = 'animal_' . time() . '_' . uniqid() . '.png';
            $path = 'public/images/' . $filename;
            Storage::disk('local')->put($path, $imageData);
            $data['identification_photo'] = '/storage/images/' . $filename;
        }
        
        $animal->update($data);
        $animal->load(['owner', 'device']);

        return response()->json([
            'message' => 'Animal updated successfully',
            'data' => new AnimalResource($animal),
        ]);
    }

    /**
     * Delete animal
     * 
     * DELETE /api/animals/{id}
     * Requires: manage_animals permission + canModifyOwner check
     * Also deletes photo from storage
     * 
     * @param Request $request
     * @param Animal $animal
     * @return JsonResponse Success message or error
     */
    public function destroy(Request $request, Animal $animal): JsonResponse
    {
        $authUser = $request->user();
        
        // Check permissions
        if ($authUser && !$authUser->can('animal_delete')) {
            return response()->json(['message' => 'Unauthorized to delete animal', 'error' => 'unauthorized'], 403);
        }
        
        if (!$this->canModifyOwner($request, $animal->owner_id)) {
            return response()->json(['message' => 'Unauthorized to delete animal', 'error' => 'unauthorized'], 403);
        }
        
        // Delete photo from storage
        if ($animal->identification_photo) {
            $oldPath = 'public/' . str_replace('/storage/', '', $animal->identification_photo);
            if (Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
        }
        
        $animal->delete();

        return response()->json(['message' => 'Animal deleted successfully']);
    }

    /**
     * Transfer animal ownership to another owner
     * 
     * POST /api/animals/{id}/transfer-ownership
     * Requires: canModifyOwner permission on current owner
     * 
     * New owner must be Admin or Owner role
     * 
     * @param Request $request Contains new_owner_id
     * @param Animal $animal
     * @return JsonResponse Updated animal or error
     */
    public function transferOwnership(Request $request, Animal $animal): JsonResponse
    {
        // Check permission on current owner
        if (!$this->canModifyOwner($request, $animal->owner_id)) {
            return response()->json(['message' => 'Unauthorized to transfer animal ownership', 'error' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'new_owner_id' => 'required|exists:users,id',
        ]);

        $newOwnerId = $validated['new_owner_id'];
        $newOwner = User::find($newOwnerId);

        if (!$newOwner) {
            return response()->json(['message' => 'New owner not found'], 404);
        }

        // Only allow transferring to Admin or Owner (can't transfer to Manager/Shepherd)
        if (!$newOwner->hasAnyRole(['Admin', 'Owner'])) {
            return response()->json(['message' => 'New owner must be an Admin or Owner'], 400);
        }

        // Update owner_id
        $animal->update(['owner_id' => $newOwnerId]);
        $animal->load(['owner', 'device']);

        return response()->json([
            'message' => 'Animal ownership transferred successfully',
            'data' => new AnimalResource($animal),
        ]);
    }
}
