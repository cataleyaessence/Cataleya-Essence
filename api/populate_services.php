<?php
// This script populates the services table with data from serv.php
// Run this file directly in the browser: http://localhost/Beauty%20Essence/api/populate_services.php

require_once __DIR__ . '/../config/database.php';

try {
    // Check if services table has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM services");
    $result = $stmt->fetch();
    $serviceCount = $result['count'];
    
    echo "<h2>Services Table Status</h2>";
    echo "<p>Current service count: <strong>" . $serviceCount . "</strong></p>";
    
    if ($serviceCount > 0) {
        echo "<p style='color: orange;'>Services table already has data. Skipping population.</p>";
        echo "<p>To repopulate, first run: TRUNCATE TABLE services;</p>";
    } else {
        echo "<p>Populating services table...</p>";
        
        // Facial Services
        $services = [
            ['Signature Facial', 'Beauty Services', 'Facial Services', 599.00, 60],
            ['Acne Clear Facial', 'Beauty Services', 'Facial Services', 599.00, 60],
            ['Classic Facial', 'Beauty Services', 'Facial Services', 350.00, 45],
            ['Hydra Facial', 'Beauty Services', 'Facial Services', 799.00, 75],
            ['Diamond Peel', 'Beauty Services', 'Facial Services', 249.00, 30],
            ['PDT Light Therapy', 'Beauty Services', 'Facial Services', 249.00, 30],
            ['Pimple Injection', 'Beauty Services', 'Facial Services', 199.00, 15],
            ['BB Glow', 'Beauty Services', 'Facial Services', 799.00, 60],
            ['Warts Removal (Per Area)', 'Beauty Services', 'Facial Services', 1499.00, 30],
            ['Vajacial', 'Beauty Services', 'Facial Services', 999.00, 45],
            ['RF Face', 'Beauty Services', 'RF + Lipo Cav', 299.00, 30],
            ['RF Arms', 'Beauty Services', 'RF + Lipo Cav', 499.00, 45],
            ['RF Belly', 'Beauty Services', 'RF + Lipo Cav', 599.00, 45],
            ['RF Legs', 'Beauty Services', 'RF + Lipo Cav', 699.00, 60],
            ['RF Whole Body', 'Beauty Services', 'RF + Lipo Cav', 1999.00, 90],
            ['Natural Look', 'Beauty Services', 'EyeLash Enhancement', 350.00, 60],
            ['Volume Look', 'Beauty Services', 'EyeLash Enhancement', 450.00, 75],
            ['Cat Eye Look', 'Beauty Services', 'EyeLash Enhancement', 550.00, 90],
            ['Wispy Look', 'Beauty Services', 'EyeLash Enhancement', 800.00, 120],
            ['Keratin lash Lift', 'Beauty Services', 'EyeLash Enhancement', 800.00, 60],
            ['Last Lift/ Mascara', 'Beauty Services', 'EyeLash Enhancement', 350.00, 45],
            ['Upper up', 'Beauty Services', 'Hair Laser Removal', 199.00, 15],
            ['Face', 'Beauty Services', 'Hair Laser Removal', 299.00, 30],
            ['Underarm', 'Beauty Services', 'Hair Laser Removal', 499.00, 30],
            ['Arms', 'Beauty Services', 'Hair Laser Removal', 799.00, 45],
            ['Legs', 'Beauty Services', 'Hair Laser Removal', 899.00, 60],
            ['Chest', 'Beauty Services', 'Hair Laser Removal', 499.00, 30],
            ['Brazilian', 'Beauty Services', 'Hair Laser Removal', 799.00, 45],
            ['Whole Body', 'Beauty Services', 'Hair Laser Removal', 2500.00, 120],
            ['Black Doll Carbon', 'Beauty Services', 'Laser Whitening', 799.00, 45],
            ['Underarm Laser', 'Beauty Services', 'Laser Whitening', 499.00, 30],
            ['Elbow Whitening', 'Beauty Services', 'Laser Whitening', 499.00, 30],
            ['Knee Whitening', 'Beauty Services', 'Laser Whitening', 599.00, 30],
            ['Melasma', 'Beauty Services', 'Laser Whitening', 799.00, 60],
            ['Tattoo Removal', 'Beauty Services', 'Laser Whitening', 2500.00, 60],
            ['Double Chin', 'Beauty Services', 'Mesolipo', 999.00, 30],
            ['Arms', 'Beauty Services', 'Mesolipo', 2000.00, 45],
            ['Tummy', 'Beauty Services', 'Mesolipo', 1499.00, 45],
            ['Thigh', 'Beauty Services', 'Mesolipo', 2000.00, 60],
            ['Ultra Whitening Drip / Session', 'Beauty Services', 'Gluta Whitening', 1799.00, 60],
            ['Microblading', 'Beauty Services', 'Semi-Permanent Make Up', 2999.00, 120],
            ['Lip Blush', 'Beauty Services', 'Semi-Permanent Make Up', 2499.00, 90],
            ['Eyeliner Tattoo', 'Beauty Services', 'Semi-Permanent Make Up', 1999.00, 60],
            ['Underarm Waxing', 'Beauty Services', 'Waxing', 199.00, 15],
            ['Full Leg Waxing', 'Beauty Services', 'Waxing', 599.00, 45],
            ['Brazilian Waxing', 'Beauty Services', 'Waxing', 799.00, 45],
            ['HIFU Face', 'Beauty Services', 'HIFU Ultheraphy', 2999.00, 90],
            ['HIFU Body', 'Beauty Services', 'HIFU Ultheraphy', 4999.00, 120],
            ['Manicure', 'Beauty Services', 'Nail Services', 199.00, 30],
            ['Pedicure', 'Beauty Services', 'Nail Services', 249.00, 45],
            ['Gel Manicure', 'Beauty Services', 'Nail Services', 399.00, 45],
            ['Gel Pedicure', 'Beauty Services', 'Nail Services', 499.00, 60],
            ['Swedish Massage', 'Spa Massage', 'Body Care', 999.00, 60],
            ['Deep Tissue Massage', 'Spa Massage', 'Body Care', 1299.00, 90],
            ['Aromatherapy Massage', 'Spa Massage', 'Body Care', 1199.00, 60],
            ['Hilot', 'Spa Massage', 'Traditional Body Care', 899.00, 60],
            ['Ventosa', 'Spa Massage', 'Traditional Body Care', 799.00, 45],
            ['Body Scrub', 'Spa Massage', 'Body Skin Treatment', 699.00, 45],
            ['Body Wrap', 'Spa Massage', 'Body Skin Treatment', 999.00, 60],
        ];
        
        $stmt = $pdo->prepare("INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        
        $inserted = 0;
        foreach ($services as $service) {
            $stmt->execute($service);
            $inserted++;
        }
        
        echo "<p style='color: green;'><strong>Successfully inserted " . $inserted . " services!</strong></p>";
        
        // Verify
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM services");
        $result = $stmt->fetch();
        echo "<p>New service count: <strong>" . $result['count'] . "</strong></p>";
        
        // Show sample data
        echo "<h3>Sample Services:</h3>";
        echo "<ul>";
        $stmt = $pdo->query("SELECT id, name, main_category, sub_category, price FROM services LIMIT 10");
        while ($row = $stmt->fetch()) {
            echo "<li>ID: " . $row['id'] . " - " . $row['name'] . " (" . $row['price'] . ")</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<p><a href='../Php/home.php'>Return to Home</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
