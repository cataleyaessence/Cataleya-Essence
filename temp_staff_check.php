<?php
$pdo = require 'config/database.php';
$stmt = $pdo->query('SHOW COLUMNS FROM staff');
foreach ($stmt as $col) {
    echo $col['Field'] . ' ' . $col['Type'] . ' ' . ($col['Null'] ? 'NULL' : 'NOT NULL') . "\n";
}
echo "---\n";
$stmt = $pdo->query('SELECT id, full_name, category, title, image_url, is_available, rating, total_reviews FROM staff LIMIT 5');
foreach ($stmt as $row) {
    echo implode(' | ', array_values($row)) . "\n";
}
