<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$full_name = $input['full_name'] ?? '';
$email = $input['email'] ?? '';
$phone = $input['phone'] ?? '';
$special_requests = $input['special_requests'] ?? '';
$service_id = $input['service_id'] ?? 0;
$service_name = $input['service_name'] ?? '';
$service_price = $input['service_price'] ?? 0;
$downpayment = $input['downpayment'] ?? 0;
$booking_date = $input['booking_date'] ?? '';
$booking_time = $input['booking_time'] ?? '';
$staff_id = $input['staff_id'] ?? null;

// Calculate deadline (24 hours after booking creation)
$deadline = date('Y-m-d H:i:s', strtotime('+24 hours'));

// If service_id is not provided but service_name is, look up the ID
if (!$service_id && $service_name) {
    // Try exact match first
    $stmt = $pdo->prepare("SELECT id FROM services WHERE name = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$service_name]);
    $service = $stmt->fetch();
    if ($service) {
        $service_id = $service['id'];
    } else {
        // Try case-insensitive match
        $stmt = $pdo->prepare("SELECT id FROM services WHERE LOWER(name) = LOWER(?) AND is_active = 1 LIMIT 1");
        $stmt->execute([$service_name]);
        $service = $stmt->fetch();
        if ($service) {
            $service_id = $service['id'];
        } else {
            // Try partial match (contains)
            $stmt = $pdo->prepare("SELECT id FROM services WHERE name LIKE ? AND is_active = 1 LIMIT 1");
            $stmt->execute(["%$service_name%"]);
            $service = $stmt->fetch();
            if ($service) {
                $service_id = $service['id'];
            }
        }
    }
}

// Validate that service_id exists in services table
$service_price_db = 0;
if ($service_id) {
    $stmt = $pdo->prepare("SELECT id, price FROM services WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();
    if (!$service) {
        echo json_encode(['success' => false, 'error' => 'Invalid service_id: ' . $service_id . ' does not exist in services table', 'debug' => ['service_id' => $service_id, 'service_name' => $service_name]]);
        exit;
    }
    $service_price_db = (float)$service['price'];
}

// Validate required fields
$missing = [];
if (!$full_name) $missing[] = 'full_name';
if (!$email) $missing[] = 'email';
if (!$phone) $missing[] = 'phone';
if (!$service_id && !$service_name) $missing[] = 'service_id or service_name';
if (!$booking_date) $missing[] = 'booking_date';
if (!$booking_time) $missing[] = 'booking_time';

if (!empty($missing)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields: ' . implode(', ', $missing), 'debug' => $input]);
    exit;
}

// Determine current user discount from reward tier
$current_tier = 'bronze';
$stmt = $pdo->prepare("SELECT tier FROM rewards WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$rewardRow = $stmt->fetch();
if ($rewardRow && !empty($rewardRow['tier'])) {
    $current_tier = $rewardRow['tier'];
}
$discount_rate = getDiscountRateFromTier($current_tier);

// Use DB price if available, otherwise fallback to submitted service price
$base_price = $service_price_db > 0 ? $service_price_db : (float)$service_price;
$discounted_amount = round($base_price * (100 - $discount_rate) / 100, 2);

try {
    $pdo->beginTransaction();

    // Insert booking
    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            user_id, 
            service_id, 
            staff_id, 
            booking_date, 
            booking_time,
            deadline,
            total_amount, 
            notes, 
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $service_id,
        $staff_id,
        $booking_date,
        $booking_time,
        $deadline,
        $discounted_amount,
        $special_requests
    ]);

    $booking_id = $pdo->lastInsertId();

    $downpaymentAmount = $downpayment > 0
        ? round((float)$downpayment, 2)
        : round($discounted_amount * 0.5, 2);

    if ($downpaymentAmount > 0) {
        $stmt = $pdo->prepare("INSERT INTO payments (booking_id, user_id, amount, payment_method, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'completed', NOW(), NOW())");
        $stmt->execute([
            $booking_id,
            $user_id,
            $downpaymentAmount,
            'gcash'
        ]);
    }

    // Fetch the complete booking data with service and staff details
    $stmt = $pdo->prepare("
        SELECT 
            b.id as booking_id,
            b.booking_date,
            b.booking_time,
            b.total_amount,
            b.notes,
            b.status,
            b.created_at,
            s.name as service_name,
            s.price as service_price,
            s.main_category,
            s.sub_category,
            st.full_name as therapist_name,
            st.title as therapist_title,
            u.full_name as customer_name,
            u.email as customer_email,
            u.phone as customer_phone
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN staff st ON b.staff_id = st.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking_data = $stmt->fetch();

    // Update rewards - add points based on service price range
    $points_to_add = calculatePointsByPrice($service_price);
    
    // Check if user has rewards record
    $stmt = $pdo->prepare("SELECT id, points, visits FROM rewards WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $rewards = $stmt->fetch();

    if ($rewards) {
        // Update existing rewards - increment points and visits
        $new_points = $rewards['points'] + $points_to_add;
        $new_visits = $rewards['visits'] + 1;
        $new_tier = getTierFromPoints($new_points);
        
        $stmt = $pdo->prepare("UPDATE rewards SET points = ?, visits = ?, tier = ? WHERE user_id = ?");
        $stmt->execute([$new_points, $new_visits, $new_tier, $user_id]);
    } else {
        // Create new rewards record
        $new_tier = getTierFromPoints($points_to_add);
        
        $stmt = $pdo->prepare("INSERT INTO rewards (user_id, points, visits, tier) VALUES (?, ?, 1, ?)");
        $stmt->execute([$user_id, $points_to_add, $new_tier]);
    }

    // Update daily slot availability
    $stmt = $pdo->prepare("
        SELECT id FROM time_slots 
        WHERE display_time = ? AND is_active = 1 
        LIMIT 1
    ");
    $stmt->execute([$booking_time]);
    $time_slot = $stmt->fetch();

    if ($time_slot) {
        $slot_id = $time_slot['id'];
        
        // Check if slot availability exists for this date
        $stmt = $pdo->prepare("
            SELECT id, current_bookings, max_bookings 
            FROM daily_slot_availability 
            WHERE slot_date = ? AND slot_id = ?
        ");
        $stmt->execute([$booking_date, $slot_id]);
        $slot_availability = $stmt->fetch();

        if ($slot_availability) {
            // Update existing
            $new_current = $slot_availability['current_bookings'] + 1;
            $new_status = $new_current >= $slot_availability['max_bookings'] ? 'booked' : 'available';
            
            $stmt = $pdo->prepare("
                UPDATE daily_slot_availability 
                SET current_bookings = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([$new_current, $new_status, $slot_availability['id']]);
        } else {
            // Create new
            $stmt = $pdo->prepare("
                INSERT INTO daily_slot_availability (slot_date, slot_id, status, max_bookings, current_bookings)
                VALUES (?, ?, 'filling', 1, 1)
            ");
            $stmt->execute([$booking_date, $slot_id]);
        }
    }

    $pdo->commit();

    // Send booking confirmation email
    if ($email && $booking_data) {
        $emailSent = sendBookingConfirmationEmail($email, $booking_data);
        if (!$emailSent) {
            error_log("Failed to send booking confirmation email to: " . $email);
        }
    }

    echo json_encode([
        'success' => true, 
        'booking_id' => $booking_id,
        'booking' => $booking_data,
        'message' => 'Booking created successfully'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

function calculatePointsByPrice($price) {
    if ($price <= 400) {
        return 10;
    } elseif ($price <= 799) {
        return 15;
    } else {
        return 20;
    }
}

function getTierFromPoints($points) {
    if ($points >= 200) return 'platinum';
    if ($points >= 150) return 'gold';
    if ($points >= 100) return 'silver';
    if ($points >= 50) return 'bronze';
    return 'bronze'; // Default to bronze for new users
}

function getDiscountRateFromTier($tier) {
    switch ($tier) {
        case 'silver':
            return 10;
        case 'gold':
            return 15;
        case 'platinum':
            return 20;
        default:
            return 5;
    }
}
