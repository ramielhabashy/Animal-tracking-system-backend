<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MenuItemController extends Controller
{
    public function index(): JsonResponse
    {
        $items = MenuItem::with('children')
            ->root()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->label,
                    'label_key' => $item->label_key,
                    'icon' => $item->icon,
                    'path' => $item->path,
                    'parent_id' => $item->parent_id,
                    'sort_order' => $item->sort_order,
                    'roles' => $item->roles ?? [],
                    'is_active' => $item->is_active,
                    'children' => $item->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'label' => $child->label,
                            'label_key' => $child->label_key,
                            'icon' => $child->icon,
                            'path' => $child->path,
                            'parent_id' => $child->parent_id,
                            'sort_order' => $child->sort_order,
                            'roles' => $child->roles ?? [],
                            'is_active' => $child->is_active,
                        ];
                    }),
                ];
            });

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'label_key' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'path' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:menu_items,id',
            'sort_order' => 'nullable|integer|min:0',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
            'is_active' => 'nullable|boolean',
        ]);

        $item = MenuItem::create($validated);

        return response()->json([
            'message' => 'Menu item created',
            'data' => $item->load('children'),
        ], 201);
    }

    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'sometimes|string|max:255',
            'label_key' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'path' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|exists:menu_items,id',
            'sort_order' => 'nullable|integer|min:0',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
            'is_active' => 'nullable|boolean',
        ]);

        $menuItem->update($validated);

        return response()->json([
            'message' => 'Menu item updated',
            'data' => $menuItem->fresh()->load('children'),
        ]);
    }

    public function destroy(MenuItem $menuItem): JsonResponse
    {
        $menuItem->delete();

        return response()->json(['message' => 'Menu item deleted']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:menu_items,id',
            'items.*.sort_order' => 'required|integer|min:0',
            'items.*.parent_id' => 'nullable|exists:menu_items,id',
        ]);

        foreach ($validated['items'] as $itemData) {
            MenuItem::where('id', $itemData['id'])->update([
                'sort_order' => $itemData['sort_order'],
                'parent_id' => $itemData['parent_id'] ?? null,
            ]);
        }

        return response()->json(['message' => 'Menu reordered']);
    }
}
