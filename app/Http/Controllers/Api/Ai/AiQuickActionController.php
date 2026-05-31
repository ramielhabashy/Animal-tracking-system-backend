<?php

namespace App\Http\Controllers\Api\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiQuickAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiQuickActionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actions = AiQuickAction::active()->ordered()->get();

        return response()->json(['data' => $actions]);
    }

    public function show(AiQuickAction $aiQuickAction): JsonResponse
    {
        return response()->json(['data' => $aiQuickAction]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'nullable|string',
            'type' => 'required|in:quick_action,suggestion',
            'icon' => 'nullable|string',
            'label' => 'required|string|max:255',
            'prompt' => 'required|string',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $action = AiQuickAction::create($validated);

        return response()->json(['data' => $action, 'message' => 'Quick action created'], 201);
    }

    public function update(Request $request, AiQuickAction $aiQuickAction): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'nullable|string',
            'type' => 'in:quick_action,suggestion',
            'icon' => 'nullable|string',
            'label' => 'string|max:255',
            'prompt' => 'string',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $aiQuickAction->update($validated);

        return response()->json(['data' => $aiQuickAction, 'message' => 'Quick action updated']);
    }

    public function destroy(AiQuickAction $aiQuickAction): JsonResponse
    {
        $aiQuickAction->delete();

        return response()->json(['message' => 'Quick action deleted']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:ai_quick_actions,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->items as $item) {
            AiQuickAction::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Quick actions reordered']);
    }
}
