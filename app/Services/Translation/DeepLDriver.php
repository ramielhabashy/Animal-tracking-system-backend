<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class DeepLDriver
{
    private const API_URL = 'https://api-free.deepl.com/v2/translate';

    private const SUPPORTED_LOCALES = ['en', 'ar', 'ur'];

    private const LOCALE_MAP = [
        'en' => 'EN-US',
        'ar' => 'AR',
        'ur' => 'UR',
    ];

    public function supports(string $targetLang): bool
    {
        return in_array($targetLang, self::SUPPORTED_LOCALES, true);
    }

    public function translate(string $text, string $targetLang, ?string $sourceLang = null): ?string
    {
        $apiKey = DB::table('settings')->where('key', 'translation_deepl_api_key')->value('value');
        if (!$apiKey) {
            return null;
        }

        try {
            $payload = [
                'text' => [$text],
                'target_lang' => self::LOCALE_MAP[$targetLang] ?? strtoupper($targetLang),
            ];
            if ($sourceLang) {
                $payload['source_lang'] = self::LOCALE_MAP[$sourceLang] ?? strtoupper($sourceLang);
            }

            $response = Http::withHeaders([
                'Authorization' => 'DeepL-Auth-Key ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post(self::API_URL, $payload);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            return $data['translations'][0]['text'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
