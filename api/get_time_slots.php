<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format');
    }

    // Check if date is in the past
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Past date - no slots available'
        ]);
        exit;
    }

    // Get time slots for the specific date
    $stmt = $pdo->prepare("
        SELECT 
            ts.id,
            ts.slot_time,
            ts.display_time,
            COALESCE(dsa.status, 'available') as status,
            COALESCE(dsa.current_bookings, 0) as current_bookings,
            COALESCE(dsa.max_bookings, 1) as max_bookings
        FROM time_slots ts
        LEFT JOIN daily_slot_availability dsa ON ts.id = dsa.slot_id AND dsa.slot_date = ?
        WHERE ts.is_active = 1
        ORDER BY ts.sort_order ASC
    ");
    $stmt->execute([$date]);
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $timeSlots = [];
    $availableCount = 0;
    $bookedCount = 0;

    foreach ($slots as $slot) {
        $status = $slot['status'];
        
        // If no daily record exists, create one as available
        if ($slot['status'] === null) {
            $status = 'available';
            
            // Create daily slot availability record
            $insertStmt = $pdo->prepare("
                INSERT INTO daily_slot_availability (slot_date, slot_id, status, max_bookings, current_bookings)
                VALUES (?, ?, 'available', 1, 0)
                ON DUPLICATE KEY UPDATE status = 'available'
            ");
            $insertStmt->execute([$date, $slot['id']]);
        }

        $isAvailable = ($status === 'available' && $slot['current_bookings'] < $slot['max_bookings']);
        
        if ($isAvailable) {
            $availableCount++;
        } else {
            $bookedCount++;
        }

        $timeSlots[] = [
            'id' => $slot['id'],
            'time' => $slot['display_time'],
            'slot_time' => $slot['slot_time'],
            'status' => $isAvailable ? 'available' : 'booked',
            'bookings' => $slot['current_bookings'],
            'max_bookings' => $slot['max_bookings']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $timeSlots,
        'date' => $date,
        'available_count' => $availableCount,
        'booked_count' => $bookedCount
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
