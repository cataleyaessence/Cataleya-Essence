<?php
error_reporting(0);
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

if (!isset($_GET['booking_id'])) {
    echo json_encode(['error' => 'Booking ID required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['booking_id'];

try {
    $stmt = $pdo->prepare("
        SELECT b.id, b.booking_date, b.booking_time, b.status, b.total_amount, b.notes, b.created_at, b.updated_at,
               s.name as service_name, s.image_url as service_image, s.price as service_price,
               st.full_name as stylist_name, st.specialization as stylist_specialization
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        LEFT JOIN staff st ON b.staff_id = st.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['error' => 'Booking not found']);
        exit;
    }

    echo json_encode(['success' => true, 'booking' => $booking]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
