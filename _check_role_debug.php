<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=ra_animal_tracking;charset=utf8mb4', 'root', '');

echo "=== zeno model_has_roles ===" . PHP_EOL;
$stmt = $pdo->query("SELECT * FROM model_has_roles WHERE model_id = 141");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);

echo PHP_EOL . "=== roles table ===" . PHP_EOL;
$stmt2 = $pdo->query("SELECT * FROM roles ORDER BY id");
$roles = $stmt2->fetchAll(PDO::FETCH_ASSOC);
print_r($roles);

echo PHP_EOL . "=== all model_has_roles (sample) ===" . PHP_EOL;
$stmt3 = $pdo->query("SELECT mhr.*, u.email FROM model_has_roles mhr JOIN users u ON u.id = mhr.model_id LIMIT 10");
$all = $stmt3->fetchAll(PDO::FETCH_ASSOC);
print_r($all);

echo PHP_EOL . "=== khalid model_has_roles ===" . PHP_EOL;
$stmt4 = $pdo->query("SELECT * FROM model_has_roles WHERE model_id = 2");
$khalidRoles = $stmt4->fetchAll(PDO::FETCH_ASSOC);
print_r($khalidRoles);
