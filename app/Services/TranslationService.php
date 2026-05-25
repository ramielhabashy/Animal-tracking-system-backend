<?php

namespace App\Services;

use App\Services\Translation\AiDriver;
use App\Services\Translation\DeepLDriver;
use App\Services\Translation\GoogleDriver;
use Illuminate\Support\Facades\DB;

class TranslationService
{
    private DeepLDriver $deepL;
    private GoogleDriver $google;
    private AiDriver $ai;

    private const CACHE_TTL_HOURS = 24;

    public function __construct()
    {
        $this->deepL = new DeepLDriver();
        $this->google = new GoogleDriver();
        $this->ai = new AiDriver();
    }

    public function translate(string $text, string $targetLang, ?string $sourceLang = null): ?string
    {
        if (empty(trim($text))) {
            return $text;
        }

        $sourceLang = $sourceLang ?: $this->detectSourceLang($text);
        if ($sourceLang === $targetLang) {
            return $text;
        }

        $textHash = md5($text);

        $cached = $this->getFromCache($textHash, $sourceLang, $targetLang);
        if ($cached !== null) {
            return $cached;
        }

        $translated = $this->translateWithDriver($text, $targetLang, $sourceLang);
        if ($translated !== null) {
            $this->storeInCache($textHash, $sourceLang, $targetLang, $translated);
        }

        return $translated;
    }

    public function translateBatch(array $texts, string $targetLang, ?string $sourceLang = null): array
    {
        $results = [];
        foreach ($texts as $text) {
            $results[] = [
                'original' => $text,
                'translated' => $this->translate($text, $targetLang, $sourceLang),
            ];
        }
        return $results;
    }

    private function translateWithDriver(string $text, string $targetLang, ?string $sourceLang): ?string
    {
        if ($this->deepL->supports($targetLang)) {
            $result = $this->deepL->translate($text, $targetLang, $sourceLang);
            if ($result !== null) {
                return $result;
            }
        }

        $result = $this->google->translate($text, $targetLang, $sourceLang);
        if ($result !== null) {
            return $result;
        }

        return $this->ai->translate($text, $targetLang, $sourceLang);
    }

    private function detectSourceLang(string $text): ?string
    {
        return null;
    }

    private function getFromCache(string $hash, ?string $sourceLang, string $targetLang): ?string
    {
        $row = DB::table('translation_cache')
            ->where('source_text_hash', $hash)
            ->where('source_lang', $sourceLang ?? 'auto')
            ->where('target_lang', $targetLang)
            ->where('expires_at', '>', now())
            ->first();

        return $row ? $row->translated_text : null;
    }

    private function storeInCache(string $hash, ?string $sourceLang, string $targetLang, string $translatedText): void
    {
        DB::table('translation_cache')->updateOrInsert(
            [
                'source_text_hash' => $hash,
                'source_lang' => $sourceLang ?? 'auto',
                'target_lang' => $targetLang,
            ],
            [
                'translated_text' => $translatedText,
                'expires_at' => now()->addHours(self::CACHE_TTL_HOURS),
                'updated_at' => now(),
            ]
        );
    }
}
