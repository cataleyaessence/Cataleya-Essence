<?php
error_reporting(0); // Suppress PHP warnings/errors from polluting JSON output
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['year']) || !isset($_GET['month'])) {
    echo json_encode(['error' => 'Year and month parameters required']);
    exit;
}

$year = intval($_GET['year']);
$month = intval($_GET['month']);

// Validate year and month
if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
    echo json_encode(['error' => 'Invalid year or month']);
    exit;
}

try {
    // Get first and last day of the month
    $first_day = new DateTime("$year-$month-01");
    $last_day = new DateTime("$year-$month-" . $first_day->format('t'));

    // Fetch daily slot availability for the month
    $stmt = $pdo->prepare("
        SELECT slot_date, status
        FROM daily_slot_availability
        WHERE slot_date >= ? AND slot_date <= ?
        GROUP BY slot_date, status
    ");
    $stmt->execute([$first_day->format('Y-m-d'), $last_day->format('Y-m-d')]);
    $daily_availability = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode(['success' => true, 'availability' => $daily_availability]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
