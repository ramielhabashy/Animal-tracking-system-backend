<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Setting UTF-8 connection...\n";
DB::statement("SET NAMES 'utf8mb4'");
DB::statement("SET CHARACTER SET utf8mb4");

$jsDir = __DIR__ . '/../../frontend/src/i18n';

$langs = ['ar', 'ur', 'eu', 'en'];

foreach ($langs as $lang) {
    $file = "{$jsDir}/{$lang}.js";
    if (!file_exists($file)) {
        echo "File not found: {$file}\n";
        continue;
    }

    $content = file_get_contents($file);
    echo "Processing {$lang}.js (" . strlen($content) . " bytes)\n";

    $translations = parseJsTranslations($content);
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

    echo "  Inserted/updated {$count} translations for {$lang}\n";
}

// Fix language native names
echo "\nFixing language native names...\n";
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

echo "\nAll translations fixed successfully!\n";

// Verify by displaying count per language
$langCounts = DB::table('translations')
    ->select('language_code', DB::raw('COUNT(*) as count'))
    ->groupBy('language_code')
    ->get();

echo "\nTranslation counts:\n";
foreach ($langCounts as $lc) {
    echo "  {$lc->language_code}: {$lc->count}\n";
}

// Verify languages table
$langs = DB::table('languages')->get();
echo "\nLanguages:\n";
foreach ($langs as $l) {
    $hex = bin2hex($l->native_name);
    echo "  {$l->code}: {$l->native_name} (direction: {$l->direction}) [hex: {$hex}]\n";
}

function parseJsTranslations(string $content): array
{
    $content = preg_replace('/^export\s+default\s*/m', '', $content);
    $content = trim($content);
    $content = rtrim($content, ';');
    $content = trim($content);

    return parseObjectBody($content, '');
}

function parseObjectBody(string $body, string $prefix): array
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
            $subResult = parseObjectBody('{' . $block . '}', $newPrefix);
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
