<?php

namespace App\Http\Controllers\Api\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Models\Task;
use App\Models\User;
use App\Models\Animal;
use App\Models\Geofence;
use App\Models\PredefinedTask;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    use ApiResponse;

    private function canAccessTask(Request $request, Task $task): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        $role = $user->getPrimaryRoleName();

        if ($role === 'Admin') {
            return true;
        }

        if ($task->owner_id == $user->id) {
            return true;
        }

        if ($task->assigned_to == $user->id) {
            return true;
        }

        if ($role === 'Doctor') {
            return $user->managed_by && $task->owner_id == $user->managed_by;
        }

        if (in_array($role, ['Manager', 'Owner'])) {
            $managedUsers = User::where('managed_by', $user->id)->pluck('id')->toArray();
            return in_array($task->owner_id, $managedUsers) || in_array($task->assigned_to, $managedUsers);
        }

        return false;
    }

    private function filterByRole(Request $request, $query)
    {
        $user = $request->user();
        if (!$user) {
            return $query->where('id', 0);
        }

        $role = $user->getPrimaryRoleName();

        if ($role === 'Admin') {
            return $query;
        }

        if ($role === 'Owner') {
            return $query->where('owner_id', $user->id);
        }

        if ($role === 'Manager') {
            $managedUserIds = User::where('managed_by', $user->id)->pluck('id')->toArray();
            $managedUserIds[] = $user->id;
            return $query->where(function ($q) use ($managedUserIds) {
                $q->whereIn('owner_id', $managedUserIds)
                  ->orWhereIn('assigned_to', $managedUserIds);
            });
        }

        if ($role === 'Shepherd') {
            return $query->where('assigned_to', $user->id);
        }

        if ($role === 'Doctor') {
            if ($user->managed_by) {
                return $query->where(function ($q) use ($user) {
                    $q->where('owner_id', $user->managed_by)
                      ->orWhere('assigned_to', $user->id);
                });
            }
            return $query->where('assigned_to', $user->id);
        }

        return $query->where('id', 0);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['owner', 'assignee', 'animal', 'geofence']);
        $query = $this->filterByRole($request, $query);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('assigned_to') && $request->assigned_to !== 'all') {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('owner_id') && $request->owner_id !== 'all') {
            $query->where('owner_id', $request->owner_id);
        }

        if ($request->boolean('overdue')) {
            $query->where('due_date', '<', now())->where('status', '!=', 'completed');
        }

        $tasks = $query->orderBy('due_date', 'asc')->paginate(20);

        return $this->paginated($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->can('manage_tasks')) {
            return $this->forbidden('Unauthorized to create tasks');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'animal_id' => 'nullable|exists:animals,id',
            'geofence_id' => 'nullable|exists:geofences,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'task_type' => 'nullable|in:inspection,medical,feeding,movement,other',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'is_recurring' => 'nullable|boolean',
            'recurrence_pattern' => 'nullable|string|in:daily,weekly,monthly,custom',
            'recurrence_interval' => 'nullable|integer|min:1|max:365',
            'recurrence_end_date' => 'nullable|date|after:today',
        ]);

        $assignee = User::find($validated['assigned_to']);
        if (!$assignee) {
            return $this->notFound('Assignee not found');
        }

        // Check permissions for Owner/Manager
        $role = $user->getPrimaryRoleName();
        if ($role === 'Manager') {
            $managedUsers = User::where('managed_by', $user->id)->pluck('id')->toArray();
            if (!in_array($assignee->managed_by, $managedUsers) && $assignee->managed_by != $user->id) {
                return $this->forbidden('Cannot assign task to this user');
            }
        }

        if ($role === 'Owner' && $assignee->managed_by != $user->id) {
            return $this->forbidden('Cannot assign task to this user');
        }

        $validated['owner_id'] = $user->id;
        $validated['status'] = 'pending';

        if ($request->boolean('is_recurring')) {
            $validated['is_recurring'] = true;
            $validated['recurrence_pattern'] = $request->recurrence_pattern ?? 'weekly';
            $validated['recurrence_interval'] = $request->recurrence_interval ?? 1;
            $validated['recurrence_end_date'] = $request->recurrence_end_date ?? null;
        }

        $task = Task::create($validated);

        if ($task->is_recurring) {
            $task->createNextOccurrence();
        }

        return $this->created($task->load(['owner', 'assignee', 'animal', 'geofence']), 'Task created successfully');
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        if (!$this->canAccessTask($request, $task)) {
            return $this->forbidden('Unauthorized');
        }

        return $this->success($task->load(['owner', 'assignee', 'animal', 'geofence']));
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        if (!$this->canAccessTask($request, $task)) {
            return $this->forbidden('Unauthorized');
        }

        $user = $request->user();
        $role = $user->getPrimaryRoleName();

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'animal_id' => 'nullable|exists:animals,id',
            'geofence_id' => 'nullable|exists:geofences,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'task_type' => 'nullable|in:inspection,medical,feeding,movement,other',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        // Restrict Shepherd from changing certain fields
        if ($role === 'Shepherd') {
            unset($validated['assigned_to'], $validated['owner_id'], $validated['title'], $validated['priority'], $validated['task_type'], $validated['due_date']);
            if (isset($validated['status']) && $validated['status'] === 'cancelled') {
                unset($validated['status']);
            }
        } elseif (!in_array($role, ['Owner', 'Admin'])) {
            unset($validated['assigned_to'], $validated['owner_id']);
        }

        // Check if reassignment is allowed
        if (isset($validated['assigned_to'])) {
            $assignee = User::find($validated['assigned_to']);
            if ($assignee && $role !== 'Admin') {
                if ($assignee->managed_by != $user->id) {
                    return $this->forbidden('Cannot reassign to this user');
                }
            }
        }

        $task->update($validated);

        return $this->updated($task->load(['owner', 'assignee', 'animal', 'geofence']), 'Task updated successfully');
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->can('manage_tasks')) {
            return $this->forbidden('Unauthorized');
        }

        $role = $user->getPrimaryRoleName();

        if ($role === 'Shepherd') {
            return $this->forbidden('Shepherds cannot delete tasks');
        }

        if ($role === 'Admin' || $task->owner_id == $user->id) {
            $task->delete();
            return $this->deleted('Task deleted successfully');
        }

        return $this->forbidden('Unauthorized');
    }

    public function myTasks(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->unauthorized();
        }

        $query = Task::with(['owner', 'animal', 'geofence'])
            ->where('assigned_to', $user->id);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $tasks = $query->orderBy('due_date', 'asc')->paginate(20);

        return $this->paginated($tasks);
    }

    public function complete(Task $task): JsonResponse
    {
        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $this->success($task->load(['owner', 'assignee', 'animal', 'geofence']), 'Task completed');
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->unauthorized();
        }

        $query = Task::query();
        $role = $user->getPrimaryRoleName();

        if ($role === 'Admin') {
            // No filter
        } elseif ($role === 'Owner') {
            $query->where('owner_id', $user->id);
        } elseif ($role === 'Manager') {
            $managedUserIds = User::where('managed_by', $user->id)->pluck('id')->toArray();
            $managedUserIds[] = $user->id;
            $query->where(function ($q) use ($managedUserIds) {
                $q->whereIn('owner_id', $managedUserIds)
                  ->orWhereIn('assigned_to', $managedUserIds);
            });
        } elseif ($role === 'Doctor') {
            if ($user->managed_by) {
                $query->where(function ($q) use ($user) {
                    $q->where('owner_id', $user->managed_by)
                      ->orWhere('assigned_to', $user->id);
                });
            } else {
                $query->where('assigned_to', $user->id);
            }
        } else {
            $query->where('assigned_to', $user->id);
        }

        $stats = [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'overdue' => (clone $query)->where('due_date', '<', now())->where('status', '!=', 'completed')->count(),
            'high_priority' => (clone $query)->whereIn('priority', ['high', 'urgent'])->whereNotIn('status', ['completed', 'cancelled'])->count(),
        ];

        return $this->success($stats);
    }
}
