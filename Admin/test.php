<?php
session_start();

// Authentication Check - Redirect if not logged in or not an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !in_array($_SESSION['user_role'], ['admin', 'sub_admin'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'airlines';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get admin information for header
$admin_id = $_SESSION['user_id'];
$admin_role = $_SESSION['user_role'];
$admin_name = $_SESSION['user_name'];
$admin_email = $_SESSION['user_email'];

// Get admin's first name and initial for display
$first_name = explode(' ', $admin_name)[0];
$admin_initial = strtoupper(substr($first_name, 0, 1));

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'verify_payment':
                $booking_ref = $_POST['booking_ref'];
                
                // Get booking details
                $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_ref = ?");
                $stmt->execute([$booking_ref]);
                $booking = $stmt->fetch();
                
                if (!$booking) {
                    echo json_encode(['success' => false, 'message' => 'Booking not found']);
                    break;
                }
                
                // Here you would integrate with Paystack API to verify payment
                // For now, we'll simulate verification
                if ($booking['status'] === 'pending') {
                    // Update booking status to completed
                    $stmt = $pdo->prepare("
                        UPDATE bookings 
                        SET status = 'completed', updated_at = NOW()
                        WHERE booking_ref = ?
                    ");
                    $result = $stmt->execute([$booking_ref]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Payment verified and booking completed']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update booking status']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Booking is already completed']);
                }
                break;

            case 'get_booking_details':
                $booking_ref = $_POST['booking_ref'];
                
                // Get detailed booking information with user and flight details
                $stmt = $pdo->prepare("
                    SELECT 
                        b.*,
                        u.full_name as passenger_name,
                        u.email as passenger_email,
                        u.phone as passenger_phone,
                        f.flight_no,
                        f.airline,
                        f.origin,
                        f.destination,
                        f.flight_date,
                        f.departure_time,
                        f.arrival_time,
                        f.aircraft,
                        f.price as flight_base_price,
                        f.seats_available,
                        -- Class-based seat availability (if columns exist)
                        COALESCE(f.economy_seats, 0) as economy_seats_available,
                        COALESCE(f.business_seats, 0) as business_seats_available,
                        COALESCE(f.first_class_seats, 0) as first_class_seats_available,
                        -- Class-based pricing (if columns exist)
                        COALESCE(f.economy_price, f.price) as economy_price,
                        COALESCE(f.business_price, f.price * 2.5) as business_price,
                        COALESCE(f.first_class_price, f.price * 4.0) as first_class_price
                    FROM bookings b
                    LEFT JOIN users u ON b.user_id = u.user_id
                    LEFT JOIN flights f ON b.flight_id = f.flight_id
                    WHERE b.booking_ref = ?
                ");
                $stmt->execute([$booking_ref]);
                $booking = $stmt->fetch();
                
                if ($booking) {
                    echo json_encode(['success' => true, 'booking' => $booking]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Booking not found']);
                }
                break;

            case 'get_seat_availability':
                $flight_id = $_POST['flight_id'];
                
                // Get current seat availability for the flight
                $stmt = $pdo->prepare("
                    SELECT 
                        f.seats_available,
                        COALESCE(f.economy_seats, 0) as economy_seats,
                        COALESCE(f.business_seats, 0) as business_seats,
                        COALESCE(f.first_class_seats, 0) as first_class_seats,
                        -- Calculate booked seats per class
                        COALESCE(SUM(CASE WHEN b.class = 'economy' AND b.status = 'completed' THEN COALESCE(b.seats, b.passengers) ELSE 0 END), 0) as economy_booked,
                        COALESCE(SUM(CASE WHEN b.class = 'business' AND b.status = 'completed' THEN COALESCE(b.seats, b.passengers) ELSE 0 END), 0) as business_booked,
                        COALESCE(SUM(CASE WHEN b.class = 'first' AND b.status = 'completed' THEN COALESCE(b.seats, b.passengers) ELSE 0 END), 0) as first_booked,
                        COALESCE(SUM(CASE WHEN b.status = 'completed' THEN COALESCE(b.seats, b.passengers) ELSE 0 END), 0) as total_booked
                    FROM flights f
                    LEFT JOIN bookings b ON f.flight_id = b.flight_id
                    WHERE f.flight_id = ?
                    GROUP BY f.flight_id
                ");
                $stmt->execute([$flight_id]);
                $availability = $stmt->fetch();
                
                if ($availability) {
                    // Calculate available seats per class
                    $availability['economy_available'] = max(0, $availability['economy_seats'] - $availability['economy_booked']);
                    $availability['business_available'] = max(0, $availability['business_seats'] - $availability['business_booked']);
                    $availability['first_available'] = max(0, $availability['first_class_seats'] - $availability['first_booked']);
                    $availability['total_available'] = max(0, $availability['seats_available'] - $availability['total_booked']);
                    
                    echo json_encode(['success' => true, 'availability' => $availability]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Flight not found']);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Get all bookings with user and flight information
$bookings = [];
try {
    $stmt = $pdo->query("
        SELECT 
            b.booking_id,
            b.booking_ref,
            b.user_id,
            b.flight_id,
            b.passengers,
            COALESCE(b.seats, b.passengers) as seats,
            b.class,
            b.total_amount,
            b.status,
            b.created_at,
            b.updated_at,
            COALESCE(b.paystack_reference, '') as paystack_reference,
            u.full_name as passenger_name,
            u.email as passenger_email,
            u.phone as passenger_phone,
            COALESCE(f.flight_no, CONCAT('FL', LPAD(b.flight_id, 3, '0'))) as flight_number,
            COALESCE(f.airline, 'Speed of Light Airlines') as airline,
            COALESCE(CONCAT(f.origin, ' → ', f.destination), 'Route Not Available') as route,
            COALESCE(f.flight_date, DATE_ADD(b.created_at, INTERVAL 30 DAY)) as departure_date,
            COALESCE(f.departure_time, '12:00:00') as departure_time,
            COALESCE(f.arrival_time, '15:00:00') as arrival_time,
            COALESCE(f.aircraft, 'Boeing 737') as aircraft,
            COALESCE(f.price, 0) as flight_base_price,
            COALESCE(f.seats_available, 0) as seats_available
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN flights f ON b.flight_id = f.flight_id
        ORDER BY b.created_at DESC
    ");
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $bookings = [];
    error_log("Error fetching bookings: " . $e->getMessage());
}

// Calculate statistics
$total_bookings = count($bookings);
$pending_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'pending'; }));
$completed_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'completed'; }));

// Calculate seat statistics
$total_seats_booked = array_sum(array_map(function($b) { return $b['seats']; }, $bookings));
$completed_seats = array_sum(array_map(function($b) { return $b['status'] === 'completed' ? $b['seats'] : 0; }, $bookings));
$pending_seats = array_sum(array_map(function($b) { return $b['status'] === 'pending' ? $b['seats'] : 0; }, $bookings));

// Get recent notifications (bookings in last 24 hours)
$notifications = [];
$notification_count = 0;
try {
    // Recent completed bookings
    $stmt = $pdo->query("
        SELECT 
            b.booking_ref,
            u.full_name,
            b.total_amount,
            COALESCE(b.seats, b.passengers) as seats,
            b.class,
            b.created_at
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE b.status = 'completed' 
        AND b.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY b.created_at DESC
        LIMIT 3
    ");
    $recent_completed = $stmt->fetchAll();
    
    // Recent pending bookings
    $stmt = $pdo->query("
        SELECT 
            b.booking_ref,
            u.full_name,
            b.total_amount,
            COALESCE(b.seats, b.passengers) as seats,
            b.class,
            b.created_at
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE b.status = 'pending' 
        AND b.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY b.created_at DESC
        LIMIT 3
    ");
    $recent_pending = $stmt->fetchAll();
    
    // Combine notifications
    foreach ($recent_completed as $booking) {
        $notifications[] = [
            'type' => 'completed',
            'icon' => 'check-circle',
            'title' => 'Payment completed',
            'meta' => $booking['booking_ref'] . ' • ' . ($booking['full_name'] ?: 'Unknown') . ' • ' . $booking['seats'] . ' seats • ₦' . number_format($booking['total_amount'])
        ];
    }
    
    foreach ($recent_pending as $booking) {
        $notifications[] = [
            'type' => 'pending',
            'icon' => 'clock',
            'title' => 'Booking pending payment',
            'meta' => $booking['booking_ref'] . ' • ' . ($booking['full_name'] ?: 'Unknown') . ' • ' . $booking['seats'] . ' seats • ₦' . number_format($booking['total_amount'])
        ];
    }
    
    $notification_count = count($notifications);
    
} catch (PDOException $e) {
    $notifications = [];
    $notification_count = 0;
}

// Helper function to format time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Function to calculate class-based pricing (since your flights table doesn't have class-specific pricing)
function getClassMultiplier($class) {
    switch (strtolower($class)) {
        case 'economy':
            return 1.0; // Base price
        case 'business':
            return 2.5; // 2.5x base price
        case 'first':
            return 4.0; // 4x base price
        default:
            return 1.0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Manage Bookings - Speed of Light Airlines Admin" />
    <meta name="keywords" content="airline admin, bookings, paystack, payment verification">
    <meta name="author" content="Speed of Light Airlines" />
    <title>Bookings | Speed of Light Airlines</title>
    <link rel="icon" href="../User/assets/images/airline-favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <!-- Feather Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #38a169;
            --brand-dark: #38a169;
            --bg: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.96);
            --muted: #64748b;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        * {
            box-sizing: border-box
        }

        body {
            background: var(--bg);
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            overflow-x: hidden
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 260px;
            background: var(--brand-dark);
            box-shadow: 2px 0 20px rgba(0, 0, 0, .08);
            z-index: 1000;
            color: #fff;
            padding: 20px 16px
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px
        }

        .brand img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .3)
        }

        .brand-title {
            font-weight: 800;
            letter-spacing: 1px
        }

        .nav-section {
            margin-top: 8px
        }

        .nav-link {
            color: #e5e7eb;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all .25s ease;
            position: relative;
            overflow: hidden
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--brand);
            color: #fff;
            transform: translateX(4px);
            box-shadow: 0 6px 18px rgba(0, 83, 156, .25)
        }

        .nav-link i {
            width: 18px;
            height: 18px
        }

        /* Topbar */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 900;
            background: linear-gradient(135deg, rgba(0, 83, 156, .9), rgba(0, 51, 102, .9));
            color: #fff;
            padding: 12px 16px;
            margin-left: 260px;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, .1)
        }

        .topbar .right {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-left: auto
        }

        .cart-icon-container {
            position: relative;
            cursor: pointer;
            transition: all .3s ease;
            padding: 6px 10px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .12)
        }

        .cart-icon-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(0, 0, 0, .2)
        }

        .cart-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: #fff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .72rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(255, 107, 107, .4)
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, .12);
            padding: 6px 12px;
            border-radius: 10px
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #48bb78, #38a169);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #fff
        }

        /* Ticker */
        .ticker {
            display: none;
            overflow: hidden;
            white-space: nowrap;
            max-width: 55%
        }

        @media(min-width:768px) {
            .ticker {
                display: block
            }
        }

        .ticker-track {
            display: inline-block;
            padding-left: 100%;
            animation: ticker 22s linear infinite;
            color: #e2e8f0;
            font-weight: 600
        }

        @keyframes ticker {
            0% {
                transform: translateX(0)
            }

            100% {
                transform: translateX(-100%)
            }
        }

        /* Content & cards */
        .content {
            margin-left: 260px;
            padding: 22px 18px
        }

        .page-title h1 {
            font-weight: 800;
            letter-spacing: .5px;
            background: linear-gradient(45deg, #38a169, #38a169);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent
        }

        .smart-card {
            background: var(--card-bg);
            border: 1px solid rgba(0, 83, 156, .08);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(0, 83, 156, .10);
            position: relative;
            overflow: hidden;
            transition: transform .35s cubic-bezier(.175, .885, .32, 1.275), box-shadow .35s
        }

        .smart-card:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 18px 50px rgba(0, 83, 156, .20)
        }

        .smart-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #38a169, #38a169)
        }

        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .6s ease, transform .6s ease
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0)
        }

        .badge-soft {
            background: rgba(0, 83, 156, .08);
            color: var(--brand-dark);
            border: 1px solid rgba(0, 83, 156, .15)
        }

        .table thead th {
            color: var(--brand-dark);
            font-weight: 700;
            border-bottom: 2px solid rgba(0, 83, 156, .15);
            font-size: 0.9rem;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .action-chip {
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #fff;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .action-chip:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            color: #fff;
        }

        .chip-blue {
            background: linear-gradient(135deg, #38a169, #38a169)
        }

        .chip-green {
            background: linear-gradient(135deg, #10b981, #059669)
        }

        .chip-orange {
            background: linear-gradient(135deg, #f59e0b, #d97706)
        }

        .chip-red {
            background: linear-gradient(135deg, #ef4444, #dc2626)
        }

        .chip-gray {
            background: linear-gradient(135deg, #6b7280, #4b5563)
        }

        /* Status badges - Only Pending and Completed */
        .status-pending {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-completed {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Stats cards - Updated with seat tracking */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid rgba(0, 83, 156, .08);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-card.pending::before {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .stat-card.completed::before {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .stat-card.total::before {
            background: linear-gradient(90deg, #38a169, #38a169);
        }

        .stat-card.seats::before {
            background: linear-gradient(90deg, #8b5cf6, #7c3aed);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--brand-dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .stat-sublabel {
            color: var(--muted);
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        /* Booking details modal improvements */
        .detail-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .detail-section h6 {
            color: var(--brand-dark);
            font-weight: 700;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--muted);
        }

        .detail-value {
            font-weight: 600;
            color: var(--brand-dark);
        }

        /* Payment status indicators */
        .payment-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .payment-pending {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .payment-failed {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Paystack reference styling */
        .paystack-ref {
            font-family: 'Courier New', monospace;
            background: rgba(0, 83, 156, 0.1);
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.85rem;
            color: var(--brand-dark);
            font-weight: 600;
        }

        /* Seat indicator styling */
        .seat-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-weight: 600;
            color: var(--brand-dark);
        }

        .seat-icon {
            width: 16px;
            height: 16px;
            color: var(--brand);
        }

        /* Notification dropdown */
        .notification-menu {
            min-width: 300px;
            border: 1px solid rgba(0, 83, 156, .12);
            border-radius: 14px;
            padding: 10px;
            background: #fff
        }

        .notification-header {
            font-weight: 700;
            color: var(--brand-dark);
            padding: 6px 10px;
            border-bottom: 1px solid rgba(0, 83, 156, .1);
            margin-bottom: 6px
        }

        .notification-item {
            display: flex;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 10px;
            transition: background .2s
        }

        .notification-item:hover {
            background: rgba(0, 83, 156, .06)
        }

        .notification-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(0, 83, 156, .1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--brand)
        }

        .notification-meta {
            font-size: .78rem;
            color: #64748b
        }

        .no-notifications {
            text-align: center;
            padding: 20px;
            color: #64748b
        }

        .no-notifications i {
            width: 32px;
            height: 32px;
            margin-bottom: 8px;
            opacity: 0.5
        }

        /* Database improvement notice */
        .improvement-notice {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 1px solid #f59e0b;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #92400e;
        }

        .improvement-notice .fw-bold {
            color: #78350f;
        }

        @media(max-width:991px) {
            .sidebar {
                left: -260px;
                transition: left .3s
            }

            .sidebar.show {
                left: 0
            }

            .topbar,
            .content {
                margin-left: 0
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width:576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <img src="../pexels-sevenstormphotography-728824 (1).jpg" alt="Logo">
            <div class="brand-title">SKYNOVA</div>
        </div>
        <nav class="nav-section">
            <a href="dashboard.php" class="nav-link"><i data-feather="home"></i> Dashboard</a>
            <a href="manage-flights.php" class="nav-link"><i data-feather="navigation"></i> Manage Flight</a>
            <a href="bookings.php" class="nav-link active"><i data-feather="calendar"></i> Bookings</a>
            <a href="users.php" class="nav-link"><i data-feather="users"></i> User Management</a>
            <a href="payments.php" class="nav-link"><i data-feather="credit-card"></i> Payments</a>
            <a href="reports.php" class="nav-link"><i data-feather="bar-chart-2"></i> Generate Reports</a>
            <a href="profile.php" class="nav-link"><i data-feather="user"></i> Profile</a>
            <a href="logout.php" class="nav-link"><i data-feather="log-out"></i> Logout</a>
        </nav>
    </aside>

    <!-- Topbar -->
    <header class="topbar d-flex align-items-center">
        <button class="btn btn-sm btn-light d-lg-none" id="menuToggle"><i data-feather="menu"></i></button>
        <div class="ticker ms-3 me-3 flex-grow-1 d-none d-md-block">
            <div class="ticker-track">
                <span>Automated Bookings • Seat Tracking • Paystack Payment Processing • Real-time Payment Verification • Welcome back <?php echo htmlspecialchars($admin_name); ?> • </span>
                <span>Automated Bookings • Seat Tracking • Paystack Payment Processing • Real-time Payment Verification • Welcome back <?php echo htmlspecialchars($admin_name); ?> • </span>
            </div>
        </div>
        <div class="right ms-auto">
            <div class="dropdown">
                <button class="cart-icon-container btn p-0" id="notifToggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i data-feather="bell" class="cart-icon"></i>
                    <?php if ($notification_count > 0): ?>
                        <div class="cart-badge"><?php echo $notification_count; ?></div>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-menu" aria-labelledby="notifToggle">
                    <div class="notification-header">Notifications</div>
                    <?php if (empty($notifications)): ?>
                        <div class="no-notifications">
                            <i data-feather="bell-off"></i>
                            <div class="fw-semibold">No new notifications</div>
                            <div class="small text-muted">You're all caught up!</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item">
                                <div class="notification-icon">
                                    <i data-feather="<?php echo $notification['icon']; ?>" style="width:16px;height:16px"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($notification['title']); ?></div>
                                    <div class="notification-meta"><?php echo htmlspecialchars($notification['meta']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-2">
                            <a href="bookings.php" class="small" style="color:var(--brand)">View all bookings</a>
        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="user-info ms-2">
                <div class="user-avatar"><?php echo $admin_initial; ?></div>
                <div class="fw-bold"><?php echo htmlspecialchars($first_name); ?></div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="content">
        <div class="page-title reveal mb-4">
            <h1>Bookings Management</h1>
            <div class="text-muted">Automated booking system with seat tracking, Paystack payment processing and real-time verification.</div>
        </div>

        <!-- Database Improvement Notice -->
        <div class="improvement-notice reveal">
            <div class="fw-bold mb-2"><i data-feather="info" style="width:16px;height:16px;"></i> Database Enhancement Recommendation</div>
            <div class="small">
                Your current flights table doesn't support class-based seating (Economy, Business, First Class). 
                Consider adding separate pricing and seat availability for each class to enable proper airline booking functionality.
                <a href="#" class="text-decoration-underline" data-bs-toggle="modal" data-bs-target="#improvementModal">View recommended changes</a>
            </div>
        </div>

        <!-- Stats Overview - Updated with seat tracking -->
        <div class="stats-grid reveal">
            <div class="stat-card total">
                <div class="stat-number"><?php echo $total_bookings; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number"><?php echo $pending_bookings; ?></div>
                <div class="stat-label">Pending Payment</div>
                <div class="stat-sublabel"><?php echo $pending_seats; ?> seats pending</div>
            </div>
            <div class="stat-card completed">
                <div class="stat-number"><?php echo $completed_bookings; ?></div>
                <div class="stat-label">Completed</div>
                <div class="stat-sublabel"><?php echo $completed_seats; ?> seats confirmed</div>
            </div>
            <div class="stat-card seats">
                <div class="stat-number"><?php echo $total_seats_booked; ?></div>
                <div class="stat-label">Total Seats Booked</div>
                <div class="stat-sublabel"><?php echo $completed_seats; ?> confirmed, <?php echo $pending_seats; ?> pending</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="smart-card reveal mb-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search Bookings</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by reference, passenger name, or flight">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select id="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Class</label>
                    <select id="classFilter" class="form-select">
                        <option value="">All Classes</option>
                        <option value="economy">Economy</option>
                        <option value="business">Business</option>
                        <option value="first">First Class</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" id="dateFrom" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" id="dateTo" class="form-control">
                </div>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="smart-card reveal">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="fw-bold">All Bookings</div>
                <div class="d-flex align-items-center gap-3">
                    <div class="small text-muted" id="countText"><?php echo $total_bookings; ?> booking(s) found</div>
                    <button class="btn btn-outline-primary btn-sm" id="refreshBtn">
                        <i data-feather="refresh-cw"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="bookingsTable">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Passenger</th>
                            <th>Flight Route</th>
                            <th>Class</th>
                            <th>Passengers</th>
                            <th>Seats</th>
                            <th>Total Amount</th>
                            <th>Paystack Ref</th>
                            <th>Status</th>
                            <th>Booked Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bookingsBody">
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    <i data-feather="calendar" style="width:48px;height:48px;margin-bottom:12px;"></i><br>
                                    No bookings found. Bookings will appear here once customers start making reservations.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['booking_ref']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['passenger_name'] ?: 'Unknown'); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($booking['route']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($booking['airline']); ?></small>
                                    </td>
                                    <td><span class="badge badge-soft"><?php echo ucfirst(htmlspecialchars($booking['class'])); ?></span></td>
                                    <td><?php echo $booking['passengers']; ?></td>
                                    <td>
                                        <div class="seat-indicator">
                                            <i data-feather="users" class="seat-icon"></i>
                                            <?php echo $booking['seats']; ?>
                                        </div>
                                    </td>
                                    <td><strong>₦<?php echo number_format($booking['total_amount']); ?></strong></td>
                                    <td>
                                        <?php if ($booking['paystack_reference']): ?>
                                            <span class="paystack-ref"><?php echo htmlspecialchars($booking['paystack_reference']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <span class="status-pending">Pending</span>
                                        <?php else: ?>
                                            <span class="status-completed">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></td>
                                    <td>
                                        <button class="action-chip chip-blue btn-sm" data-action="details" data-ref="<?php echo htmlspecialchars($booking['booking_ref']); ?>">
                                            <i data-feather="eye"></i> View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i data-feather="eye"></i> Booking Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Booking Information -->
                    <div class="detail-section">
                        <h6><i data-feather="bookmark"></i> Booking Information</h6>
                        <div class="detail-row">
                            <span class="detail-label">Reference:</span>
                            <span class="detail-value" id="detailRef">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span id="detailStatus">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Booking Date:</span>
                            <span class="detail-value" id="detailBookingDate">-</span>
                        </div>
                    </div>

                    <!-- Passenger Information -->
                    <div class="detail-section">
                        <h6><i data-feather="user"></i> Passenger Information</h6>
                        <div class="detail-row">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value" id="detailPassengerName">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value" id="detailPassengerEmail">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value" id="detailPassengerPhone">-</span>
                        </div>
                    </div>

                    <!-- Flight Information -->
                    <div class="detail-section">
                        <h6><i data-feather="navigation"></i> Flight Information</h6>
                        <div class="detail-row">
                            <span class="detail-label">Airline:</span>
                            <span class="detail-value" id="detailAirline">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Flight Number:</span>
                            <span class="detail-value" id="detailFlightNumber">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Route:</span>
                            <span class="detail-value" id="detailRoute">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Aircraft:</span>
                            <span class="detail-value" id="detailAircraft">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Class:</span>
                            <span class="detail-value" id="detailClass">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Passengers:</span>
                            <span class="detail-value" id="detailPassengers">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Seats Booked:</span>
                            <span class="detail-value" id="detailSeats">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Departure:</span>
                            <span class="detail-value" id="detailDeparture">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Arrival:</span>
                            <span class="detail-value" id="detailArrival">-</span>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="detail-section">
                        <h6><i data-feather="credit-card"></i> Payment Information</h6>
                        <div class="detail-row">
                            <span class="detail-label">Base Price:</span>
                            <span class="detail-value" id="detailBasePrice">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Class Multiplier:</span>
                            <span class="detail-value" id="detailClassMultiplier">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Amount:</span>
                            <span class="detail-value" id="detailAmount">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Status:</span>
                            <span id="detailPaymentStatus">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Method:</span>
                            <span class="detail-value">Paystack</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Paystack Reference:</span>
                            <span class="detail-value paystack-ref" id="detailPaystackRef">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Date:</span>
                            <span class="detail-value" id="detailPaymentDate">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="verifyPaymentBtn">
                        <i data-feather="shield-check"></i> Verify Payment
                    </button>
                    <button type="button" class="btn btn-info" id="checkAvailabilityBtn">
                        <i data-feather="search"></i> Check Seat Availability
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Improvement Modal -->
    <div class="modal fade" id="improvementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i data-feather="database"></i> Recommended Database Improvements
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Current Issue:</strong> Your flights table only has one price and seats_available field, which doesn't support class-based booking (Economy, Business, First Class).
                    </div>
                    
                    <h6 class="fw-bold mb-3">Recommended Changes:</h6>
                    
                    <div class="mb-4">
                        <h6>1. Add Class-Based Pricing to Flights Table:</h6>
                        <pre class="bg-light p-3 rounded"><code>ALTER TABLE flights ADD COLUMN economy_price DECIMAL(10,2) DEFAULT 0;
ALTER TABLE flights ADD COLUMN business_price DECIMAL(10,2) DEFAULT 0;
ALTER TABLE flights ADD COLUMN first_class_price DECIMAL(10,2) DEFAULT 0;</code></pre>
                    </div>

                    <div class="mb-4">
                        <h6>2. Add Class-Based Seat Availability:</h6>
                        <pre class="bg-light p-3 rounded"><code>ALTER TABLE flights ADD COLUMN economy_seats INT DEFAULT 0;
ALTER TABLE flights ADD COLUMN business_seats INT DEFAULT 0;
ALTER TABLE flights ADD COLUMN first_class_seats INT DEFAULT 0;</code></pre>
                    </div>

                    <div class="mb-4">
                        <h6>3. Add Seats Column to Bookings Table (if missing):</h6>
                        <pre class="bg-light p-3 rounded"><code>ALTER TABLE bookings ADD COLUMN seats INT NOT NULL DEFAULT 1 AFTER passengers;
UPDATE bookings SET seats = passengers WHERE seats = 1;</code></pre>
                    </div>

                    <div class="mb-4">
                        <h6>4. Update Existing Data (Example):</h6>
                        <pre class="bg-light p-3 rounded"><code>-- Set class-based pricing based on current price
UPDATE flights SET 
    economy_price = price,
    business_price = price * 2.5,
    first_class_price = price * 4.0;

-- Distribute seats across classes (example for 180-seat aircraft)
UPDATE flights SET 
    economy_seats = FLOOR(seats_available * 0.7),    -- 70% economy
    business_seats = FLOOR(seats_available * 0.25),   -- 25% business  
    first_class_seats = FLOOR(seats_available * 0.05) -- 5% first class
WHERE seats_available > 0;</code></pre>
                    </div>

                    <div class="alert alert-success">
                        <strong>Benefits:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Proper airline class-based booking system</li>
                            <li>Different pricing for Economy, Business, and First Class</li>
                            <li>Separate seat availability tracking per class</li>
                            <li>Better revenue management</li>
                            <li>Professional airline booking experience</li>
                            <li>Accurate seat tracking and availability</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Note:</strong> The paystack_reference field already exists in your bookings table, so you don't need to add it again.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="copyToClipboard()">
                        <i data-feather="copy"></i> Copy SQL Commands
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Seat Availability Modal -->
    <div class="modal fade" id="availabilityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i data-feather="search"></i> Seat Availability
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="spinner-border text-primary" role="status" id="availabilitySpinner">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div id="availabilityContent" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-primary">Economy Class</h5>
                                            <div class="display-6 fw-bold" id="economyAvailable">-</div>
                                            <small class="text-muted">seats available</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-warning">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-warning">Business Class</h5>
                                            <div class="display-6 fw-bold" id="businessAvailable">-</div>
                                            <small class="text-muted">seats available</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-success">First Class</h5>
                                            <div class="display-6 fw-bold" id="firstAvailable">-</div>
                                            <small class="text-muted">seats available</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-dark">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-dark">Total</h5>
                                            <div class="display-6 fw-bold" id="totalAvailable">-</div>
                                            <small class="text-muted">seats available</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
        <div id="liveToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toastMsg">Action completed</div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();

        // Copy SQL commands to clipboard
        function copyToClipboard() {
            const sqlCommands = `-- Add Class-Based Pricing to Flights Table
ALTER TABLE flights ADD COLUMN economy_price DECIMAL(10,2) DEFAULT 0;
ALTER TABLE flights ADD COLUMN business_price DECIMAL(10,2) DEFAULT 0;
ALTER TABLE flights ADD COLUMN first_class_price DECIMAL(10,2) DEFAULT 0;

-- Add Class-Based Seat Availability
ALTER TABLE flights ADD COLUMN economy_seats INT DEFAULT 0;
ALTER TABLE flights ADD COLUMN business_seats INT DEFAULT 0;
ALTER TABLE flights ADD COLUMN first_class_seats INT DEFAULT 0;

-- Add Seats Column to Bookings Table (if missing)
ALTER TABLE bookings ADD COLUMN seats INT NOT NULL DEFAULT 1 AFTER passengers;
UPDATE bookings SET seats = passengers WHERE seats = 1;

-- Update Existing Data (Example)
UPDATE flights SET 
    economy_price = price,
    business_price = price * 2.5,
    first_class_price = price * 4.0;

UPDATE flights SET 
    economy_seats = FLOOR(seats_available * 0.7),
    business_seats = FLOOR(seats_available * 0.25),
    first_class_seats = FLOOR(seats_available * 0.05)
WHERE seats_available > 0;`;

            navigator.clipboard.writeText(sqlCommands).then(() => {
                notify('SQL commands copied to clipboard!');
            }).catch(() => {
                notify('Failed to copy to clipboard', 'error');
            });
        }

        // Sidebar toggle for mobile
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        if (menuToggle) menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // Reveal on scroll
        const revealEls = document.querySelectorAll('.reveal');
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    io.unobserve(e.target);
                }
            });
        }, {
            threshold: .1,
            rootMargin: '0px 0px -50px 0px'
        });
        revealEls.forEach(el => io.observe(el));

        // Toast helper
        const toastEl = document.getElementById('liveToast');
        const toast = toastEl ? new bootstrap.Toast(toastEl) : null;

        function notify(msg, type = 'success') {
            if (!toast) return;
            const toastBody = document.getElementById('toastMsg');
            toastBody.textContent = msg;
            toastEl.className = `toast align-items-center border-0 text-bg-${type === 'error' ? 'danger' : 'success'}`;
            toast.show();
        }

        // Load bookings data from PHP
        let bookings = <?php echo json_encode($bookings); ?>;

        // Elements
        const tbody = document.getElementById('bookingsBody');
        const countText = document.getElementById('countText');
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const classFilter = document.getElementById('classFilter');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');

        // Helper functions
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-NG', {
                style: 'currency',
                currency: 'NGN'
            }).format(amount);
        }

        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('en-GB', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function formatDateTime(dateStr) {
            return new Date(dateStr).toLocaleString('en-GB', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function statusBadge(status) {
            const badges = {
                'pending': '<span class="status-pending">Pending</span>',
                'completed': '<span class="status-completed">Completed</span>'
            };
            return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
        }

        function paymentStatusBadge(status) {
            const badges = {
                'paid': '<span class="payment-success"><i data-feather="check-circle" style="width:14px;height:14px;"></i> Paid</span>',
                'pending': '<span class="payment-pending"><i data-feather
                 