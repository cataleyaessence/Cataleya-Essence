<?php
// auth/set_new_password.php – called via auth.php?action=set-password
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters.']);
    exit;
}
if ($password !== $confirm) {
    echo json_encode(['success' => false, 'error' => 'Passwords do not match.']);
    exit;
}

// Check if reset is verified
if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please verify your code first.']);
    exit;
}

$user_id = $_SESSION['reset_user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please request a new reset.']);
    exit;
}

// Update password
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$stmt->execute([$hash, $user_id]);

// Clear reset session
unset($_SESSION['reset_user_id']);
unset($_SESSION['reset_email']);
unset($_SESSION['reset_verified']);

echo json_encode([
    'success' => true,
    'message' => 'Password updated successfully!',
    'redirect' => '../Php/signin.php'
]);
?>