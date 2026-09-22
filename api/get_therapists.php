<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $category = isset($_GET['category']) ? $_GET['category'] : 'Beauty Services';
    
    // Validate category
    if (!in_array($category, ['Beauty Services', 'Spa Massage'])) {
        $category = 'Beauty Services';
    }

    // Fetch therapists by category
    $stmt = $pdo->prepare("
        SELECT 
            id,
            full_name,
            title,
            image_url,
            experience_years,
            rating,
            total_reviews,
            category,
            is_available
        FROM staff 
        WHERE category = ? AND is_available = 1 AND is_active = 1
        ORDER BY rating DESC, total_reviews DESC
    ");
    $stmt->execute([$category]);
    $therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $therapists,
        'category' => $category,
        'count' => count($therapists)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
