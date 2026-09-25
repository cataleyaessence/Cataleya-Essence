<?php
// This script adds image_url column to services table and populates it with image paths from serv.php
require_once __DIR__ . '/../config/database.php';

try {
    // Check if image_url column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM services LIKE 'image_url'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        // Add image_url column
        $pdo->exec("ALTER TABLE services ADD COLUMN image_url VARCHAR(255) DEFAULT NULL AFTER price");
        echo "<p style='color: green;'>Added image_url column to services table.</p>";
    } else {
        echo "<p style='color: orange;'>image_url column already exists.</p>";
    }

    // Map service names to image paths from serv.php
    $serviceImages = [
        'Signature Facial' => '../img/Signature facial.png',
        'Acne Clear Facial' => '../img/acne clear facial.png',
        'Classic Facial' => '../img/classic facial.png',
        'Hydra Facial' => '../img/hydra facial.png',
        'Diamond Peel' => '../img/diamond feal.png',
        'PDT Light Therapy' => '../img/pdt light.png',
        'Pimple Injection' => '../img/pimple injection.png',
        'BB Glow' => '../img/bb glow.png',
        'Warts Removal (Per Area)' => '../img/warts removal.png',
        'Vajacial' => '../img/vajacial.png',
        'RF Face' => '../img/rf face (1).png',
        'RF Arms' => '../img/rf arms.png',
        'RF Belly' => '../img/rf belly.png',
        'RF Legs' => '../img/rf legs.png',
        'RF Whole Body' => '../img/rf whole.png',
        'Natural Look' => '../img/natural look.png',
        'Volume Look' => '../img/volume look.png',
        'Cat Eye Look' => '../img/cat eye look.png',
        'Wispy Look' => '../img/wispy look.png',
        'Keratin lash Lift' => '../img/keratin lash.png',
        'Last Lift/ Mascara' => '../img/last lift.png',
        'Upper up' => '../img/removal upper up.png',
        'Face' => '../img/removal face.png',
        'Underarm' => '../img/removal underarm.png',
        'Legs' => '../img/removal legs.png',
        'Chest' => '../img/removal chest.png',
        'Brazilian' => '../img/removal brazilian.png',
        'Whole Body' => '../img/removal whole body.png',
        'Black Doll Carbon' => '../img/back doll.png',
        'Underarm Laser' => '../img/underarm laser.png',
        'Elbow Whitening' => '../img/elbow whitenming.png',
        'Knee Whitening' => '../img/knee whitening.png',
        'Melasma' => '../img/melasma.png',
        'Tattoo Removal' => '../img/tattoo removal.png',
        'Double Chin' => '../img/double chin.png',
        'Tummy' => '../img/tummy.png',
        'Thigh' => '../img/thigh.png',
        'Ultra Whitening Drip / Session' => '../img/ultra whitening.png',
        'Microblading' => '../img/microblading.png',
        'Lip Blush' => '../img/microshading.png',
        'Eyeliner Tattoo' => '../img/cat eye look.png',
        'Underarm Waxing' => '../img/underarm.png',
        'Full Leg Waxing' => '../img/legs.png',
        'Brazilian Waxing' => '../img/brazilian.png',
        'HIFU Face' => '../img/hifu face.png',
        'HIFU Body' => '../img/hifu body.png',
        'Manicure' => '../img/manicure.png',
        'Pedicure' => '../img/pedicure.png',
        'Gel Manicure' => '../img/gel manicure.png',
        'Gel Pedicure' => '../img/gel pedicure.png',
        'Swedish Massage' => '../img/swedish massage.png',
        'Deep Tissue Massage' => '../img/deep tissue massage.png',
        'Aromatherapy Massage' => '../img/aromatherapy massage.png',
        'Hilot' => '../img/Hilot.png',
        'Ventosa' => '../img/Ventosa.png',
        'Body Scrub' => '../img/body scrub.png',
        'Body Wrap' => '../img/body wrap.png',
    ];

    // Update services with image URLs. Services named "Arms" exist in two
    // different sub-categories, so they must be updated with their category
    // included instead of using a duplicate associative-array key.
    $stmt = $pdo->prepare("UPDATE services SET image_url = ? WHERE name = ?");
    $updated = 0;

    foreach ($serviceImages as $serviceName => $imageUrl) {
        $stmt->execute([$imageUrl, $serviceName]);
        if ($stmt->rowCount() > 0) {
            $updated++;
        }
    }

    $scopedServiceImages = [
        [
            'name' => 'Arms',
            'category' => 'Beauty Services',
            'subCategory' => 'Hair Laser Removal',
            'image' => '../img/removal arms.png',
        ],
        [
            'name' => 'Arms',
            'category' => 'Beauty Services',
            'subCategory' => 'Mesolipo',
            'image' => '../img/arms gluta.png',
        ],
    ];
    $scopedStmt = $pdo->prepare(
        'UPDATE services
         SET image_url = ?
         WHERE name = ? AND main_category = ? AND sub_category = ?'
    );
    foreach ($scopedServiceImages as $service) {
        $scopedStmt->execute([
            $service['image'],
            $service['name'],
            $service['category'],
            $service['subCategory'],
        ]);
        if ($scopedStmt->rowCount() > 0) {
            $updated++;
        }
    }

    echo "<p style='color: green;'>Updated <strong>$updated</strong> services with image URLs.</p>";

    // Show sample data
    echo "<h3>Sample Services with Images:</h3>";
    echo "<ul>";
    $stmt = $pdo->query("SELECT id, name, image_url FROM services WHERE image_url IS NOT NULL LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "<li>ID: " . $row['id'] . " - " . $row['name'] . " - " . $row['image_url'] . "</li>";
    }
    echo "</ul>";

    echo "<hr>";
    echo "<p><a href='../Php/profile.php'>View Profile</a> | <a href='../Php/bookings.php'>View Bookings</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
