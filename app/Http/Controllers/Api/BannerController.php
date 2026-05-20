<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function active(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|string',
            'locale' => 'nullable|string|size:2',
        ]);

        $query = Banner::active()->orderBy('sort_order')->orderByDesc('id');

        if (!empty($validated['type'])) {
            $types = explode(',', $validated['type']);
            $query->byType($types);
        }

        $locale = $validated['locale'] ?? app()->getLocale();

        $banners = $query->get()->map(function ($banner) use ($locale) {
            return [
                'id' => $banner->id,
                'type' => $banner->type,
                'icon' => $banner->icon,
                'color_scheme' => $banner->color_scheme,
                'title' => $banner->getTitle($locale),
                'description' => $banner->getDescription($locale),
                'button_text' => $banner->getButtonText($locale),
                'button_url' => $banner->button_url,
                'sort_order' => $banner->sort_order,
            ];
        });

        return response()->json(['data' => $banners]);
    }

    public function index(): JsonResponse
    {
        $banners = Banner::orderBy('sort_order')->orderByDesc('id')->get()->map(function ($banner) {
            return [
                'id' => $banner->id,
                'type' => $banner->type,
                'icon' => $banner->icon,
                'color_scheme' => $banner->color_scheme,
                'translations' => $banner->translations,
                'button_text' => $banner->button_text,
                'button_url' => $banner->button_url,
                'sort_order' => $banner->sort_order,
                'is_active' => $banner->is_active,
                'starts_at' => $banner->starts_at?->toISOString(),
                'expires_at' => $banner->expires_at?->toISOString(),
                'created_at' => $banner->created_at?->toISOString(),
            ];
        });

        return response()->json(['data' => $banners]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'icon' => 'nullable|string|max:100',
            'color_scheme' => 'required|string|max:50',
            'translations' => 'required|array',
            'translations.*.title' => 'nullable|string|max:255',
            'translations.*.description' => 'nullable|string|max:1000',
            'translations.*.button_text' => 'nullable|string|max:255',
            'button_url' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);

        $banner = Banner::create($validated);

        return response()->json(['data' => ['id' => $banner->id]], 201);
    }

    public function update(Request $request, Banner $banner): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'icon' => 'nullable|string|max:100',
            'color_scheme' => 'required|string|max:50',
            'translations' => 'required|array',
            'translations.*.title' => 'nullable|string|max:255',
            'translations.*.description' => 'nullable|string|max:1000',
            'translations.*.button_text' => 'nullable|string|max:255',
            'button_url' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);

        $banner->update($validated);

        return response()->json(['message' => 'Banner updated']);
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();
        return response()->json(['message' => 'Banner deleted']);
    }
}
