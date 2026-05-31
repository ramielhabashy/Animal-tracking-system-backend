<?php
function checkDb($dbName, $label) {
    echo "=== $label ($dbName) ===" . PHP_EOL;
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=$dbName;charset=utf8mb4", 'root', '');
        
        $users = ['zeno@oasis.com', 'khalid@oasis.com', 'admin@oasis.com', 'fatima@oasis.com'];
        foreach ($users as $email) {
            $stmt = $pdo->prepare("SELECT id, email, name, managed_by FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $stmt2 = $pdo->prepare("SELECT role_id FROM model_has_roles WHERE model_id = ? AND model_type = 'App\Models\User'");
                $stmt2->execute([$user['id']]);
                $roleId = $stmt2->fetchColumn();
                echo "  $email: ID={$user['id']}, managed_by={$user['managed_by']}, role_id=$roleId" . PHP_EOL;
            } else {
                echo "  $email: NOT FOUND" . PHP_EOL;
            }
        }
        
        $stmt = $pdo->query("SELECT owner_id, COUNT(*) as cnt FROM animals GROUP BY owner_id ORDER BY owner_id");
        echo "  Animals by owner:" . PHP_EOL;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "    owner_id={$row['owner_id']}: {$row['cnt']} animals" . PHP_EOL;
        }
        
        echo "  Total animals: " . $pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn() . PHP_EOL;
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;
}

checkDb('ra_animal_tracking', 'ra_animal_tracking');
checkDb('oasis_staging', 'oasis_staging');
