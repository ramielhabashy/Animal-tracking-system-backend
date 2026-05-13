<?php

namespace App\Http\Controllers\Api\Resources;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Http\Controllers\Traits\OwnableAuthorization;
use App\Models\AnimalGroup;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnimalGroupController extends Controller
{
    use OwnableAuthorization, ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = AnimalGroup::with(['owner']);
        $query = $this->filterByOwner($request, $query);

        $groups = $query->withCount('animals')->orderBy('created_at', 'desc')->get();

        return $this->success($groups->load('animals'));
    }

    public function store(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser || !$authUser->hasAnyRole(['Admin', 'Owner', 'Manager'])) {
            return $this->forbidden('Unauthorized to create animal groups');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'animal_ids' => 'nullable|array',
            'animal_ids.*' => 'exists:animals,id',
        ]);

        $ownerId = null;
        $role = $authUser->getPrimaryRoleName();

        if ($role === 'Owner') {
            $ownerId = $authUser->id;
        } elseif ($role === 'Manager') {
            $ownerId = $authUser->managed_by;
        } elseif ($role === 'Admin') {
            $ownerId = $request->input('owner_id');
        }

        $animalIds = $validated['animal_ids'] ?? [];
        if ($role === 'Admin' && $ownerId && !empty($animalIds)) {
            $animalIds = Animal::whereIn('id', $animalIds)
                ->where('owner_id', $ownerId)
                ->pluck('id')
                ->toArray();
        }

        $group = AnimalGroup::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? '#D4AF37',
            'owner_id' => $ownerId,
        ]);

        if (!empty($animalIds)) {
            $group->animals()->attach($animalIds);
        }

        return $this->created($group->load('animals'), 'Animal group created successfully');
    }

    public function show(Request $request, AnimalGroup $animalGroup): JsonResponse
    {
        if (!$this->canAccessOwner($request, $animalGroup->owner_id)) {
            return $this->forbidden('Unauthorized');
        }

        return $this->success($animalGroup->load(['animals.device', 'owner']));
    }

    public function update(Request $request, AnimalGroup $animalGroup): JsonResponse
    {
        $authUser = $request->user();

        if (!$this->canModifyOwner($request, $animalGroup->owner_id)) {
            return $this->forbidden('Unauthorized to modify animal group');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'animal_ids' => 'nullable|array',
            'animal_ids.*' => 'exists:animals,id',
            'owner_id' => 'nullable|exists:users,id',
        ]);

        $updateData = [
            'name' => $validated['name'] ?? $animalGroup->name,
            'description' => $validated['description'] ?? $animalGroup->description,
            'color' => $validated['color'] ?? $animalGroup->color,
        ];

        if ($authUser && $authUser->hasRole('Admin') && isset($validated['owner_id'])) {
            $updateData['owner_id'] = $validated['owner_id'];
        }

        $animalGroup->update($updateData);

        $animalIds = $validated['animal_ids'] ?? null;
        if ($animalIds !== null) {
            if ($authUser && $authUser->hasRole('Admin') && $animalGroup->owner_id) {
                $animalIds = Animal::whereIn('id', $animalIds)
                    ->where('owner_id', $animalGroup->owner_id)
                    ->pluck('id')
                    ->toArray();
            }
            $animalGroup->animals()->sync($animalIds);
        }

        return $this->updated($animalGroup->load('animals'), 'Animal group updated successfully');
    }

    public function destroy(Request $request, AnimalGroup $animalGroup): JsonResponse
    {
        if (!$this->canModifyOwner($request, $animalGroup->owner_id)) {
            return $this->forbidden('Unauthorized to delete animal group');
        }

        $animalGroup->delete();

        return $this->deleted('Animal group deleted successfully');
    }

    public function addAnimals(Request $request, AnimalGroup $animalGroup): JsonResponse
    {
        if (!$this->canAccessOwner($request, $animalGroup->owner_id)) {
            return $this->forbidden('Unauthorized');
        }

        $validated = $request->validate([
            'animal_ids' => 'required|array',
            'animal_ids.*' => 'exists:animals,id',
        ]);

        $authUser = $request->user();
        if ($authUser && $authUser->hasRole('Admin') && $animalGroup->owner_id) {
            $animalIds = Animal::whereIn('id', $validated['animal_ids'])
                ->where('owner_id', $animalGroup->owner_id)
                ->pluck('id')
                ->toArray();
            $animalGroup->animals()->syncWithoutDetaching($animalIds);
        } else {
            $animalGroup->animals()->syncWithoutDetaching($validated['animal_ids']);
        }

        return $this->success($animalGroup->load('animals'), 'Animals added to group');
    }

    public function removeAnimals(Request $request, AnimalGroup $animalGroup): JsonResponse
    {
        if (!$this->canAccessOwner($request, $animalGroup->owner_id)) {
            return $this->forbidden('Unauthorized');
        }

        $validated = $request->validate([
            'animal_ids' => 'required|array',
            'animal_ids.*' => 'exists:animals,id',
        ]);

        $animalGroup->animals()->detach($validated['animal_ids']);

        return $this->success($animalGroup->load('animals'), 'Animals removed from group');
    }

    public function availableAnimals(Request $request, AnimalGroup $animalGroup): JsonResponse
    {
        $query = Animal::with('device');

        $authUser = $request->user();
        if ($authUser) {
            $query = $this->filterByOwner($request, $query);
        }

        $assignedIds = $animalGroup->animals()->pluck('animals.id');
        $available = $query->whereNotIn('id', $assignedIds)->get();

        return $this->success($available);
    }
}
