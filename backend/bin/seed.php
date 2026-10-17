<?php
// Usage: docker compose exec backend php bin/seed.php   (DEV ONLY)
require __DIR__ . '/../vendor/autoload.php';

$pdo = App\Database::pdo();
$users = [
    ['Admin', 'admin@securedesk.test', 'admin'],
    ['Agent', 'agent@securedesk.test', 'agent'],
    ['Alice', 'alice@securedesk.test', 'user'],
];
$stmt = $pdo->prepare('INSERT IGNORE INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
foreach ($users as [$n, $e, $r]) {
    $stmt->execute([$n, $e, password_hash('Password123!', PASSWORD_DEFAULT), $r]);
}
echo "Seeded demo users (password: Password123!)\n";
