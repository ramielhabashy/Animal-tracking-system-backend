<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaskType;
use App\Models\TaskLogType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = TaskType::orderBy('name')->get();
        return response()->json(['data' => $types]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:task_types,slug',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $type = TaskType::create($validated);
        return response()->json(['data' => $type, 'message' => 'Task type created'], 201);
    }

    public function update(Request $request, TaskType $taskType): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:50|unique:task_types,slug,' . $taskType->id,
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $taskType->update($validated);
        return response()->json(['data' => $taskType, 'message' => 'Task type updated']);
    }

    public function destroy(TaskType $taskType): JsonResponse
    {
        $taskType->delete();
        return response()->json(['message' => 'Task type deleted']);
    }

    // Log Types CRUD
    public function logTypesList(): JsonResponse
    {
        $types = TaskLogType::orderBy('name')->get();
        return response()->json(['data' => $types]);
    }

    public function storeLogType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:task_log_types,slug',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'allows_media' => 'nullable|boolean',
            'is_status' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $type = TaskLogType::create($validated);
        return response()->json(['data' => $type, 'message' => 'Log type created'], 201);
    }

    public function updateLogType(Request $request, TaskLogType $taskLogType): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:50|unique:task_log_types,slug,' . $taskLogType->id,
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'allows_media' => 'nullable|boolean',
            'is_status' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $taskLogType->update($validated);
        return response()->json(['data' => $taskLogType, 'message' => 'Log type updated']);
    }

    public function destroyLogType(TaskLogType $taskLogType): JsonResponse
    {
        $taskLogType->delete();
        return response()->json(['message' => 'Log type deleted']);
    }
}
