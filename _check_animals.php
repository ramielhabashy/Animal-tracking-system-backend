<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=ra_animal_tracking;charset=utf8mb4', 'root', '');
    
    // Check animals for owner_id = 2 (khalid)
    $cols = $pdo->query("SHOW COLUMNS FROM animals")->fetchAll(PDO::FETCH_COLUMN, 0);
    echo 'Animal columns: ' . implode(', ', $cols) . PHP_EOL . PHP_EOL;
    $stmt = $pdo->query("SELECT id, name, owner_id FROM animals WHERE owner_id = 2 LIMIT 20");
    $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo 'Khalid animals (' . count($animals) . '):' . PHP_EOL;
    print_r($animals);
    
    // Check total animals count
    $stmt2 = $pdo->query("SELECT COUNT(*) FROM animals");
    echo 'Total animals: ' . $stmt2->fetchColumn() . PHP_EOL;
    
    // Check what owner_ids exist
    $stmt3 = $pdo->query("SELECT owner_id, COUNT(*) as cnt FROM animals GROUP BY owner_id ORDER BY cnt DESC LIMIT 10");
    $owners = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    echo 'Animals by owner:' . PHP_EOL;
    print_r($owners);
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
