<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AiDriver
{
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): ?string
    {
        $settings = $this->getSettings();
        if ($settings['provider'] === 'disabled' || empty($settings['api_key'])) {
            return null;
        }

        $enabled = DB::table('settings')->where('key', 'translation_ai_enabled')->value('value');
        if ($enabled !== '1' && $enabled !== 'true') {
            return null;
        }

        $sourceLang = $sourceLang ?: $this->detectSourceLang($text);
        $prompt = "Translate the following text from {$sourceLang} to {$targetLang}. Return ONLY the translated text, no quotes, no explanations, no formatting.\n\nText: {$text}";

        try {
            $result = match ($settings['provider']) {
                'groq' => $this->callGroq($settings['api_key'], $settings['model'], $prompt),
                'gemini' => $this->callGemini($settings['api_key'], $settings['model'], $prompt),
                'openai' => $this->callOpenAI($settings['api_key'], $settings['model'], $prompt),
                default => null,
            };

            if ($result === null || trim($result) === '' || trim($result) === $text) {
                return null;
            }

            return trim($result);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getSettings(): array
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

    protected function detectSourceLang(string $text): string
    {
        return 'en';
    }

    protected function callGroq(string $apiKey, string $model, string $prompt): ?string
    {
        $response = Http::timeout(30)->withoutVerifying()->withToken($apiKey)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.1,
            'max_tokens' => 2048,
        ]);

        if ($response->failed()) return null;

        return $response->json()['choices'][0]['message']['content'] ?? null;
    }

    protected function callGemini(string $apiKey, string $model, string $prompt): ?string
    {
        $response = Http::timeout(30)->withoutVerifying()->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 2048],
            ]
        );

        if ($response->failed()) return null;

        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    protected function callOpenAI(string $apiKey, string $model, string $prompt): ?string
    {
        $response = Http::timeout(30)->withoutVerifying()->withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.1,
            'max_tokens' => 2048,
        ]);

        if ($response->failed()) return null;

        return $response->json()['choices'][0]['message']['content'] ?? null;
    }
}
