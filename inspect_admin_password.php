<?php
require_once __DIR__ . '/config/database.php';
$email = 'cataleyaessence23@gmail.com';
$password = 'Admincataleya';
try {
    $stmt = $pdo->prepare('SELECT id, email, is_admin, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo "NO USER\n";
        exit(1);
    }
    echo "USER ID=" . $row['id'] . " email=" . $row['email'] . " is_admin=" . $row['is_admin'] . "\n";
    echo password_verify($password, $row['password_hash']) ? "PASSWORD MATCH\n" : "PASSWORD FAIL\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
