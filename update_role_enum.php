<?php
$host = 'localhost';
$db   = 'oasis_staging';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // First update existing records that might have old roles
    $pdo->exec("UPDATE users SET role = 'Veterinarian' WHERE role = 'Doctor'");
    $pdo->exec("UPDATE users SET role = 'Veterinarian' WHERE role = 'Manager'");
    
    // Then modify the column
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'Owner', 'Veterinarian', 'Shepherd') DEFAULT 'Owner'");
    
    echo "Successfully updated users table role column!\n";
    
    // Show current roles
    $stmt = $pdo->query("SELECT DISTINCT role FROM users");
    echo "Current roles in database: ";
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
    
} catch (\PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
