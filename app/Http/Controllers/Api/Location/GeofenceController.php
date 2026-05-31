<?php

namespace App\Http\Controllers\Api\Location;

use App\Models\Geofence;
use App\Models\GeofenceAlert;
use App\Models\User;
use App\Http\Resources\GeofenceAlertResource;
use App\Models\Animal;
use App\Models\AnimalGroup;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Traits\OwnableAuthorization;
use App\Http\Controllers\Controller;

class GeofenceController extends Controller
{
    use OwnableAuthorization;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    private function filterAlertsByRole(Request $request, $query)
    {
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);
        
        if ($userRole === 'Admin') {
            return $query;
        }
        
        if ($userRole === 'Owner') {
            return $query->where(function ($q) use ($userId) {
                $q->whereHas('geofence', function ($q2) use ($userId) {
                    $q2->where('owner_id', $userId);
                })->orWhere(function ($q3) use ($userId) {
                    $q3->whereNull('geofence_id')
                       ->whereHas('animal', function ($q4) use ($userId) {
                           $q4->where('owner_id', $userId);
                       });
                });
            });
        }
        
        if (in_array($userRole, ['Manager', 'Shepherd'])) {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                return $query->where(function ($q) use ($user) {
                    $q->whereHas('geofence', function ($q2) use ($user) {
                        $q2->where('owner_id', $user->managed_by);
                    })->orWhere(function ($q3) use ($user) {
                        $q3->whereNull('geofence_id')
                           ->whereHas('animal', function ($q4) use ($user) {
                               $q4->where('owner_id', $user->managed_by);
                           });
                    });
                });
            }
            return $query;
        }
        
        if ($userRole === 'Doctor') {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                return $query->where(function ($q) use ($user) {
                    $q->whereHas('geofence', function ($q2) use ($user) {
                        $q2->where('owner_id', $user->managed_by);
                    })->orWhere(function ($q3) use ($user) {
                        $q3->whereNull('geofence_id')
                           ->whereHas('animal', function ($q4) use ($user) {
                               $q4->where('owner_id', $user->managed_by);
                           });
                    });
                });
            }
            return $query->whereRaw('1 = 0');
        }
        
        return $query->whereRaw('1 = 0');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Geofence::with(['owner', 'animals', 'groups']);
        $query = $this->filterByOwner($request, $query);
        
        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }
        
        $geofences = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json(['data' => $geofences]);
    }

    public function store(Request $request): JsonResponse
    {
        $authUser = $request->user();
        
        if ($authUser && !$authUser->can('geofence_create')) {
            return response()->json(['message' => 'Unauthorized to create geofences', 'error' => 'unauthorized'], 403);
        }
        
        if (!$this->canCreateAsOwner($request)) {
            return response()->json(['message' => 'Unauthorized to create geofences', 'error' => 'unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'coordinates' => 'required|string',
            'color' => 'nullable|string|max:7',
            'alert_type' => 'nullable|in:entry,exit,both',
        ]);

        $validated['coordinates'] = json_decode($validated['coordinates'], true);
        
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);
        
        if ($userRole === 'Owner') {
            $validated['owner_id'] = $userId;
        } elseif ($userRole === 'Manager') {
            $user = $this->getUser($request);
            $validated['owner_id'] = $user->managed_by ?? $userId;
        } else {
            $validated['owner_id'] = $request->input('owner_id');
        }
        
        $validated['color'] = $validated['color'] ?? '#D4AF37';
        $validated['alert_type'] = $validated['alert_type'] ?? 'both';
        
        $geofence = Geofence::create($validated);

        return response()->json([
            'message' => 'Geofence created successfully',
            'geofence' => $geofence
        ], 201);
    }

    public function show(Request $request, Geofence $geofence): JsonResponse
    {
        if (!$this->canAccessOwner($request, $geofence->owner_id)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        
        return response()->json($geofence);
    }

    public function update(Request $request, Geofence $geofence): JsonResponse
    {
        $authUser = $request->user();
        
        if ($authUser && !$authUser->can('geofence_edit')) {
            return response()->json(['message' => 'Unauthorized to modify geofence', 'error' => 'unauthorized'], 403);
        }
        
        if (!$this->canModifyOwner($request, $geofence->owner_id)) {
            return response()->json(['message' => 'Unauthorized to modify geofence', 'error' => 'unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'coordinates' => 'sometimes|string',
            'color' => 'nullable|string|max:7',
            'alert_type' => 'nullable|in:entry,exit,both',
            'is_active' => 'nullable|boolean',
        ]);

        if (isset($validated['coordinates'])) {
            $validated['coordinates'] = json_decode($validated['coordinates'], true);
        }
        
        $geofence->update($validated);

        return response()->json([
            'message' => 'Geofence updated successfully',
            'geofence' => $geofence
        ]);
    }

    public function destroy(Request $request, Geofence $geofence): JsonResponse
    {
        $authUser = $request->user();
        
        if ($authUser && !$authUser->can('geofence_delete')) {
            return response()->json(['message' => 'Unauthorized to delete geofence', 'error' => 'unauthorized'], 403);
        }
        
        if (!$this->canModifyOwner($request, $geofence->owner_id)) {
            return response()->json(['message' => 'Unauthorized to delete geofence', 'error' => 'unauthorized'], 403);
        }
        
        $geofence->delete();
        return response()->json(['message' => 'Geofence deleted successfully']);
    }

    public function temperatureAlerts(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 50);
        $page = $request->integer('page', 1);

        $query = GeofenceAlert::with(['animal', 'geofence'])
            ->where('type', 'temperature')
            ->orderBy('triggered_at', 'desc');

        if ($request->has('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->has('is_acknowledged')) {
            $query->where('is_acknowledged', $request->boolean('is_acknowledged'));
        }

        if ($request->has('owner_id')) {
            $query->whereHas('animal', function ($q) use ($request) {
                $q->where('owner_id', $request->input('owner_id'));
            });
        }

        if ($request->has('animal_id')) {
            $query->where('animal_id', $request->input('animal_id'));
        }

        $query = $this->filterAlertsByRole($request, $query);

        $total = $query->count();
        $alerts = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $unresolvedCount = GeofenceAlert::where('type', 'temperature')
            ->where('is_acknowledged', false)
            ->count();

        return response()->json([
            'data' => GeofenceAlertResource::collection($alerts),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'unresolved_count' => $unresolvedCount,
        ]);
    }

    public function alerts(Request $request)
    {
        $perPage = $request->integer('per_page', 50);
        $page = $request->integer('page', 1);
        
        $query = GeofenceAlert::with(['geofence', 'animal'])
            ->orderBy('triggered_at', 'desc');
        
        if ($request->has('is_acknowledged')) {
            $query->where('is_acknowledged', $request->boolean('is_acknowledged'));
        }

        if ($request->has('type')) {
            $types = explode(',', $request->input('type'));
            $query->whereIn('type', $types);
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->has('owner_id')) {
            $query->whereHas('geofence', function ($q) use ($request) {
                $q->where('owner_id', $request->input('owner_id'));
            });
        }

        if ($request->has('animal_id')) {
            $query->where('animal_id', $request->input('animal_id'));
        }

        if ($request->has('group_id')) {
            $query->whereHas('animal', function ($q) use ($request) {
                $q->whereHas('groups', function ($q2) use ($request) {
                    $q2->where('animal_groups.id', $request->input('group_id'));
                });
            });
        }

        if ($request->has('animal_name') && $request->input('animal_name') !== '') {
            $search = $request->input('animal_name');
            $query->whereHas('animal', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $query = $this->filterAlertsByRole($request, $query);

        $total = $query->count();
        $alerts = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $unresolvedQuery = GeofenceAlert::where('is_acknowledged', false);
        $unresolvedQuery = $this->filterAlertsByRole($request, $unresolvedQuery);
        $unresolvedCount = $unresolvedQuery->count();
        
        return response()->json([
            'data' => GeofenceAlertResource::collection($alerts),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'unresolved_count' => $unresolvedCount,
        ]);
    }

    public function acknowledgeAlert(Request $request, GeofenceAlert $alert): JsonResponse
    {
        $authUser = $request->user();
        if ($authUser && !$authUser->can('geofence_edit')) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $ownerId = $alert->geofence?->owner_id ?? $alert->animal?->owner_id;
        if ($ownerId !== null && !$this->canAccessOwner($request, $ownerId)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        
        $alert->update(['is_acknowledged' => true]);
        return response()->json([
            'message' => 'Alert acknowledged',
            'data' => new GeofenceAlertResource($alert->load(['animal', 'geofence'])),
        ]);
    }

    public function showAlert(Request $request, GeofenceAlert $alert): JsonResponse
    {
        $ownerId = $alert->geofence?->owner_id ?? $alert->animal?->owner_id;
        if ($ownerId !== null && !$this->canAccessOwner($request, $ownerId)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $alert->load(['geofence', 'animal']);
        return response()->json([
            'data' => new GeofenceAlertResource($alert),
        ]);
    }

    public function deleteAlert(Request $request, GeofenceAlert $alert): JsonResponse
    {
        $authUser = $request->user();
        if ($authUser && !$authUser->can('geofence_delete')) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $ownerId = $alert->geofence?->owner_id ?? $alert->animal?->owner_id;
        if ($ownerId !== null && !$this->canAccessOwner($request, $ownerId)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $alert->delete();
        return response()->json(['message' => 'Alert deleted successfully']);
    }

    public function deactivateAlerts(Request $request): JsonResponse
    {
        $query = GeofenceAlert::query();
        $query = $this->filterAlertsByRole($request, $query);

        $count = $query->where('is_acknowledged', false)->update(['is_acknowledged' => true]);

        return response()->json([
            'message' => "{$count} alerts deactivated",
            'count' => $count,
        ]);
    }

    public function sendNotification(Request $request, GeofenceAlert $alert): JsonResponse
    {
        $authUser = $request->user();
        if ($authUser && !$authUser->can('geofence_edit')) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        if (!$this->canAccessOwner($request, $alert->geofence->owner_id)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $result = $this->notificationService->sendGeofenceAlert($alert);
        
        if ($result['success']) {
            return response()->json([
                'message' => 'Notification sent successfully',
                'details' => $result['details'] ?? [],
            ]);
        }

        return response()->json([
            'message' => $result['message'],
            'success' => false,
        ], 400);
    }

    public function sendBulkNotifications(Request $request): JsonResponse
    {
        $authUser = $request->user();
        if ($authUser && !$authUser->can('geofence_edit')) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $query = GeofenceAlert::where('is_acknowledged', false)
            ->whereNull('notification_sent_at');
        
        $query = $this->filterAlertsByRole($request, $query);

        $alerts = $query->limit(10)->get();
        $results = ['sent' => 0, 'failed' => 0, 'details' => []];

        foreach ($alerts as $alert) {
            $result = $this->notificationService->sendGeofenceAlert($alert);
            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
            $results['details'][] = $result;
        }

        return response()->json([
            'message' => "Sent: {$results['sent']}, Failed: {$results['failed']}",
            'results' => $results,
        ]);
    }

    public function geofenceAnimals(Geofence $geofence): JsonResponse
    {
        return response()->json(['data' => $geofence->animals()->with('device')->get()]);
    }

    public function assignAnimals(Request $request, Geofence $geofence): JsonResponse
    {
        $authUser = $request->user();
        if ($authUser && !$authUser->can('geofence_edit')) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        if (!$this->canAccessOwner($request, $geofence->owner_id)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'animal_ids' => 'required|array',
            'animal_ids.*' => 'exists:animals,id',
        ]);

        $geofence->animals()->syncWithoutDetaching($validated['animal_ids']);
        
        return response()->json([
            'message' => 'Animals assigned to geofence',
            'data' => $geofence->load('animals'),
        ]);
    }

    public function removeAnimals(Request $request, Geofence $geofence): JsonResponse
    {
        $authUser = $request->user();
        if ($authUser && !$authUser->can('geofence_edit')) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        if (!$this->canAccessOwner($request, $geofence->owner_id)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'animal_ids' => 'required|array',
            'animal_ids.*' => 'exists:animals,id',
        ]);

        $geofence->animals()->detach($validated['animal_ids']);
        
        return response()->json([
            'message' => 'Animals removed from geofence',
            'data' => $geofence->load('animals'),
        ]);
    }

    public function availableAnimals(Request $request, Geofence $geofence): JsonResponse
    {
        $role = $this->getUserRole($request);
        
        $geofenceOwnerId = $geofence->owner_id;
        
        $query = Animal::with('device');
        
        if ($role !== 'Admin') {
            $query->where('owner_id', $geofenceOwnerId);
        }

        $assignedIds = $geofence->animals()->pluck('animals.id');
        $available = $query->whereNotIn('id', $assignedIds)->get();
        
        return response()->json(['data' => $available]);
    }

    public function geofenceGroups(Geofence $geofence): JsonResponse
    {
        return response()->json(['data' => $geofence->groups()->with('animals')->get()]);
    }

    public function assignGroups(Request $request, Geofence $geofence): JsonResponse
    {
        $authUser = $request->user();
        if ($authUser && !$authUser->can('geofence_edit')) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        if (!$this->canAccessOwner($request, $geofence->owner_id)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'group_ids' => 'required|array',
            'group_ids.*' => 'exists:animal_groups,id',
        ]);

        $geofence->groups()->syncWithoutDetaching($validated['group_ids']);
        
        return response()->json([
            'message' => 'Groups assigned to geofence',
            'data' => $geofence->load('groups'),
        ]);
    }

    public function removeGroups(Request $request, Geofence $geofence): JsonResponse
    {
        $authUser = $request->user();
        if ($authUser && !$authUser->can('geofence_edit')) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        if (!$this->canAccessOwner($request, $geofence->owner_id)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'group_ids' => 'required|array',
            'group_ids.*' => 'exists:animal_groups,id',
        ]);

        $geofence->groups()->detach($validated['group_ids']);
        
        return response()->json([
            'message' => 'Groups removed from geofence',
            'data' => $geofence->load('groups'),
        ]);
    }

    public function availableGroups(Request $request, Geofence $geofence): JsonResponse
    {
        $role = $this->getUserRole($request);
        
        $geofenceOwnerId = $geofence->owner_id;
        
        $query = AnimalGroup::with('animals');
        
        if ($role !== 'Admin') {
            $query->where('owner_id', $geofenceOwnerId);
        }

        $assignedIds = $geofence->groups()->pluck('animal_groups.id');
        $available = $query->whereNotIn('id', $assignedIds)->get();
        
        return response()->json(['data' => $available]);
    }
}