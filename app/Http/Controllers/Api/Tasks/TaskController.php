<?php

namespace App\Http\Controllers\Api\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Http\Controllers\Traits\SendsEmailNotifications;
use App\Models\Task;
use App\Models\User;
use App\Models\Animal;
use App\Models\Geofence;
use App\Models\PredefinedTask;
use App\Models\TaskType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    use ApiResponse, SendsEmailNotifications;

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
            if ($user->managed_by && $task->owner_id == $user->managed_by) {
                return true;
            }
            return $task->assigned_to == $user->id;
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
        $user = $request->user();
        if (!$user || !$user->can('task_view')) {
            return $this->forbidden('Unauthorized to view tasks');
        }

        $query = Task::with(['owner', 'assignee', 'animal', 'geofence'])->withCount('logs');
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
        if (!$user || !$user->can('task_create')) {
            return $this->forbidden('Unauthorized to create tasks');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'animal_id' => 'nullable|exists:animals,id',
            'geofence_id' => 'nullable|exists:geofences,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'task_type' => 'nullable|string|exists:task_types,slug',
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

        // Check assignment permissions by role
        $role = $user->getPrimaryRoleName();
        if ($role === 'Shepherd') {
            // Shepherds can only assign tasks to themselves
            if ((int) $validated['assigned_to'] !== (int) $user->id) {
                return $this->forbidden('Shepherds can only assign tasks to themselves');
            }
        } elseif ($role === 'Manager') {
            $managedUsers = User::where('managed_by', $user->id)->pluck('id')->toArray();
            if (!in_array($assignee->managed_by, $managedUsers) && $assignee->managed_by != $user->id) {
                return $this->forbidden('Cannot assign task to this user');
            }
        } elseif ($role === 'Owner' && $assignee->managed_by != $user->id) {
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

        if ($task->assigned_to && (int) $task->assigned_to !== (int) $user->id) {
            \App\Models\Notification::create([
                'user_id' => $task->assigned_to,
                'type' => 'task_assigned',
                'title' => 'New Task Assigned',
                'body' => 'You have been assigned: ' . $task->title,
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'priority' => $task->priority,
                    'due_date' => $task->due_date?->toDateString(),
                    'link' => '/tasks',
                ],
            ]);

            $assignee = User::find($task->assigned_to);
            if ($assignee) {
                $this->sendNotificationMail(
                    $assignee,
                    'task_assigned',
                    "New Task Assigned – {$task->title}",
                    [
                        "You have been assigned a new task: {$task->title}",
                        $task->description ? "Description: {$task->description}" : '',
                        $task->due_date ? "Due date: {$task->due_date->format('M d, Y')}" : '',
                        "Priority: {$task->priority}",
                    ],
                    rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/tasks',
                    'View Tasks',
                );
            }
        }

        return $this->created($task->load(['owner', 'assignee', 'animal', 'geofence']), 'Task created successfully');
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->can('task_view')) {
            return $this->forbidden('Unauthorized to view tasks');
        }

        if (!$this->canAccessTask($request, $task)) {
            return $this->forbidden('Unauthorized');
        }

        return $this->success($task->load(['owner', 'assignee', 'animal', 'geofence']));
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->can('task_view')) {
            return $this->forbidden('Unauthorized to view tasks');
        }

        if (!$this->canAccessTask($request, $task)) {
            return $this->forbidden('Unauthorized');
        }
        $role = $user->getPrimaryRoleName();

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'animal_id' => 'nullable|exists:animals,id',
            'geofence_id' => 'nullable|exists:geofences,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
             'status' => 'nullable|in:pending,in_progress,delivered,completed,cancelled',
            'task_type' => 'nullable|string|exists:task_types,slug',
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
        $previousAssignee = $task->assigned_to;
        if (isset($validated['assigned_to'])) {
            $assignee = User::find($validated['assigned_to']);
            if ($assignee && $role !== 'Admin') {
                if ($assignee->managed_by != $user->id) {
                    return $this->forbidden('Cannot reassign to this user');
                }
            }
        }

        $task->update($validated);

        // Notify new assignee on reassignment
        if (isset($validated['assigned_to']) && (int) $validated['assigned_to'] !== (int) $previousAssignee && (int) $validated['assigned_to'] !== (int) $user->id) {
            \App\Models\Notification::create([
                'user_id' => $task->assigned_to,
                'type' => 'task_assigned',
                'title' => 'New Task Assigned',
                'body' => 'You have been assigned: ' . $task->title,
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'priority' => $task->priority,
                    'due_date' => $task->due_date?->toDateString(),
                    'link' => '/tasks',
                ],
            ]);
        }

        return $this->updated($task->load(['owner', 'assignee', 'animal', 'geofence']), 'Task updated successfully');
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->can('task_delete')) {
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
        if (!$user || !$user->can('task_view')) {
            return $this->forbidden('Unauthorized to view tasks');
        }

        $query = Task::with(['owner', 'animal', 'geofence'])
            ->where('assigned_to', $user->id);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $tasks = $query->orderBy('due_date', 'asc')->paginate(20);

        return $this->paginated($tasks);
    }

    public function complete(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->can('task_complete')) {
            return $this->forbidden('Unauthorized to complete tasks');
        }

        if (!$this->canAccessTask($request, $task)) {
            return $this->forbidden('Unauthorized');
        }

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Notify task owner that task was completed
        if ($task->owner_id && (int) $task->owner_id !== (int) $user->id) {
            \App\Models\Notification::create([
                'user_id' => $task->owner_id,
                'type' => 'task_completed',
                'title' => 'Task Completed',
                'body' => $user->name . ' completed task: ' . $task->title,
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'completed_by' => $user->id,
                    'completed_by_name' => $user->name,
                    'link' => '/tasks',
                ],
            ]);

            $owner = User::find($task->owner_id);
            if ($owner) {
                $this->sendNotificationMail(
                    $owner,
                    'task_assigned',
                    "Task Completed – {$task->title}",
                    [
                        "{$user->name} has completed the task: {$task->title}",
                    ],
                    rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/tasks',
                    'View Tasks',
                );
            }
        }

        // Notify assignee if completed by someone else (e.g. owner marking assignee's task as done)
        if ($task->assigned_to && (int) $task->assigned_to !== (int) $user->id && (int) $task->assigned_to !== (int) $task->owner_id) {
            \App\Models\Notification::create([
                'user_id' => $task->assigned_to,
                'type' => 'task_completed',
                'title' => 'Task Marked Complete',
                'body' => 'Your task has been marked complete: ' . $task->title,
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'completed_by' => $user->id,
                    'completed_by_name' => $user->name,
                    'link' => '/tasks',
                ],
            ]);

            $assignee = User::find($task->assigned_to);
            if ($assignee) {
                $this->sendNotificationMail(
                    $assignee,
                    'task_assigned',
                    "Task Marked Complete – {$task->title}",
                    [
                        "Your task \"{$task->title}\" has been marked complete by {$user->name}.",
                    ],
                    rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/tasks',
                    'View Tasks',
                );
            }
        }

        return $this->success($task->load(['owner', 'assignee', 'animal', 'geofence']), 'Task completed');
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->can('task_view')) {
            return $this->forbidden('Unauthorized to view tasks');
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
             'delivered' => (clone $query)->where('status', 'delivered')->count(),
             'completed' => (clone $query)->where('status', 'completed')->count(),
             'overdue' => (clone $query)->where('due_date', '<', now())->whereNotIn('status', ['completed', 'delivered', 'cancelled'])->count(),
             'high_priority' => (clone $query)->whereIn('priority', ['high', 'urgent'])->whereNotIn('status', ['completed', 'delivered', 'cancelled'])->count(),
         ];

        return $this->success($stats);
    }

    public function calendar(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->unauthorized();
        }

        $month = $request->integer('month', now()->month);
        $year = $request->integer('year', now()->year);

        $query = Task::with(['assignee', 'animal'])
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $month);

        $query = $this->filterByRole($request, $query);

        $tasks = $query->get(['id', 'task_id', 'title', 'due_date', 'status', 'priority', 'assigned_to', 'animal_id']);

        $grouped = [];
        foreach ($tasks as $task) {
            $date = $task->due_date ? $task->due_date->toDateString() : 'no_date';
            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }
            $grouped[$date][] = [
                'id' => $task->id,
                'task_id' => $task->task_id,
                'title' => $task->title,
                'due_date' => $task->due_date?->toDateString(),
                'status' => $task->status,
                'priority' => $task->priority,
                'assignee_name' => $task->assignee?->name,
                'animal_name' => $task->animal?->animal_id,
            ];
        }

        return $this->success($grouped);
    }

     public function deliver(Request $request, Task $task): JsonResponse
     {
         $user = $request->user();
         if (!$user) {
             return $this->unauthorized();
         }

         if (!$this->canAccessTask($request, $task)) {
             return $this->forbidden('Unauthorized');
         }

         if (in_array($task->status, ['delivered', 'completed', 'cancelled'])) {
             return $this->error('Task is already delivered, completed or cancelled');
         }

         $validated = $request->validate([
             'notes' => 'nullable|string|max:2000',
         ]);

         $task->update([
             'status' => 'delivered',
             'delivered_at' => now(),
             'delivered_by' => $user->id,
             'deliver_notes' => $validated['notes'] ?? $task->deliver_notes,
         ]);

         if ($task->owner_id && (int) $task->owner_id !== (int) $user->id) {
             \App\Models\Notification::create([
                 'user_id' => $task->owner_id,
                 'type' => 'task_delivered',
                 'title' => 'Task Delivered for Review',
                 'body' => $user->name . ' delivered task: ' . $task->title,
                 'data' => [
                     'task_id' => $task->id,
                     'task_title' => $task->title,
                     'delivered_by' => $user->id,
                     'delivered_by_name' => $user->name,
                     'deliver_notes' => $validated['notes'] ?? null,
                     'link' => '/tasks',
                 ],
             ]);
         }

          return $this->success($task->load(['owner', 'assignee', 'animal', 'geofence']), 'Task delivered for review');
      }

     public function approve(Request $request, Task $task): JsonResponse
     {
         $user = $request->user();
         if (!$user) {
             return $this->unauthorized();
         }

         if (!$this->canAccessTask($request, $task)) {
             return $this->forbidden('Unauthorized');
         }

         if ($task->status !== 'delivered') {
             return $this->error('Task is not delivered for approval');
         }

         $role = $user->getPrimaryRoleName();
         $isOwner = (int) $task->owner_id === (int) $user->id;
         
         if (!in_array($role, ['Admin', 'Owner', 'Manager']) && !$isOwner) {
             return $this->forbidden('Only task owner, admin, or manager can approve tasks');
         }

         $validated = $request->validate([
             'notes' => 'nullable|string|max:2000',
         ]);

         $task->update([
             'status' => 'completed',
             'completed_at' => now(),
         ]);

         if ($task->assigned_to && (int) $task->assigned_to !== (int) $user->id) {
             \App\Models\Notification::create([
                 'user_id' => $task->assigned_to,
                 'type' => 'task_approved',
                 'title' => 'Task Approved',
                 'body' => 'Your task "' . $task->title . '" has been approved by ' . $user->name,
                 'data' => [
                     'task_id' => $task->id,
                     'task_title' => $task->title,
                     'approved_by' => $user->id,
                     'approved_by_name' => $user->name,
                     'approval_notes' => $validated['notes'] ?? null,
                     'link' => '/tasks',
                 ],
             ]);
         }

         if ($task->is_recurring && $task->next_due_date) {
             $task->createNextOccurrence();
         }

         return $this->success($task->load(['owner', 'assignee', 'animal', 'geofence']), 'Task approved and completed');
     }

     public function reject(Request $request, Task $task): JsonResponse
     {
         $user = $request->user();
         if (!$user) {
             return $this->unauthorized();
         }

         if (!$this->canAccessTask($request, $task)) {
             return $this->forbidden('Unauthorized');
         }

         if ($task->status !== 'delivered') {
             return $this->error('Task is not delivered for review');
         }

         $role = $user->getPrimaryRoleName();
         $isOwner = (int) $task->owner_id === (int) $user->id;
         
         if (!in_array($role, ['Admin', 'Owner', 'Manager']) && !$isOwner) {
             return $this->forbidden('Only task owner, admin, or manager can reject tasks');
         }

         $validated = $request->validate([
             'notes' => 'required|string|max:2000',
         ]);

         $task->update([
             'status' => 'in_progress',
             'reject_notes' => $validated['notes'],
         ]);

         if ($task->assigned_to && (int) $task->assigned_to !== (int) $user->id) {
             \App\Models\Notification::create([
                 'user_id' => $task->assigned_to,
                 'type' => 'task_rejected',
                 'title' => 'Task Rejected - Needs More Work',
                 'body' => 'Your task "' . $task->title . '" was rejected by ' . $user->name . '. Reason: ' . $validated['notes'],
                 'data' => [
                     'task_id' => $task->id,
                     'task_title' => $task->title,
                     'rejected_by' => $user->id,
                     'rejected_by_name' => $user->name,
                     'reject_notes' => $validated['notes'],
                     'link' => '/tasks',
                 ],
             ]);
         }

         return $this->success($task->load(['owner', 'assignee', 'animal', 'geofence']), 'Task rejected and sent back to assignee');
     }

     public function reassign(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->unauthorized();
        }

        if (!$this->canAccessTask($request, $task)) {
            return $this->forbidden('Unauthorized');
        }

        $role = $user->getPrimaryRoleName();
        if ($role === 'Shepherd') {
            return $this->forbidden('Shepherds cannot reassign tasks');
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $assignee = User::find($validated['assigned_to']);
        if ($role !== 'Admin' && $assignee->managed_by != $user->id) {
            return $this->forbidden('Cannot reassign to this user');
        }

        $previousAssignee = $task->assigned_to;
        $task->update(['assigned_to' => $validated['assigned_to']]);

        if ((int) $validated['assigned_to'] !== (int) $user->id) {
            \App\Models\Notification::create([
                'user_id' => $task->assigned_to,
                'type' => 'task_assigned',
                'title' => 'Task Reassigned',
                'body' => 'Task has been reassigned to you: ' . $task->title,
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'priority' => $task->priority,
                    'due_date' => $task->due_date?->toDateString(),
                    'link' => '/tasks',
                ],
            ]);
        }

        return $this->success($task->load(['owner', 'assignee', 'animal', 'geofence']), 'Task reassigned successfully');
    }

    public function taskTypes(): JsonResponse
    {
        $types = TaskType::active()->orderBy('name')->get(['id', 'name', 'slug', 'icon', 'color']);
        return response()->json(['data' => $types]);
    }
}
