<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'airlines';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];
// Clean up expired cart items (optional) - ADD THIS SECTION
$stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND flight_id IN (SELECT flight_id FROM flights WHERE flight_date < CURDATE())");
$stmt->execute([$user_id]);

// CHANGE 1: Function to get pending payments count (instead of cart count)
// Function to get cart items count
function getCartCount($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['cart_count'];
}
// Get user information
$stmt = $pdo->prepare("SELECT user_id, full_name, email, phone, avatar FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// CHANGE 1: Get pending payments count for navbar (instead of cart count)
// Get cart items count for navbar
$cart_count = getCartCount($pdo, $user_id);

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    try {
        $booking_id = intval($_POST['booking_id']);

        $pdo->beginTransaction();

        // Get booking details before cancellation
        $stmt = $pdo->prepare("
            SELECT b.*, f.economy_seats, f.business_seats, f.first_class_seats 
            FROM bookings b 
            JOIN flights f ON b.flight_id = f.flight_id 
            WHERE b.booking_id = ? AND b.user_id = ? AND b.status IN ('Pending', 'Confirmed')
        ");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            // Update booking status to cancelled
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'Cancelled', updated_at = NOW() WHERE booking_id = ?");
            $stmt->execute([$booking_id]);

            // Restore seat availability if booking was confirmed
            if ($booking['status'] === 'Confirmed') {
                $seat_column = strtolower($booking['class']) . '_seats';
                $current_seats = $booking[$seat_column];
                $new_seats = $current_seats + $booking['passengers'];

                $stmt = $pdo->prepare("UPDATE flights SET {$seat_column} = ? WHERE flight_id = ?");
                $stmt->execute([$new_seats, $booking['flight_id']]);
            }

            $pdo->commit();
            $success_message = "Booking cancelled successfully. Refund will be processed within 3-5 business days.";
        } else {
            throw new Exception("Booking not found or cannot be cancelled.");
        }
    } catch (Exception $e) {
        $pdo->rollback();
        $error_message = "Failed to cancel booking: " . $e->getMessage();
    }
}

// Get filter parameter
$filter = $_GET['filter'] ?? 'all';
$valid_filters = ['all', 'upcoming', 'completed', 'cancelled', 'pending'];
if (!in_array($filter, $valid_filters)) {
    $filter = 'all';
}

// Build query based on filter
$where_clause = "WHERE b.user_id = ?";
$params = [$user_id];

if ($filter !== 'all') {
    if ($filter === 'upcoming') {
        $where_clause .= " AND b.status = 'Confirmed' AND f.flight_date >= CURDATE()";
    } elseif ($filter === 'completed') {
        $where_clause .= " AND b.status = 'Confirmed' AND f.flight_date < CURDATE()";
    } elseif ($filter === 'cancelled') {
        $where_clause .= " AND b.status = 'Cancelled'";
    } elseif ($filter === 'pending') {
        // For pending filter, we'll handle this separately 
        $show_cart_items = true;
    }
}

