<?php
session_start();
require_once __DIR__ . '/config/database.php';

echo "=== THERAPIST SELECTION TEST ===\n\n";

// Test 1: Beauty Services category
echo "Test 1: Beauty Services Category\n";
echo "--------------------------------\n";
$category = 'Beauty Services';
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

echo "Category: " . $category . "\n";
echo "Therapists found: " . count($therapists) . "\n";
foreach ($therapists as $t) {
    echo "- ID: " . $t['id'] . ", Name: " . $t['full_name'] . ", Title: " . $t['title'] . ", Rating: " . $t['rating'] . "\n";
}

echo "\n";

// Test 2: Spa Massage category
echo "Test 2: Spa Massage Category\n";
echo "---------------------------\n";
$category = 'Spa Massage';
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

echo "Category: " . $category . "\n";
echo "Therapists found: " . count($therapists) . "\n";
foreach ($therapists as $t) {
    echo "- ID: " . $t['id'] . ", Name: " . $t['full_name'] . ", Title: " . $t['title'] . ", Rating: " . $t['rating'] . "\n";
}

echo "\n";

// Test 3: Invalid category (should default to Beauty Services)
echo "Test 3: Invalid Category (should default to Beauty Services)\n";
echo "---------------------------------------------------------------\n";
$category = 'Invalid Category';
if (!in_array($category, ['Beauty Services', 'Spa Massage'])) {
    $category = 'Beauty Services';
    echo "Category normalized to: " . $category . "\n";
}

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

echo "Therapists found: " . count($therapists) . "\n";

echo "\n=== TEST COMPLETE ===\n";
?>
