<?php
// Direct fix using raw PDO with explicit utf8mb4 charset in DSN
// Uses line-by-line parser (no regex) for reliability

$host = 'localhost';
$db = 'ra_animal_tracking';
$user = 'root';
$pass = '';

echo "=== Direct MySQL UTF-8 Fix ===\n\n";

$dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Verify charset
$stmt = $pdo->query("SHOW VARIABLES LIKE 'character_set_connection'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "1. character_set_connection: " . $row['Value'] . "\n";

$pdo->exec("SET NAMES utf8mb4");
$pdo->exec("SET CHARACTER SET utf8mb4");

echo "\n2. Fixing language native names...\n";
$stmt = $pdo->prepare("UPDATE languages SET native_name = ? WHERE code = ?");
$stmt->execute(['العربية', 'ar']);
$stmt->execute(['اردو', 'ur']);
$stmt->execute(['Euskara', 'eu']);
$stmt->execute(['English', 'en']);

// Verify language names
$stmt = $pdo->query("SELECT code, native_name FROM languages");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $hex = bin2hex($row['native_name']);
    echo "   {$row['code']}: '{$row['native_name']}' hex={$hex}\n";
}

echo "\n3. Deleting all AR, UR, EU translations...\n";
$pdo->exec("DELETE FROM translations WHERE language_code IN ('ar', 'ur', 'eu')");
echo "   Deleted.\n";

echo "\n4. Parsing JS files and inserting translations...\n";
$jsDir = __DIR__ . '/../../frontend/src/i18n';

function parseJsFile($filePath) {
    $content = file_get_contents($filePath);
    // Normalize line endings
    $content = str_replace("\r\n", "\n", $content);
    
    $result = []; // group => [ key => value ]
    $currentGroup = null;
    
    foreach (explode("\n", $content) as $line) {
        $line = trim($line);
        
        // Skip empty lines, import/export statements, braces, and comments
        if ($line === '' || $line === '{' || $line === '}' || $line === '};' || 
            $line === '},' || $line === '},;' ||
            str_starts_with($line, 'import ') || 
            str_starts_with($line, 'export default')) {
            continue;
        }
        
        // Check if this is a group header:  groupName: {
        if (preg_match('/^(\w+)\s*:\s*\{$/', $line, $m)) {
            $currentGroup = $m[1];
            if (!isset($result[$currentGroup])) {
                $result[$currentGroup] = [];
            }
            continue;
        }
        
        // Check if this is a key-value pair:  key: 'value',
        if ($currentGroup && preg_match("/^(\w+)\s*:\s*'(.*)'\s*,?$/", $line, $m)) {
            $key = $m[1];
            $value = $m[2];
            // Unescape JS escapes
            $value = str_replace(["\\'", "\\\\"], ["'", "\\"], $value);
            $result[$currentGroup][$key] = $value;
        }
    }
    
    return $result;
}

foreach (['ar' => 'Arabic', 'ur' => 'Urdu', 'eu' => 'Basque'] as $code => $name) {
    $file = "{$jsDir}/{$code}.js";
    if (!file_exists($file)) {
        echo "   WARNING: {$file} not found!\n";
        continue;
    }
    
    $data = parseJsFile($file);
    $totalKeys = 0;
    foreach ($data as $group => $keys) {
        $totalKeys += count($keys);
    }
    echo "   {$code}.js: parsed {$totalKeys} keys in " . count($data) . " groups\n";
    
    $stmt = $pdo->prepare(
        "INSERT INTO translations (language_code, `group`, `key`, value, created_at, updated_at) 
         VALUES (?, ?, ?, ?, NOW(), NOW())"
    );
    
    $inserted = 0;
    foreach ($data as $group => $keys) {
        foreach ($keys as $key => $value) {
            if ($value === '' || $value === null) continue;
            $stmt->execute([$code, $group, $key, $value]);
            $inserted++;
        }
    }
    echo "   Inserted {$inserted} {$name} translations.\n";
    
    // Verify a sample
    $check = $pdo->prepare("SELECT value FROM translations WHERE language_code = ? LIMIT 1");
    $check->execute([$code]);
    $sample = $check->fetch(PDO::FETCH_ASSOC);
    if ($sample) {
        $hex = bin2hex($sample['value']);
        echo "   Sample value hex: {$hex}\n";
    }
}

echo "\n5. Final verification - translation counts:\n";
$stmt = $pdo->query("SELECT language_code, COUNT(*) as cnt FROM translations GROUP BY language_code ORDER BY language_code");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   {$row['language_code']}: {$row['cnt']} translations\n";
}

echo "\n6. Final verification - language native names:\n";
$stmt = $pdo->query("SELECT code, native_name, HEX(native_name) as hex FROM languages");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   {$row['code']}: '{$row['native_name']}' [{$row['hex']}]\n";
}

echo "\n=== DONE ===\n";
