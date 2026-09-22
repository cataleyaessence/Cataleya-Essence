<?php
// config/database.php
$host = 'sql109.infinityfree.com';
$dbname = 'if0_42984699_Cataleya';
$username = 'if0_42984699';
$password = 'Admincataleya';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Store PHP sessions in the project so Laragon's PHP process does not depend
// on a protected global temp folder. Every page loads this bootstrap before
// accessing the session, keeping sign-in and admin redirects in one session.
if (session_status() === PHP_SESSION_NONE) {
    $sessionDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
    if (!is_dir($sessionDirectory) && !@mkdir($sessionDirectory, 0700, true) && !is_dir($sessionDirectory)) {
        throw new RuntimeException('Unable to create the application session directory.');
    }
    if (!is_writable($sessionDirectory)) {
        throw new RuntimeException('The application session directory is not writable.');
    }
    session_save_path($sessionDirectory);
    session_start();
}
?>
