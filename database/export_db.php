<?php
/**
 * Standalone Database Export Script
 * Run: php export_db.php
 */

$host = '127.0.0.1';
$db   = 'oasis_staging';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$outputFile = __DIR__ . '/oasis_staging_v0.1.sql';

echo "Connecting to MySQL...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "Connected. Exporting tables...\n";

$sql = "";
$sql .= "-- Database Export: $db\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// Get all tables
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "Exporting: $table\n";
    
    // Drop table
    $sql .= "\nDROP TABLE IF EXISTS `$table`;\n";
    
    // Create table
    $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
    $create = $stmt->fetch(PDO::FETCH_ASSOC);
    $sql .= $create['Create Table'] . ";\n";
    
    // Insert data
    $stmt = $pdo->query("SELECT * FROM `$table`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $values = array_map(function($val) use ($pdo) {
            if (is_null($val)) return 'NULL';
            return $pdo->quote($val);
        }, $row);
        $sql .= "INSERT INTO `$table` (" . implode(', ', array_keys($row)) . ") VALUES (" . implode(', ', $values) . ");\n";
    }
}

$sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

file_put_contents($outputFile, $sql);
echo "Done! Saved to: $outputFile\n";
echo "Size: " . filesize($outputFile) . " bytes\n";