// Get all bookings for the user - FIXED: Added table aliases to avoid ambiguity
// Get bookings or cart items based on filter
if (isset($show_cart_items) && $show_cart_items) {
    // Show cart items for pending filter (only future flights)
    $stmt = $pdo->prepare("
        SELECT c.*, f.airline, f.flight_no, f.origin, f.destination, f.flight_date,
               f.departure_time, f.arrival_time, f.aircraft, c.cart_id as booking_id,
               c.booking_reference as booking_ref, 'Pending' as status
        FROM cart c
        JOIN flights f ON c.flight_id = f.flight_id
        WHERE c.user_id = ? AND f.flight_date >= CURDATE()
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Show regular bookings
    $stmt = $pdo->prepare("
        SELECT b.*, f.airline, f.flight_no, f.origin, f.destination, f.flight_date,
               f.departure_time, f.arrival_time, f.aircraft
        FROM bookings b
        JOIN flights f ON b.flight_id = f.flight_id
        {$where_clause}
        ORDER BY b.created_at DESC
    ");
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Get booking statistics - FIXED: Added table aliases
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN b.status = 'Confirmed' AND f.flight_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming,
        SUM(CASE WHEN b.status = 'Confirmed' AND f.flight_date < CURDATE() THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN b.status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN b.status = 'Pending' THEN 1 ELSE 0 END) as pending
    FROM bookings b
    JOIN flights f ON b.flight_id = f.flight_id
    WHERE b.user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Function to determine booking status
function getBookingStatus($booking)
{
    if ($booking['status'] === 'Cancelled') {
        return ['status' => 'cancelled', 'label' => 'Cancelled', 'icon' => 'x-circle'];
    } elseif ($booking['status'] === 'Pending') {
        return ['status' => 'pending', 'label' => 'Pending Payment', 'icon' => 'clock'];
    } elseif ($booking['status'] === 'Confirmed') {
        if (strtotime($booking['flight_date']) < strtotime('today')) {
            return ['status' => 'completed', 'label' => 'Completed', 'icon' => 'check-circle'];
        } else {
            return ['status' => 'upcoming', 'label' => 'Upcoming', 'icon' => 'calendar'];
        }
    }
    return ['status' => 'unknown', 'label' => 'Unknown', 'icon' => 'help-circle'];
}

// Function to get airline logo
function getAirlineLogo($airline)
{
    $logos = [
        'Air France' => 'https://cdn.airpaz.com/cdn-cgi/image/w=54,h=54,f=webp/rel-0275/airlines/201x201/AF.png',
        'British Airways' => 'https://cdn.airpaz.com/cdn-cgi/image/w=54,h=54,f=webp/rel-0275/airlines/201x201/BA.png',
        'Emirates' => 'https://cdn.airpaz.com/cdn-cgi/image/w=54,h=54,f=webp/rel-0275/airlines/201x201/EK.png',
        'Lufthansa' => 'https://cdn.airpaz.com/cdn-cgi/image/w=54,h=54,f=webp/rel-0275/airlines/201x201/LH.png',
        'Turkish Airlines' => 'https://cdn.airpaz.com/cdn-cgi/image/w=54,h=54,f=webp/rel-0275/airlines/201x201/TK.png',
        'Qatar Airways' => 'https://cdn.airpaz.com/cdn-cgi/image/w=54,h=54,f=webp/rel-0275/airlines/201x201/QR.png'
    ];

    return $logos[$airline] ?? 'https://via.placeholder.com/54x54/00539C/FFFFFF?text=' . strtoupper(substr($airline, 0, 2));
}

// Get user's first name and avatar initial
$full_name = $user['full_name'];
$first_name = explode(' ', $full_name)[0];
$avatar_initial = strtoupper(substr($full_name, 0, 1));
$user_avatar = $user['avatar'] ? $user['avatar'] : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Bookings | Speed of Light Airlines</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="My Bookings - Speed of Light Airlines" />
    <meta name="keywords" content="airline, booking, flights, dashboard">
    <meta name="author" content="Speed of Light Airlines" />
    <link rel="icon" href="assets/images/airline-favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <!-- Feather Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8fafc;
            min-height: 100vh;
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(0, 83, 156, 0.06);
            border-radius: 50%;
            animation: float 12s ease-in-out infinite;
        }

        .particle:nth-child(1) {
            width: 60px;
            height: 60px;
            left: 10%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            width: 40px;
            height: 40px;
            left: 30%;
            animation-delay: 3s;
        }

        .particle:nth-child(3) {
            width: 80px;
            height: 80px;
            left: 50%;
            animation-delay: 6s;
        }

        .particle:nth-child(4) {
            width: 30px;
            height: 30px;
            left: 70%;
            animation-delay: 9s;
        }

        .particle:nth-child(5) {
            width: 50px;
            height: 50px;
            left: 90%;
            animation-delay: 12s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.4;
            }

            50% {
                transform: translateY(-150px) rotate(180deg);
                opacity: 0.1;
            }
        }

        .navbar {
            background: #38a169;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 83, 156, 0.15);
        }

        .navbar-brand {
            color: #fff !important;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 1.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .navbar-toggler {
            border: none;
            color: #fff;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        /* Enhanced Header User Section */
        .navbar-user-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-left: auto;
        }

        .cart-icon-container {
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .cart-icon-container:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .cart-icon {
            color: #fff;
            font-size: 1.3rem;
            transition: all 0.3s ease;
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: #fff;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            animation: pulse 2s infinite;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.4);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #48bb78, #38a169);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-name {
            font-size: 1rem;
            font-weight: 600;
        }

        .sidebar {
            background: #38a169;
            min-height: 100vh;
            padding-top: 30px;
            z-index: 100;
            box-shadow: 2px 0 20px rgba(0, 51, 102, 0.1);
        }

        .sidebar .nav-link {
            color: #fff;
            font-weight: 500;
            margin-bottom: 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            padding: 12px 20px;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 83, 156, 0.3), transparent);
            transition: left 0.5s;
        }

        .sidebar .nav-link:hover::before {
            left: 100%;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: #38a169;
            color: #fff;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .main-content {
            padding: 40px 20px 80px 20px;
            position: relative;
            z-index: 10;
        }

        .bookings-header {
            color: #38a169;
            text-align: center;
            margin-bottom: 50px;
            animation: fadeInDown 1s ease-out;
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.05) 0%, rgba(0, 51, 102, 0.05) 100%);
            padding: 40px 20px;
            border-radius: 24px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 83, 156, 0.1);
        }

        .bookings-header h1 {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #38a169, #38a169);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .bookings-header p {
            font-size: 1.2rem;
            opacity: 0.8;
            font-weight: 400;
        }

        /* Statistics Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 83, 156, 0.1);
            border: 1px solid rgba(0, 83, 156, 0.1);
            transition: all 0.3s ease;
            animation: fadeInUp 1s ease-out;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 83, 156, 0.15);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: #fff;
        }

        .stat-total {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .stat-upcoming {
            background: linear-gradient(135deg, #48bb78, #38a169);
        }

        .stat-completed {
            background: linear-gradient(135deg, #4299e1, #3182ce);
        }

        .stat-cancelled {
            background: linear-gradient(135deg, #f56565, #e53e3e);
        }

        .stat-pending {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #38a169;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Filter Controls */
        .filter-controls {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 40px;
            box-shadow: 0 4px 20px rgba(0, 83, 156, 0.1);
            border: 1px solid rgba(0, 83, 156, 0.1);
            animation: fadeInUp 1s ease-out;
        }

        .filter-btn {
            background: rgba(0, 83, 156, 0.1);
            color: #38a169;
            border: 2px solid rgba(0, 83, 156, 0.2);
            border-radius: 12px;
            padding: 10px 20px;
            margin: 5px;
            transition: all 0.3s ease;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: #38a169;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
            text-decoration: none;
        }

        /* Timeline View Toggle */
        .view-toggle {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 5px;
            display: inline-flex;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 83, 156, 0.1);
        }

        .view-toggle button {
            background: transparent;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .view-toggle button.active {
            background: #38a169;
            color: #fff;
            box-shadow: 0 2px 8px rgba(0, 83, 156, 0.3);
        }

        /* Smart Booking Cards */
        .booking-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 83, 156, 0.1);
            margin-bottom: 30px;
            padding: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: fadeInUp 1s ease-out;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 83, 156, 0.1);
        }

        .booking-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #38a169, #38a169);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .booking-card:hover::before {
            transform: scaleX(1);
        }

        .booking-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 83, 156, 0.2);
            background: rgba(255, 255, 255, 1);
        }

        .booking-card-header {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.05) 0%, rgba(0, 51, 102, 0.05) 100%);
            padding: 20px 25px;
            border-bottom: 1px solid rgba(0, 83, 156, 0.1);
            position: relative;
        }

        .booking-status {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-upcoming {
            background: linear-gradient(135deg, #e3fcec, #c8f7c5);
            color: #1b5e20;
            box-shadow: 0 2px 10px rgba(27, 94, 32, 0.2);
        }

        .status-completed {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1565c0;
            box-shadow: 0 2px 10px rgba(21, 101, 192, 0.2);
        }

        .status-cancelled {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            color: #c62828;
            box-shadow: 0 2px 10px rgba(198, 40, 40, 0.2);
        }

        .status-pending {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            color: #e65100;
            box-shadow: 0 2px 10px rgba(230, 81, 0, 0.2);
        }

        .airline-section {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .airline-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-right: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.1);
            transition: transform 0.3s ease;
        }

        .booking-card:hover .airline-logo {
            transform: scale(1.1) rotate(5deg);
        }

        .airline-info h4 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #38a169;
            margin-bottom: 5px;
        }

        .flight-number {
            color: #666;
            font-weight: 500;
            font-size: 1rem;
        }

        .flight-route {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 25px 0;
            position: relative;
        }

        .route-point {
            text-align: center;
            flex: 1;
        }

        .route-code {
            font-size: 1.8rem;
            font-weight: 700;
            color: #38a169;
            margin-bottom: 5px;
        }

        .route-city {
            color: #666;
            font-size: 0.9rem;
        }

        .route-arrow {
            margin: 0 20px;
            color: #38a169;
            font-size: 1.5rem;
            position: relative;
        }

        .route-arrow::before {
            content: '✈️';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.2rem;
            animation: fly 3s ease-in-out infinite;
        }

        @keyframes fly {

            0%,
            100% {
                transform: translateX(-50%) translateY(0);
            }

            50% {
                transform: translateX(-50%) translateY(-5px);
            }
        }

        .flight-details {
            background: rgba(0, 83, 156, 0.02);
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }

        .detail-item {
            text-align: center;
            padding: 10px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.1);
        }

        .detail-label {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-weight: 700;
            color: #38a169;
            font-size: 1.1rem;
        }

        .booking-actions {
            padding: 20px 25px;
            background: rgba(0, 83, 156, 0.02);
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-width: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .action-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            text-decoration: none;
        }

        .btn-view {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: #fff;
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.3);
        }

        .btn-view:hover {
            color: #fff;
            box-shadow: 0 8px 25px rgba(66, 153, 225, 0.4);
        }

        .btn-cancel {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: #fff;
            box-shadow: 0 4px 15px rgba(245, 101, 101, 0.3);
        }

        .btn-cancel:hover {
            color: #fff;
            box-shadow: 0 8px 25px rgba(245, 101, 101, 0.4);
        }

        .btn-download {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: #fff;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
        }

        .btn-download:hover {
            color: #fff;
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.4);
        }

        .btn-pay {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
            color: #fff;
            box-shadow: 0 4px 15px rgba(237, 137, 54, 0.3);
        }

        .btn-pay:hover {
            color: #fff;
            box-shadow: 0 8px 25px rgba(237, 137, 54, 0.4);
        }

        /* Timeline View */
        .timeline-view {
            position: relative;
            padding-left: 30px;
        }

        .timeline-view::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #38a169, #38a169);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 20px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #38a169;
            box-shadow: 0 0 0 4px rgba(0, 83, 156, 0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state img {
            width: 200px;
            opacity: 0.5;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #38a169;
            margin-bottom: 15px;
        }

        .empty-state p {
            margin-bottom: 30px;
        }

        .empty-state .btn {
            background: linear-gradient(135deg, #38a169, #38a169);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .empty-state .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 83, 156, 0.3);
            color: #fff;
        }

        .footer {
            background: #38a169;
            color: #fff;
            text-align: center;
            padding: 18px 0 10px 0;
            margin-top: 40px;
            letter-spacing: 1px;
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Alert Styles */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #f0fff4, #e6fffa);
            color: #2f855a;
            border-left: 4px solid #38a169;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee, #fdd);
            color: #c53030;
            border-left: 4px solid #e53e3e;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .sidebar {
                min-height: auto;
                padding-top: 0;
            }

            .main-content {
                padding: 30px 15px 80px 15px;
            }

            .navbar-user-section {
                gap: 15px;
            }
        }

        @media (max-width: 767px) {
            .bookings-header h1 {
                font-size: 2.2rem;
            }

            .booking-card {
                margin-bottom: 20px;
            }

            .route-code {
                font-size: 1.4rem;
            }

            .detail-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .particles {
                display: none;
            }

            .navbar-user-section {
                flex-direction: column;
                gap: 10px;
            }

            .user-info {
                order: 2;
            }

            .cart-icon-container {
                order: 1;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 83, 156, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(0, 83, 156, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #38a169;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: #666;
        }

        .detail-value {
            font-weight: 600;
            color: #333;
        }

        .booking-detail-modal {
            padding: 20px 0;
        }
    </style>
</head>

<body>
    <!-- Animated Background Particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- Enhanced Navbar with Cart Icon and User Info -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="../pexels-sevenstormphotography-728824 (1).jpg" alt="Logo" style="width:38px; margin-right:10px; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                SKYNOVA
            </a>

            <!-- CHANGE 1: Enhanced User Section with Pending Payments Icon -->
            <div class="navbar-user-section">
                <div class="cart-icon-container" onclick="window.location.href='checkout.php'">
                    <i data-feather="shopping-cart" class="cart-icon"></i>
                    <?php if ($cart_count > 0): ?>
                        <div class="cart-badge"><?php echo $cart_count; ?></div>
                    <?php endif; ?>
                </div>

                <div class="user-info" onclick="window.location.href='profile.php'">
                    <div class="user-avatar">
                        <?php if ($user_avatar): ?>
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="User Avatar">
                        <?php else: ?>
                            <?php echo $avatar_initial; ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-name"><?php echo htmlspecialchars($first_name); ?></div>
                </div>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span><i data-feather="menu"></i></span>
            </button>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-lg-2 col-md-3 d-lg-block sidebar collapse">
                <div class="position-sticky">
                    <ul class="nav flex-column mt-4">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i data-feather="home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="book-flight.php">
                                <i data-feather="send"></i> Book a Flight
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="my-bookings.php">
                                <i data-feather="calendar"></i> My Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i data-feather="user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i data-feather="log-out"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-lg-10 col-md-9 ms-sm-auto main-content">
                <div class="bookings-header animate__animated animate__fadeInDown">
                    <h1>My Bookings</h1>
                    <p>Manage your flight bookings with style and ease.</p>
                </div>
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success animate__animated animate__fadeInDown">
                        <i data-feather="check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger animate__animated animate__fadeInDown">
                        <i data-feather="alert-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon stat-total">
                            <i data-feather="calendar"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-upcoming">
                            <i data-feather="clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['upcoming']; ?></div>
                        <div class="stat-label">Upcoming</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-completed">
                            <i data-feather="check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['completed']; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-cancelled">
                            <i data-feather="x-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['cancelled']; ?></div>
                        <div class="stat-label">Cancelled</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-pending">
                            <i data-feather="shopping-cart"></i>
                        </div>
                        <div class="stat-number"><?php echo $cart_count; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div class="filter-controls text-center">
                    <a href="my-bookings.php?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        <i data-feather="list"></i> All Bookings
                    </a>
                    <a href="my-bookings.php?filter=upcoming" class="filter-btn <?php echo $filter === 'upcoming' ? 'active' : ''; ?>">
                        <i data-feather="clock"></i> Upcoming
                    </a>
                    <a href="my-bookings.php?filter=completed" class="filter-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                        <i data-feather="check-circle"></i> Completed
                    </a>
                    <a href="my-bookings.php?filter=cancelled" class="filter-btn <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">
                        <i data-feather="x-circle"></i> Cancelled
                    </a>
                    <a href="my-bookings.php?filter=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                        <i data-feather="clock"></i> Pending Payment
                    </a>
                </div>

                <!-- View Toggle -->
                <div class="text-center mb-4">
                    <div class="view-toggle">
                        <button class="active" data-view="card">
                            <i data-feather="grid"></i> Card View
                        </button>
                        <button data-view="timeline">
                            <i data-feather="list"></i> Timeline View
                        </button>
                    </div>
                </div>

                <!-- Bookings Container -->
                <div id="bookingsContainer">
                    <?php if (empty($bookings)): ?>
                        <div class="empty-state">
                            <img src="https://cdn.airpaz.com/cdn-cgi/image/w=400,h=400,f=webp/forerunner-next/img/illustration/v2/spot/empty-booking.png" alt="No Bookings">
                            <h3>No Bookings Found</h3>
                            <p>You haven't made any bookings yet. Start your journey by booking your first flight!</p>
                            <a href="book-flight.php" class="btn">
                                <i data-feather="plus"></i> Book Your First Flight
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($bookings as $index => $booking): ?>
                            <?php $status = getBookingStatus($booking); ?>
                            <div class="booking-card animate__animated animate__fadeInUp" data-status="<?php echo $status['status']; ?>" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                                <div class="booking-status status-<?php echo $status['status']; ?>">
                                    <i data-feather="<?php echo $status['icon']; ?>"></i> <?php echo $status['label']; ?>
                                </div>

                                <div class="booking-card-header">
                                    <div class="airline-section">
                                        <img src="<?php echo getAirlineLogo($booking['airline']); ?>" alt="<?php echo htmlspecialchars($booking['airline']); ?>" class="airline-logo">
                                        <div class="airline-info">
                                            <h4><?php echo htmlspecialchars($booking['airline']); ?></h4>
                                            <div class="flight-number"><?php echo htmlspecialchars($booking['flight_no']); ?></div>
                                        </div>
                                    </div>

                                    <div class="flight-route">
                                        <div class="route-point">
                                            <div class="route-code"><?php echo htmlspecialchars($booking['origin']); ?></div>
                                            <div class="route-city">Departure</div>
                                        </div>
                                        <div class="route-arrow">
                                            <i data-feather="arrow-right"></i>
                                        </div>
                                        <div class="route-point">
                                            <div class="route-code"><?php echo htmlspecialchars($booking['destination']); ?></div>
                                            <div class="route-city">Arrival</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flight-details">
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <div class="detail-label">Date</div>
                                            <div class="detail-value"><?php echo date('M d, Y', strtotime($booking['flight_date'])); ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Departure</div>
                                            <div class="detail-value"><?php echo date('H:i', strtotime($booking['departure_time'])); ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Arrival</div>
                                            <div class="detail-value"><?php echo date('H:i', strtotime($booking['arrival_time'])); ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Class</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($booking['class']); ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Passengers</div>
                                            <div class="detail-value"><?php echo $booking['passengers']; ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Aircraft</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($booking['aircraft']); ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Booking Ref</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($booking['booking_ref']); ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Amount</div>
                                            <div class="detail-value">₦<?php echo number_format($booking['total_amount'], 2); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="booking-actions">
                                    <button class="action-btn btn-view" data-bs-toggle="modal" data-bs-target="#viewModal"
                                        onclick="loadBookingDetails(<?php echo $booking['booking_id']; ?>)">
                                        <i data-feather="eye"></i> View Details
                                    </button>

                                    <?php if ($status['status'] === 'pending'): ?>
                                        <?php if (isset($show_cart_items) && $show_cart_items): ?>
                                            <a href="checkout.php?cart_id=<?php echo $booking['cart_id']; ?>" class="action-btn btn-pay">
                                                <i data-feather="shopping-cart"></i> Complete Payment
                                            </a>
                                        <?php else: ?>
                                            <a href="checkout.php?booking_id=<?php echo $booking['booking_id']; ?>" class="action-btn btn-pay">
                                                <i data-feather="shopping-cart"></i> Complete Payment
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($status['status'] === 'upcoming'): ?>
                                        <button class="action-btn btn-cancel" data-bs-toggle="modal" data-bs-target="#cancelModal"
                                            onclick="setCancelBooking(<?php echo $booking['booking_id']; ?>, '<?php echo htmlspecialchars($booking['booking_ref']); ?>')">
                                            <i data-feather="x-circle"></i> Cancel Booking
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($status['status'] === 'completed'): ?>
                                        <button class="action-btn btn-download" onclick="downloadTicket(<?php echo $booking['booking_id']; ?>)">
                                            <i data-feather="download"></i> Download Ticket
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <footer class="footer">
        &copy; <span id="year"></span> SKYNOVA Airlines. All Rights Reserved.  
    </footer>

    <!-- Modals -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="viewModalLabel">
                        <i data-feather="info"></i> Booking Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewModalBody">
                    <div class="text-center">
                        <div class="loading-spinner"></div>
                        <p class="mt-2">Loading booking details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelModalLabel">
                        <i data-feather="alert-triangle"></i> Cancel Booking
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i data-feather="alert-octagon" class="text-danger" style="width:48px;height:48px;"></i>
                    <h5 class="mt-3">Are you sure you want to cancel this booking?</h5>
                    <p class="text-muted">This action cannot be undone. Cancellation fees may apply.</p>
                    <p><strong>Booking Reference: <span id="cancelBookingRef"></span></strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Booking</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="cancel_booking" value="1">
                        <input type="hidden" name="booking_id" id="cancelBookingId">
                        <button type="submit" class="btn btn-danger">Yes, Cancel Booking</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();
        document.getElementById('year').textContent = new Date().getFullYear();

        // Sidebar toggler for mobile
        document.querySelectorAll('.navbar-toggler').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var sidebar = document.getElementById('sidebarMenu');
                if (sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                } else {
                    sidebar.classList.add('show');
                }
            });
        });

        // Close sidebar on nav-link click (mobile)
        document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                var sidebar = document.getElementById('sidebarMenu');
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('show');
                }
            });
        });

        // Cart icon click animation
        const cartIconEl = document.querySelector('.cart-icon-container');
        if (cartIconEl) {
            cartIconEl.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        }

        // Load booking details function
        function loadBookingDetails(bookingId) {
            const modalBody = document.getElementById('viewModalBody');
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="loading-spinner"></div>
                    <p class="mt-2">Loading booking details...</p>
                </div>
            `;

            // Fetch booking details from server
            fetch('get-booking-details.php?booking_id=' + bookingId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalBody.innerHTML = `
                            <div class="booking-detail-modal">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3"><i data-feather="send"></i> Flight Information</h6>
                                        <div class="detail-row">
                                            <span class="detail-label">Airline:</span>
                                            <span class="detail-value">${data.booking.airline}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Flight Number:</span>
                                            <span class="detail-value">${data.booking.flight_no}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Route:</span>
                                            <span class="detail-value">${data.booking.origin} → ${data.booking.destination}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Date:</span>
                                            <span class="detail-value">${data.booking.flight_date}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Departure:</span>
                                            <span class="detail-value">${data.booking.departure_time}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Arrival:</span>
                                            <span class="detail-value">${data.booking.arrival_time}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3"><i data-feather="user"></i> Passenger Information</h6>
                                        <div class="detail-row">
                                            <span class="detail-label">Name:</span>
                                            <span class="detail-value">${data.booking.passenger_name}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Email:</span>
                                            <span class="detail-value">${data.booking.passenger_email}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Phone:</span>
                                            <span class="detail-value">${data.booking.passenger_phone}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Class:</span>
                                            <span class="detail-value">${data.booking.class}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Passengers:</span>
                                            <span class="detail-value">${data.booking.passengers}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Booking Reference:</span>
                                            <span class="detail-value">${data.booking.booking_ref}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Total Amount:</span>
                                            <span class="detail-value">₦${parseFloat(data.booking.total_amount).toLocaleString()}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Status:</span>
                                            <span class="detail-value badge bg-success">${data.booking.status}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        modalBody.innerHTML = `
                            <div class="text-center">
                                <i data-feather="alert-circle" class="text-danger" style="width:48px;height:48px;"></i>
                                <h5 class="mt-3">Error Loading Details</h5>
                                <p class="text-muted">${data.message || 'Unable to load booking details.'}</p>
                            </div>
                        `;
                    }
                    feather.replace();
                })
                .catch(error => {
                    modalBody.innerHTML = `
                        <div class="text-center">
                            <i data-feather="wifi-off" class="text-warning" style="width:48px;height:48px;"></i>
                            <h5 class="mt-3">Connection Error</h5>
                            <p class="text-muted">Please check your internet connection and try again.</p>
                        </div>
                    `;
                    feather.replace();
                });
        }

        // Set cancel booking function
        function setCancelBooking(bookingId, bookingRef) {
            document.getElementById('cancelBookingId').value = bookingId;
            document.getElementById('cancelBookingRef').textContent = bookingRef;
        }

        // CHANGE 2: Enhanced download ticket function with PDF generation
        function downloadTicket(bookingId) {
            // Find the button that was clicked
            const btn = document.querySelector(`button[onclick="downloadTicket(${bookingId})"]`);
            if (!btn) return;

            const originalText = btn.innerHTML;

            // Disable button and show loading
            btn.innerHTML = '<span class="loading-spinner"></span> Generating Ticket...';
            btn.disabled = true;

            // Create form and submit to download ticket
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'download-ticket.php';
            form.style.display = 'none';
            form.target = '_blank';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'booking_id';
            input.value = bookingId;

            form.appendChild(input);
            document.body.appendChild(form);

            // Submit form
            setTimeout(() => {
                form.submit();

                // Reset button after delay
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    document.body.removeChild(form);
                }, 2000);
            }, 1000);
        }

        // View toggle functionality
        document.querySelectorAll('.view-toggle button').forEach(function(btn) {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.view-toggle button').forEach(function(b) {
                    b.classList.remove('active');
                });

                // Add active class to clicked button
                this.classList.add('active');

                const view = this.getAttribute('data-view');
                const container = document.getElementById('bookingsContainer');

                if (view === 'timeline') {
                    container.classList.add('timeline-view');
                    document.querySelectorAll('.booking-card').forEach(function(card, index) {
                        card.classList.add('timeline-item');
                        card.style.animationDelay = (index * 0.1) + 's';
                    });
                } else {
                    container.classList.remove('timeline-view');
                    document.querySelectorAll('.booking-card').forEach(function(card) {
                        card.classList.remove('timeline-item');
                    });
                }

                feather.replace();
            });
        });

        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';

        // Entrance animations on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all booking cards
        document.querySelectorAll('.booking-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            });
        }, 5000);

        // Enhanced card interactions
        document.querySelectorAll('.booking-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Close any open modals
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                });
            }
        });

        console.log('My Bookings page loaded successfully');
        console.log('Total booking cards:', document.querySelectorAll('.booking-card').length);
        console.log('Filter functionality enabled');
        console.log('View toggle functionality enabled');
        console.log('Modal functionality enabled');
        console.log('PDF download functionality enabled');
        console.log('Animation observers initialized');
        console.log('Keyboard navigation enabled');
        console.log('Cart count:', <?php echo $cart_count; ?>);
    </script>
</body>

</html>