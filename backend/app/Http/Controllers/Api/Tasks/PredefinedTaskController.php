<?php

namespace App\Http\Controllers\Api\Tasks;

use App\Http\Controllers\Controller;
use App\Models\PredefinedTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Traits\OwnableAuthorization;

class PredefinedTaskController extends Controller
{
    use OwnableAuthorization;

    public function index(Request $request): JsonResponse
    {
        $query = PredefinedTask::query();
        $query = $this->filterByOwner($request, $query);

        $tasks = $query->orderBy('title', 'asc')->get();

        return response()->json(['data' => $tasks]);
    }

    public function store(Request $request): JsonResponse
    {
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);

        if (!in_array($userRole, ['Admin', 'Owner', 'Manager'])) {
            return response()->json(['message' => 'Unauthorized to create predefined tasks', 'error' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'task_type' => 'nullable|string|exists:task_types,slug',
            'is_recurring' => 'nullable|boolean',
            'recurrence_pattern' => 'nullable|string|in:daily,weekly,monthly,custom',
            'recurrence_interval' => 'nullable|integer|min:1|max:365',
        ]);

        $validated['owner_id'] = $userId;

        $predefinedTask = PredefinedTask::create($validated);

        return response()->json([
            'message' => 'Predefined task created successfully',
            'data' => $predefinedTask,
        ], 201);
    }

    public function show(Request $request, PredefinedTask $predefinedTask): JsonResponse
    {
        $userId = $this->getUserId($request);
        $user = $this->getUser($request);

        if ($user && $user->hasRole('Admin')) {
            return response()->json(['data' => $predefinedTask]);
        }

        if ($predefinedTask->owner_id != $userId) {
            $manager = $user;
            if (!$manager || !$manager->hasRole('Manager') || !in_array($predefinedTask->owner_id, User::where('managed_by', $userId)->pluck('id')->toArray())) {
                return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
            }
        }

        return response()->json(['data' => $predefinedTask]);
    }

    public function update(Request $request, PredefinedTask $predefinedTask): JsonResponse
    {
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);

        if ($userRole !== 'Admin' && $predefinedTask->owner_id != $userId) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'task_type' => 'nullable|string|exists:task_types,slug',
            'is_recurring' => 'nullable|boolean',
            'recurrence_pattern' => 'nullable|string|in:daily,weekly,monthly,custom',
            'recurrence_interval' => 'nullable|integer|min:1|max:365',
        ]);

        $predefinedTask->update($validated);

        return response()->json([
            'message' => 'Predefined task updated successfully',
            'data' => $predefinedTask,
        ]);
    }

    public function destroy(Request $request, PredefinedTask $predefinedTask): JsonResponse
    {
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);

        if ($userRole !== 'Admin' && $predefinedTask->owner_id != $userId) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $predefinedTask->delete();

        return response()->json(['message' => 'Predefined task deleted successfully']);
    }
}
