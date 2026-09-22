<?php
// Test the actual therapist selection page logic
$_GET['category'] = 'Beauty Services';

// Simulate the therapist selection page logic
require_once __DIR__ . '/config/database.php';

// Get category from URL parameter or default to Beauty Services
$category = isset($_GET['category']) ? $_GET['category'] : 'Beauty Services';

// Validate category
if (!in_array($category, ['Beauty Services', 'Spa Massage'])) {
    $category = 'Beauty Services';
}

echo "Simulating Therapist Selection Page\n";
echo "===================================\n";
echo "URL Category: " . $category . "\n\n";

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
    WHERE category = ? AND is_available = 1
    ORDER BY rating DESC, total_reviews DESC
");
$stmt->execute([$category]);
$therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map category to data-type for JavaScript
$categoryType = ($category === 'Beauty Services') ? 'beauty' : 'spa';

echo "Category Type: " . $categoryType . "\n";
echo "Therapists to display: " . count($therapists) . "\n\n";

echo "Therapist Cards HTML Generation:\n";
echo "-------------------------------\n";

if (empty($therapists)) {
    echo "<p>No therapists available for this category.</p>\n";
} else {
    foreach ($therapists as $therapist) {
        echo "<div class='therapist-card' data-type='" . $categoryType . "' data-id='" . $therapist['id'] . "'>\n";
        echo "  <div class='therapist-avatar'>\n";
        echo "    <img src='" . htmlspecialchars($therapist['image_url']) . "' alt='" . htmlspecialchars($therapist['full_name']) . "' />\n";
        echo "  </div>\n";
        echo "  <h3 class='therapist-name'>" . htmlspecialchars($therapist['full_name']) . "</h3>\n";
        echo "  <p class='therapist-title'>" . htmlspecialchars($therapist['title']) . "</p>\n";
        echo "  <div class='therapist-rating'>\n";
        echo "    <i class='fas fa-star'></i> " . number_format($therapist['rating'], 1) . " <span>· " . $therapist['experience_years'] . " years</span>\n";
        echo "  </div>\n";
        echo "  <p class='therapist-specialties'>Specialists</p>\n";
        echo "  <button class='select-btn' data-id='" . $therapist['id'] . "'>Select</button>\n";
        echo "</div>\n\n";
    }
}

echo "\n=== PAGE SIMULATION COMPLETE ===\n";
?>
