<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=ra_animal_tracking;charset=utf8mb4', 'root', '');
    $stmt = $pdo->query("SELECT id, email, name FROM users WHERE email = 'zeno@oasis.com'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        print_r($user);
        $stmt2 = $pdo->query("SELECT model_id, role_id FROM model_has_roles WHERE model_id = " . $user['id']);
        $roleIds = $stmt2->fetchAll(PDO::FETCH_COLUMN, 1);
        echo 'Role IDs: ' . implode(', ', $roleIds) . PHP_EOL;
    } else {
        echo 'zeno@oasis.com not found in ra_animal_tracking' . PHP_EOL;
    }
    $stmt3 = $pdo->query("SELECT id, email, name FROM users WHERE email = 'khalid@oasis.com'");
    $khalid = $stmt3->fetch(PDO::FETCH_ASSOC);
    if ($khalid) {
        print_r($khalid);
    } else {
        echo 'khalid@oasis.com not found' . PHP_EOL;
    }
    $stmt4 = $pdo->query("SELECT COUNT(*) FROM users");
    echo 'Total users: ' . $stmt4->fetchColumn() . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
