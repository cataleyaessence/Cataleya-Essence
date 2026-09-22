<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

// Redirect to home.php (main logged-in page)
header('Location: home.php');
exit;
?>
