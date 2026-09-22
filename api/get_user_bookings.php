<?php
error_reporting(0); // Suppress PHP warnings/errors from polluting JSON output
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Calculate date range for the month
$first_day = sprintf('%04d-%02d-01', $year, $month);
$last_day = date('Y-m-t', strtotime($first_day));

try {
    // Fetch user's bookings for the specified month
    $stmt = $pdo->prepare("
        SELECT b.booking_date, b.booking_time, b.status, s.name as service_name
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.user_id = ? AND b.booking_date >= ? AND b.booking_date <= ?
        ORDER BY b.booking_date, b.booking_time
    ");
    $stmt->execute([$user_id, $first_day, $last_day]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'bookings' => $bookings
    ]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
