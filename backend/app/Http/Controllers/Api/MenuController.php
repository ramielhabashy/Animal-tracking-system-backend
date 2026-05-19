<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $user?->getPrimaryRoleName();

        $items = MenuItem::with(['children' => function ($q) use ($role) {
            $q->active()->forRole($role)->orderBy('sort_order');
        }])
            ->active()
            ->root()
            ->forRole($role)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->label,
                    'label_key' => $item->label_key,
                    'icon' => $item->icon,
                    'path' => $item->path,
                    'has_children' => $item->children->isNotEmpty(),
                    'children' => $item->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'label' => $child->label,
                            'label_key' => $child->label_key,
                            'icon' => $child->icon,
                            'path' => $child->path,
                        ];
                    }),
                ];
            });

        return response()->json(['data' => $items]);
    }
}
