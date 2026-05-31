<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=ra_animal_tracking;charset=utf8mb4', 'root', '');
    $stmt = $pdo->query("SELECT id, name FROM roles ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($roles);
    echo PHP_EOL;
    $stmt2 = $pdo->query("SELECT id, email, managed_by FROM users WHERE email IN ('zeno@oasis.com', 'khalid@oasis.com', 'fatima@oasis.com')");
    $users = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
