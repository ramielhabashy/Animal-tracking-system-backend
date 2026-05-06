<?php

namespace App\Http\Controllers\Api\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Models\TaskLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class TaskLogController extends Controller
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

        if (in_array($role, ['Manager', 'Owner'])) {
            $managedUsers = User::where('managed_by', $user->id)->pluck('id')->toArray();
            return in_array($task->owner_id, $managedUsers) || in_array($task->assigned_to, $managedUsers);
        }

        return false;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->unauthorized();
        }

        $query = TaskLog::with(['task', 'user']);
        $role = $user->getPrimaryRoleName();

        if (in_array($role, ['Admin', 'Owner'])) {
            // No filter
        } elseif ($role === 'Manager') {
            $managedUserIds = User::where('managed_by', $user->id)->pluck('id')->toArray();
            $managedUserIds[] = $user->id;
            $query->whereHas('task', function ($q) use ($managedUserIds) {
                $q->whereIn('owner_id', $managedUserIds)
                  ->orWhereIn('assigned_to', $managedUserIds);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->has('task_id')) {
            $query->where('task_id', $request->task_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('log_type') && $request->log_type !== 'all') {
            $query->where('log_type', $request->log_type);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(30);

        return $this->paginated($logs);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->getPrimaryRoleName() !== 'Shepherd') {
            return $this->forbidden('Only shepherds can submit task logs');
        }

        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'log_type' => 'required|in:checkpoint,photo,note,location_update,status_change',
            'description' => 'required|string|max:2000',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg|max:5120',
            'voice_note' => 'nullable|file|mimes:webm,mp3,wav|max:10240',
        ]);

        $task = Task::find($validated['task_id']);

        if (!$this->canAccessTask($request, $task)) {
            return $this->forbidden('Unauthorized to submit logs for this task');
        }

        if ($task->assigned_to != $user->id) {
            return $this->forbidden('You can only submit logs for tasks assigned to you');
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = time() . '_' . $user->id . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            $photoPath = $photo->storeAs('task-logs', $filename, 'public');
        }

        $voiceNotePath = null;
        if ($request->hasFile('voice_note')) {
            $voice = $request->file('voice_note');
            $filename = time() . '_' . $user->id . '_voice_' . uniqid() . '.webm';
            $voiceNotePath = $voice->storeAs('task-logs', $filename, 'public');
        }

        $log = TaskLog::create([
            'task_id' => $validated['task_id'],
            'user_id' => $user->id,
            'log_type' => $validated['log_type'],
            'description' => $validated['description'],
            'location_lat' => $validated['location_lat'] ?? null,
            'location_lng' => $validated['location_lng'] ?? null,
            'photo_path' => $photoPath,
            'voice_note_path' => $voiceNotePath,
            'status' => 'submitted',
        ]);

        if ($task->status === 'pending') {
            $task->update(['status' => 'in_progress']);
        }

        return $this->created($log->load(['task', 'user']), 'Task log submitted successfully');
    }

    public function show(Request $request, TaskLog $taskLog): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->unauthorized();
        }

        $role = $user->getPrimaryRoleName();

        if ($role === 'Admin') {
            // Allow
        } elseif (in_array($role, ['Owner', 'Manager'])) {
            if ($taskLog->task->owner_id != $user->id && $taskLog->task->assigned_to != $user->id) {
                $managedUsers = User::where('managed_by', $user->id)->pluck('id')->toArray();
                if (!in_array($taskLog->task->owner_id, $managedUsers)) {
                    return $this->forbidden('Unauthorized');
                }
            }
        } else {
            if ($taskLog->user_id != $user->id && $taskLog->task->assigned_to != $user->id) {
                return $this->forbidden('Unauthorized');
            }
        }

        return $this->success($taskLog->load(['task', 'user']));
    }

    public function update(Request $request, TaskLog $taskLog): JsonResponse
    {
        $user = $request->user();
        if (!$user || !in_array($user->getPrimaryRoleName(), ['Admin', 'Owner', 'Manager'])) {
            return $this->forbidden('Only managers can update log status');
        }

        $validated = $request->validate([
            'status' => 'nullable|in:submitted,reviewed,approved,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        $taskLog->update($validated);

        return $this->updated($taskLog->load(['task', 'user']), 'Log updated successfully');
    }

    public function destroy(Request $request, TaskLog $taskLog): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->unauthorized();
        }

        if ($user->getPrimaryRoleName() === 'Admin' || $taskLog->task->owner_id == $user->id) {
            if ($taskLog->photo_path) {
                Storage::disk('public')->delete($taskLog->photo_path);
            }
            $taskLog->delete();
            return $this->deleted('Log deleted successfully');
        }

        return $this->forbidden('Unauthorized');
    }

    public function logsForTask(Request $request, Task $task): JsonResponse
    {
        if (!$this->canAccessTask($request, $task)) {
            return $this->forbidden('Unauthorized');
        }

        $logs = TaskLog::where('task_id', $task->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($logs);
    }

    public function myLogs(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->unauthorized();
        }

        $query = TaskLog::with(['task', 'user'])
            ->where('user_id', $user->id);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('log_type') && $request->log_type !== 'all') {
            $query->where('log_type', $request->log_type);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(30);

        return $this->paginated($logs);
    }

    public function archive(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->unauthorized();
        }

        $query = TaskLog::with(['task', 'user']);

        $role = $user->getPrimaryRoleName();

        if ($role === 'Admin') {
            // No filter
        } elseif ($role === 'Owner') {
            $query->whereHas('task', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            });
        } elseif ($role === 'Manager') {
            $managedUserIds = User::where('managed_by', $user->id)->pluck('id')->toArray();
            $managedUserIds[] = $user->id;
            $query->whereHas('task', function ($q) use ($managedUserIds) {
                $q->whereIn('owner_id', $managedUserIds);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('log_type') && $request->log_type !== 'all') {
            $query->where('log_type', $request->log_type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('task', function ($tq) use ($search) {
                        $tq->where('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return $this->paginated($logs);
    }
}
