<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiTranslationController extends Controller
{
    private const MAX_LIMIT = 500;
    private const DEFAULT_LIMIT = 200;

    public function fillUi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_lang' => 'required|string|size:2',
            'target_lang' => 'required|string|size:2',
            'group' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:' . self::MAX_LIMIT,
        ]);

        $sourceLang = $validated['source_lang'];
        $targetLang = $validated['target_lang'];
        $group = $validated['group'] ?? null;
        $limit = $validated['limit'] ?? self::DEFAULT_LIMIT;

        if ($sourceLang === $targetLang) {
            return response()->json(['error' => 'Source and target languages must differ'], 422);
        }

        $missing = $this->getMissingUiKeys($sourceLang, $targetLang, $group, $limit);

        if (empty($missing)) {
            return response()->json([
                'filled' => 0,
                'remaining' => 0,
                'total' => 0,
                'group' => $group,
                'message' => 'No missing translations found',
            ]);
        }

        $keys = array_keys($missing);
        $texts = array_values($missing);

        $translations = $this->batchTranslateWithAi($texts, $sourceLang, $targetLang);

        if (empty($translations)) {
            return response()->json(['error' => 'AI translation failed — check AI settings'], 500);
        }

        $filled = 0;
        foreach ($keys as $i => $key) {
            $translated = $translations[$i] ?? null;
            if ($translated !== null && trim($translated) !== '' && trim($translated) !== $texts[$i]) {
                DB::table('translations')->updateOrInsert(
                    [
                        'language_code' => $targetLang,
                        'group' => $group ?? $this->extractGroup($key),
                        'key' => $key,
                    ],
                    [
                        'value' => trim($translated),
                        'updated_at' => now(),
                    ]
                );
                $filled++;
            }
        }

        $remaining = $this->countMissingUiKeys($sourceLang, $targetLang, $group);

        return response()->json([
            'filled' => $filled,
            'remaining' => $remaining,
            'total' => $remaining + $filled,
            'group' => $group,
        ]);
    }

    public function fillModels(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_lang' => 'required|string|size:2',
            'target_lang' => 'required|string|size:2',
            'limit' => 'nullable|integer|min:1|max:' . self::MAX_LIMIT,
        ]);

        $sourceLang = $validated['source_lang'];
        $targetLang = $validated['target_lang'];
        $limit = $validated['limit'] ?? self::DEFAULT_LIMIT;

        if ($sourceLang === $targetLang) {
            return response()->json(['error' => 'Source and target languages must differ'], 422);
        }

        $modelTranslations = $this->getMissingModelTranslations($sourceLang, $targetLang, $limit);

        if (empty($modelTranslations)) {
            return response()->json([
                'filled' => 0,
                'remaining' => 0,
                'total' => 0,
                'message' => 'No missing model translations found',
            ]);
        }

        $texts = array_column($modelTranslations, 'value');

        $translations = $this->batchTranslateWithAi($texts, $sourceLang, $targetLang);

        if (empty($translations)) {
            return response()->json(['error' => 'AI translation failed — check AI settings'], 500);
        }

        $filled = 0;
        foreach ($modelTranslations as $i => $item) {
            $translated = $translations[$i] ?? null;
            if ($translated === null || trim($translated) === '' || trim($translated) === $item['value']) {
                continue;
            }

            $this->updateModelJsonColumn(
                $item['table'],
                $item['id'],
                $item['column'],
                $targetLang,
                trim($translated)
            );
            $filled++;
        }

        $remaining = $this->countMissingModelTranslations($sourceLang, $targetLang);

        return response()->json([
            'filled' => $filled,
            'remaining' => $remaining,
            'total' => $remaining + $filled,
        ]);
    }

    protected function getMissingUiKeys(string $sourceLang, string $targetLang, ?string $group, int $limit): array
    {
        $query = DB::table('translations as s')
            ->select('s.key', 's.group', 's.value')
            ->where('s.language_code', $sourceLang)
            ->whereNotExists(function ($q) use ($targetLang) {
                $q->select(DB::raw(1))
                    ->from('translations as t')
                    ->whereColumn('t.key', 's.key')
                    ->where('t.language_code', $targetLang)
                    ->whereColumn('t.group', 's.group');
            });

        if ($group) {
            $query->where('s.group', $group);
        }

        return $query->limit($limit)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    protected function countMissingUiKeys(string $sourceLang, string $targetLang, ?string $group): int
    {
        $query = DB::table('translations as s')
            ->where('s.language_code', $sourceLang)
            ->whereNotExists(function ($q) use ($targetLang) {
                $q->select(DB::raw(1))
                    ->from('translations as t')
                    ->whereColumn('t.key', 's.key')
                    ->where('t.language_code', $targetLang)
                    ->whereColumn('t.group', 's.group');
            });

        if ($group) {
            $query->where('s.group', $group);
        }

        return $query->count();
    }

    protected function getMissingModelTranslations(string $sourceLang, string $targetLang, int $limit): array
    {
        $tables = [
            ['table' => 'species', 'column' => 'name_json', 'fallback' => 'name'],
            ['table' => 'species', 'column' => 'description_json', 'fallback' => 'description'],
            ['table' => 'breeds', 'column' => 'name_json', 'fallback' => 'name'],
            ['table' => 'breeds', 'column' => 'description_json', 'fallback' => 'description'],
            ['table' => 'geofences', 'column' => 'name_json', 'fallback' => 'name'],
            ['table' => 'animal_groups', 'column' => 'name_json', 'fallback' => 'name'],
            ['table' => 'animal_groups', 'column' => 'description_json', 'fallback' => 'description'],
            ['table' => 'subscription_tiers', 'column' => 'name_json', 'fallback' => 'name'],
            ['table' => 'subscription_tiers', 'column' => 'description_json', 'fallback' => 'description'],
        ];

        $results = [];
        $sourceLocale = $sourceLang;
        $targetLocale = $targetLang;

        foreach ($tables as $tbl) {
            $rows = DB::table($tbl['table'])
                ->select('id', $tbl['fallback'], $tbl['column'])
                ->get();

            foreach ($rows as $row) {
                if (count($results) >= $limit) {
                    break 2;
                }

                $json = json_decode($row->{$tbl['column']} ?? '{}', true);
                $value = $row->{$tbl['fallback']};

                if (empty($value) || isset($json[$targetLocale])) {
                    continue;
                }

                $results[] = [
                    'table' => $tbl['table'],
                    'id' => $row->id,
                    'column' => $tbl['column'],
                    'value' => $value,
                ];
            }

            if (count($results) >= $limit) {
                break;
            }
        }

        return array_slice($results, 0, $limit);
    }

    protected function countMissingModelTranslations(string $sourceLang, string $targetLang): int
    {
        $tables = [
            ['table' => 'species', 'column' => 'name_json', 'fallback' => 'name'],
            ['table' => 'species', 'column' => 'description_json', 'fallback' => 'description'],
            ['table' => 'breeds', 'column' => 'name_json', 'fallback' => 'name'],
            ['table' => 'breeds', 'column' => 'description_json', 'fallback' => 'description'],
            ['table' => 'geofences', 'column' => 'name_json', 'fallback' => 'name'],
            ['table' => 'animal_groups', 'column' => 'name_json', 'fallback' => 'name'],
            ['table' => 'animal_groups', 'column' => 'description_json', 'fallback' => 'description'],
            ['table' => 'subscription_tiers', 'column' => 'name_json', 'fallback' => 'name'],
            ['table' => 'subscription_tiers', 'column' => 'description_json', 'fallback' => 'description'],
        ];

        $count = 0;
        $targetLocale = $targetLang;

        foreach ($tables as $tbl) {
            $rows = DB::table($tbl['table'])
                ->select('id', $tbl['fallback'], $tbl['column'])
                ->get();

            foreach ($rows as $row) {
                $json = json_decode($row->{$tbl['column']} ?? '{}', true);
                $value = $row->{$tbl['fallback']};

                if (empty($value) || isset($json[$targetLocale])) {
                    continue;
                }

                $count++;
            }
        }

        return $count;
    }

    protected function updateModelJsonColumn(string $table, int $id, string $column, string $locale, string $translatedValue): void
    {
        $allowedTables = ['species', 'breeds', 'geofences', 'animal_groups', 'subscription_tiers'];
        $allowedColumns = ['name_json', 'description_json'];

        if (!in_array($table, $allowedTables, true) || !in_array($column, $allowedColumns, true)) {
            return;
        }

        $path = '$."' . $locale . '"';
        $encoded = json_encode($translatedValue, JSON_UNESCAPED_UNICODE);

        DB::statement("UPDATE {$table} SET {$column} = JSON_SET(COALESCE({$column}, '{}'), ?, CAST(? AS JSON)) WHERE id = ?", [
            $path,
            $encoded,
            $id,
        ]);
    }

    protected function extractGroup(string $key): string
    {
        $parts = explode('.', $key, 2);
        return $parts[0] ?? 'general';
    }

    protected function batchTranslateWithAi(array $texts, string $sourceLang, string $targetLang): array
    {
        if (empty($texts)) return [];

        $aiSettings = $this->getAiSettings();
        if ($aiSettings['provider'] === 'disabled' || empty($aiSettings['api_key'])) {
            return [];
        }

        $indexed = [];
        foreach ($texts as $i => $text) {
            $indexed[] = "{$i}: {$text}";
        }

        $joined = implode("\n", $indexed);
        $prompt = "Translate each of the following " . count($texts) . " {$sourceLang} words/phrases to {$targetLang}. Return ONLY a valid JSON object where keys are the line numbers and values are the translated strings. No explanations, no formatting, no markdown.\n\n{$joined}";

        try {
            $result = match ($aiSettings['provider']) {
                'groq' => $this->callGroq($aiSettings['api_key'], $aiSettings['model'], $prompt),
                'gemini' => $this->callGemini($aiSettings['api_key'], $aiSettings['model'], $prompt),
                'openai' => $this->callOpenAI($aiSettings['api_key'], $aiSettings['model'], $prompt),
                default => null,
            };

            if ($result === null) return [];

            $parsed = json_decode($result, true);
            if (!is_array($parsed)) {
                $parsed = $this->parseLineResponse($result, count($texts));
            }

            $translations = [];
            for ($i = 0; $i < count($texts); $i++) {
                $translations[] = $parsed[(string)$i] ?? $parsed[$i] ?? null;
            }

            return $translations;
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function parseLineResponse(string $response, int $count): array
    {
        $lines = explode("\n", trim($response));
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^(\d+)[:\-.]?\s*(.+)$/', $line, $m)) {
                $result[$m[1]] = trim($m[2]);
            }
        }

        return $result;
    }

    protected function getAiSettings(): array
    {
        $settings = DB::table('settings')
            ->where('key', 'like', 'ai_%')
            ->pluck('value', 'key')
            ->toArray();

        $provider = $settings['ai_provider'] ?? 'disabled';
        $apiKey = $settings['ai_api_key'] ?? null;

        if ($provider !== 'disabled' && empty($apiKey)) {
            $apiKey = config('services.groq.api_key');
        }

        $defaultModels = [
            'groq' => 'llama-3.3-70b-versatile',
            'gemini' => 'gemini-2.0-flash',
            'openai' => 'gpt-4o',
        ];

        $model = $settings['ai_model'] ?? ($defaultModels[$provider] ?? 'llama-3.3-70b-versatile');

        return [
            'provider' => $provider,
            'api_key' => $apiKey ?? '',
            'model' => $model,
        ];
    }

    protected function callGroq(string $apiKey, string $model, string $prompt): ?string
    {
        $response = \Illuminate\Support\Facades\Http::timeout(120)->withoutVerifying()
            ->withToken($apiKey)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.1,
                'max_tokens' => 8192,
            ]);

        if ($response->failed()) return null;
        return $response->json()['choices'][0]['message']['content'] ?? null;
    }

    protected function callGemini(string $apiKey, string $model, string $prompt): ?string
    {
        $response = \Illuminate\Support\Facades\Http::timeout(120)->withoutVerifying()
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 8192],
                ]
            );

        if ($response->failed()) return null;
        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    protected function callOpenAI(string $apiKey, string $model, string $prompt): ?string
    {
        $response = \Illuminate\Support\Facades\Http::timeout(120)->withoutVerifying()
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.1,
                'max_tokens' => 8192,
            ]);

        if ($response->failed()) return null;
        return $response->json()['choices'][0]['message']['content'] ?? null;
    }
}
