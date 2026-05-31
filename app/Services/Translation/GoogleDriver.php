<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class GoogleDriver
{
    private const API_URL = 'https://translation.googleapis.com/language/translate/v2';

    public function translate(string $text, string $targetLang, ?string $sourceLang = null): ?string
    {
        $apiKey = DB::table('settings')->where('key', 'translation_google_api_key')->value('value');
        if (!$apiKey) {
            return null;
        }

        try {
            $payload = [
                'q' => $text,
                'target' => $targetLang,
                'format' => 'text',
            ];
            if ($sourceLang) {
                $payload['source'] = $sourceLang;
            }

            $response = Http::get(self::API_URL, array_merge($payload, [
                'key' => $apiKey,
            ]));

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            return $data['data']['translations'][0]['translatedText'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
