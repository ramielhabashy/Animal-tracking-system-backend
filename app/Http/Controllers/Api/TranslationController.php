<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    private TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function translate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'target_lang' => 'nullable|string|size:2',
            'source_lang' => 'nullable|string|size:2',
        ]);

        $targetLang = $validated['target_lang'] ?? $request->header('Accept-Language', 'en');
        $targetLang = substr($targetLang, 0, 2);

        $translated = $this->translationService->translate(
            $validated['text'],
            $targetLang,
            $validated['source_lang'] ?? null
        );

        if ($translated === null) {
            return response()->json([
                'translated_text' => $validated['text'],
                'source_lang' => $validated['source_lang'] ?? 'auto',
                'target_lang' => $targetLang,
                'from_cache' => false,
                'error' => 'Translation unavailable',
            ]);
        }

        return response()->json([
            'translated_text' => $translated,
            'source_lang' => $validated['source_lang'] ?? 'auto',
            'target_lang' => $targetLang,
            'from_cache' => false,
        ]);
    }

    public function translateBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'texts' => 'required|array|max:50',
            'texts.*' => 'string|max:5000',
            'target_lang' => 'nullable|string|size:2',
            'source_lang' => 'nullable|string|size:2',
        ]);

        $targetLang = $validated['target_lang'] ?? $request->header('Accept-Language', 'en');
        $targetLang = substr($targetLang, 0, 2);

        $translations = $this->translationService->translateBatch(
            $validated['texts'],
            $targetLang,
            $validated['source_lang'] ?? null
        );

        return response()->json([
            'translations' => $translations,
            'source_lang' => $validated['source_lang'] ?? 'auto',
            'target_lang' => $targetLang,
        ]);
    }
}
