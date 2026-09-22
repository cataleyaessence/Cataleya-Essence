<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

    // Get the first and last day of the month
    $firstDay = mktime(0, 0, 0, $month, 1, $year);
    $lastDay = mktime(0, 0, 0, $month + 1, 0, $year);
    
    $startDate = date('Y-m-d', $firstDay);
    $endDate = date('Y-m-d', $lastDay);

    // Get daily slot availability for the month
    $stmt = $pdo->prepare("
        SELECT 
            dsa.slot_date,
            COUNT(CASE WHEN dsa.status = 'booked' THEN 1 END) as booked_count,
            COUNT(CASE WHEN dsa.status = 'filling' THEN 1 END) as filling_count,
            COUNT(CASE WHEN dsa.status = 'available' THEN 1 END) as available_count,
            COUNT(*) as total_slots
        FROM daily_slot_availability dsa
        WHERE dsa.slot_date BETWEEN ? AND ?
        GROUP BY dsa.slot_date
        ORDER BY dsa.slot_date
    ");
    $stmt->execute([$startDate, $endDate]);
    $dailyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $calendarData = [];
    foreach ($dailyData as $day) {
        $total = $day['total_slots'];
        $booked = $day['booked_count'];
        $filling = $day['filling_count'];
        $available = $day['available_count'];

        // Determine status based on availability
        if ($booked === $total) {
            $status = 'booked';
        } elseif ($filling > 0 || ($available / $total) < 0.3) {
            $status = 'filling';
        } else {
            $status = 'available';
        }

        $calendarData[$day['slot_date']] = [
            'status' => $status,
            'available' => $available,
            'booked' => $booked,
            'filling' => $filling,
            'total' => $total
        ];
    }

    // Initialize slots for dates that don't exist in database
    $currentDate = $startDate;
    while ($currentDate <= $endDate) {
        if (!isset($calendarData[$currentDate])) {
            // Check if it's a past date
            if (strtotime($currentDate) < strtotime(date('Y-m-d'))) {
                $calendarData[$currentDate] = [
                    'status' => 'past',
                    'available' => 0,
                    'booked' => 0,
                    'filling' => 0,
                    'total' => 0
                ];
            } else {
                // Initialize as available (will be created on first access)
                $calendarData[$currentDate] = [
                    'status' => 'available',
                    'available' => 10, // Default assumption
                    'booked' => 0,
                    'filling' => 0,
                    'total' => 10
                ];
            }
        }
        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
    }

    echo json_encode([
        'success' => true,
        'data' => $calendarData,
        'month' => $month,
        'year' => $year
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
