<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Database Charset Diagnostics ===\n\n";

echo "1. Connection charset:\n";
$charset = DB::select("SHOW VARIABLES LIKE 'character_set%'");
foreach ($charset as $row) {
    printf("  %s: %s\n", $row->Variable_name, $row->Value);
}

echo "\n2. Database charset:\n";
$dbName = DB::select("SELECT DATABASE() as db")[0]->db;
$dbStatus = DB::select("SHOW CREATE DATABASE `{$dbName}`");
echo "  " . $dbStatus[0]->{"Create Database"} . "\n";

echo "\n3. Languages table charset:\n";
$langStatus = DB::select("SHOW CREATE TABLE languages");
echo "  " . $langStatus[0]->{"Create Table"} . "\n\n";

echo "4. Languages data (hex dump):\n";
$langs = DB::table('languages')->get();
foreach ($langs as $l) {
    $hex = bin2hex($l->native_name);
    $binary = $l->native_name;
    $escaped = '';
    for ($i = 0; $i < strlen($binary); $i++) {
        $escaped .= '\\x' . dechex(ord($binary[$i]));
    }
    printf("  %s: name=%s, native=%s, hex=%s\n", $l->code, $l->name, $l->native_name, $hex);
}

echo "\n5. Sample Arabic translations (hex dump, first 5):\n";
$arTrans = DB::table('translations')->where('language_code', 'ar')->where('group', 'common')->limit(5)->get();
foreach ($arTrans as $t) {
    $hex = bin2hex($t->value);
    printf("  [%s.%s] value=%s hex=%s\n", $t->group, $t->key, $t->value, $hex);
}

echo "\n6. Try force-updating with PDO:\n";
$pdo = DB::connection()->getPdo();
$pdo->exec("SET NAMES utf8mb4");
$stmt = $pdo->prepare("UPDATE languages SET native_name = ? WHERE code = ?");
$stmt->execute(['العربية', 'ar']);
$stmt->execute(['اردو', 'ur']);
$stmt->execute(['Euskara', 'eu']);

$langs = DB::table('languages')->get();
echo "  After PDO update:\n";
foreach ($langs as $l) {
    $hex = bin2hex($l->native_name);
    printf("  %s: native=%s hex=%s\n", $l->code, $l->native_name, $hex);
}

echo "\n7. Force-update translations with PDO:\n";
$pdo->prepare("UPDATE translations SET value = ? WHERE language_code = ? AND `group` = ? AND `key` = ?");
$test = $pdo->prepare("INSERT INTO translations (language_code, `group`, `key`, value, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE value = VALUES(value)");

// Read JS files and force-insert
$jsDir = __DIR__ . '/../../frontend/src/i18n';
$jsFiles = ['ar', 'ur', 'eu', 'en'];
foreach ($jsFiles as $lang) {
    $file = "{$jsDir}/{$lang}.js";
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $jsContent = file_get_contents($file);
    echo "  {$lang}.js: " . strlen($jsContent) . " bytes\n";
    
    // Quick hex check of first Arabic from file
    if ($lang === 'ar') {
        preg_match("/dashboard:\s*'([^']+)'/", $jsContent, $m);
        if (isset($m[1])) {
            $hex = bin2hex($m[1]);
            echo "    first ar value hex: {$hex}\n";
        }
    }
}

echo "\n=== Done ===\n";
