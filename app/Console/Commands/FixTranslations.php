<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FixTranslations extends Command
{
    protected $signature = 'fix:translations';
    protected $description = 'Re-insert translations from JS files with proper UTF-8 encoding';

    public function handle()
    {
        $this->info('Setting UTF-8 connection...');
        DB::statement("SET NAMES 'utf8mb4'");
        DB::statement("SET CHARACTER SET utf8mb4");

        $jsDir = base_path('../frontend/src/i18n');

        $langs = ['ar', 'ur', 'eu', 'en'];

        foreach ($langs as $lang) {
            $file = "{$jsDir}/{$lang}.js";
            if (!File::exists($file)) {
                $this->warn("File not found: {$file}");
                continue;
            }

            $content = File::get($file);
            $this->info("Processing {$lang}.js (" . strlen($content) . " bytes)");

            $translations = $this->parseJsTranslations($content);
            $count = 0;

            foreach ($translations as $group => $keys) {
                foreach ($keys as $key => $value) {
                    if ($value === '' || $value === null) continue;

                    DB::table('translations')->updateOrInsert(
                        [
                            'language_code' => $lang,
                            'group' => $group,
                            'key' => $key,
                        ],
                        [
                            'value' => $value,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                    $count++;
                }
            }

            $this->info("  Inserted/updated {$count} translations for {$lang}");
        }

        $this->fixLanguageNames();

        $this->info('All translations fixed successfully!');
    }

    private function fixLanguageNames(): void
    {
        DB::table('languages')->where('code', 'ar')->update([
            'native_name' => 'العربية',
            'name' => 'Arabic',
            'direction' => 'rtl',
        ]);
        DB::table('languages')->where('code', 'ur')->update([
            'native_name' => 'اردو',
            'name' => 'Urdu',
            'direction' => 'rtl',
        ]);
        DB::table('languages')->where('code', 'eu')->update([
            'native_name' => 'Euskara',
            'name' => 'Basque',
            'direction' => 'ltr',
        ]);
        DB::table('languages')->where('code', 'en')->update([
            'native_name' => 'English',
            'name' => 'English',
            'direction' => 'ltr',
        ]);
    }

    private function parseJsTranslations(string $content): array
    {
        $content = preg_replace('/^export\s+default\s*/m', '', $content);
        $content = trim($content);
        $content = rtrim($content, ';');
        $content = trim($content);

        return $this->parseObjectBody($content, '');
    }

    private function parseObjectBody(string $body, string $prefix): array
    {
        $result = [];

        $body = trim($body);
        if (!str_starts_with($body, '{') || !str_ends_with($body, '}')) {
            return $result;
        }
        $body = trim(substr($body, 1, -1));

        $lines = explode("\n", $body);
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            $line = trim($lines[$i]);
            if ($line === '' || $line === '}' || $line === '},') continue;

            if (preg_match('/^(\w+)\s*:\s*\{$/', $line, $m)) {
                $key = $m[1];
                $block = '';
                $depth = 1;
                while ($i + 1 < $count && $depth > 0) {
                    $i++;
                    $nextLine = $lines[$i];
                    $openCount = substr_count($nextLine, '{');
                    $closeCount = substr_count($nextLine, '}');
                    $depth += $openCount - $closeCount;
                    if ($depth > 0) {
                        $block .= $nextLine . "\n";
                    }
                }
                $newPrefix = $prefix ? "{$prefix}.{$key}" : $key;
                $subResult = $this->parseObjectBody('{' . $block . '}', $newPrefix);
                foreach ($subResult as $g => $kvs) {
                    if (!isset($result[$g])) $result[$g] = [];
                    foreach ($kvs as $k => $v) {
                        $result[$g][$k] = $v;
                    }
                }
                continue;
            }

            if (preg_match("/^(\w+)\s*:\s*'(.*)'\s*,?$/", $line, $m)) {
                $key = $m[1];
                $value = str_replace(["\\'", "\\\\"], ["'", "\\"], $m[2]);
                if ($value !== '' && $value !== null) {
                    $group = $prefix ?: '';
                    if (!isset($result[$group])) $result[$group] = [];
                    $result[$group][$key] = $value;
                }
            }
        }

        return $result;
    }
}
