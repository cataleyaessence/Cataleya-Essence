<?php
require_once __DIR__ . '/config/database.php';
try {
    $stmt = $pdo->prepare('SELECT id, email, is_admin, password_hash FROM users WHERE email = ?');
    $stmt->execute(['cataleyaessence23@gmail.com']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
