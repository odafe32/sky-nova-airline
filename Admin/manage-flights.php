<?php
session_start();

// Authentication Check - Redirect if not logged in or not an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
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

// Get admin information
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'];
$admin_email = $_SESSION['user_email'];

// Get admin's first name for display
$first_name = explode(' ', $admin_name)[0];
$admin_initial = strtoupper(substr($first_name, 0, 1));

// Function to generate next flight number
function generateNextFlightNumber($pdo)
{
    try {
        // Get the highest flight number that starts with SKYNOVA
        $stmt = $pdo->query("SELECT flight_no FROM flights WHERE flight_no LIKE 'SKYNOVA%' ORDER BY flight_no DESC LIMIT 1");
        $result = $stmt->fetch();

        if ($result) {
            // Extract number from SKYNOVA001, SKYNOVA002, etc.
            $lastNumber = intval(substr($result['flight_no'], 4));
            $nextNumber = $lastNumber + 1;
        } else {
            // First flight
            $nextNumber = 1;
        }

        // Format as SKYNOVA001, SKYNOVA002, etc.
        return 'SKYNOVA' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        return 'SKYNOVA001'; // Default if error
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'generate_flight_data':
                $nextFlightNo = generateNextFlightNumber($pdo);
                echo json_encode([
                    'success' => true,
                    'flight_no' => $nextFlightNo
                ]);
                break;

            case 'add_flight':
                // Calculate total seats
                $totalSeats = intval($_POST['economy_seats']) + intval($_POST['business_seats']) + intval($_POST['first_class_seats']);
                
                $stmt = $pdo->prepare("
                    INSERT INTO flights (
                        airline, flight_no, origin, destination, flight_date, departure_time, arrival_time, aircraft, 
                        economy_price, business_price, first_class_price,
                        economy_seats, business_seats, first_class_seats,
                        seats_available, status, created_at
                    ) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())
                ");

                $result = $stmt->execute([
                    $_POST['airline'],
                    $_POST['flight_no'],
                    $_POST['origin'],
                    $_POST['destination'],
                    $_POST['flight_date'],
                    $_POST['departure_time'],
                    $_POST['arrival_time'],
                    $_POST['aircraft'],
                    $_POST['economy_price'],
                    $_POST['business_price'],
                    $_POST['first_class_price'],
                    $_POST['economy_seats'],
                    $_POST['business_seats'],
                    $_POST['first_class_seats'],
                    $totalSeats
                ]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Flight added successfully with Active status']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add flight']);
                }
                break;

            case 'update_flight':
                // Calculate total seats
                $totalSeats = intval($_POST['economy_seats']) + intval($_POST['business_seats']) + intval($_POST['first_class_seats']);
                
                $stmt = $pdo->prepare("
                    UPDATE flights 
                    SET flight_date = ?, departure_time = ?, arrival_time = ?, aircraft = ?, 
                        economy_price = ?, business_price = ?, first_class_price = ?,
                        economy_seats = ?, business_seats = ?, first_class_seats = ?,
                        seats_available = ?, status = ?
                    WHERE flight_id = ?
                ");

                $status = 'scheduled'; // Default to scheduled (Active)
                if ($_POST['status'] === 'Disabled') {
                    $status = 'disabled';
                } elseif ($_POST['status'] === 'Cancelled') {
                    $status = 'cancelled';
                }

                $result = $stmt->execute([
                    $_POST['flight_date'],
                    $_POST['departure_time'],
                    $_POST['arrival_time'],
                    $_POST['aircraft'],
                    $_POST['economy_price'],
                    $_POST['business_price'],
                    $_POST['first_class_price'],
                    $_POST['economy_seats'],
                    $_POST['business_seats'],
                    $_POST['first_class_seats'],
                    $totalSeats,
                    $status,
                    $_POST['flight_id']
                ]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Flight updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update flight']);
                }
                break;

            case 'toggle_status':
                // Get current status
                $stmt = $pdo->prepare("SELECT status FROM flights WHERE flight_id = ?");
                $stmt->execute([$_POST['flight_id']]);
                $flight = $stmt->fetch();

                if ($flight) {
                    $new_status = ($flight['status'] === 'scheduled') ? 'disabled' : 'scheduled';

                    $stmt = $pdo->prepare("UPDATE flights SET status = ? WHERE flight_id = ?");
                    $result = $stmt->execute([$new_status, $_POST['flight_id']]);

                    if ($result) {
                        $status_text = ($new_status === 'scheduled') ? 'enabled' : 'disabled';
                        echo json_encode(['success' => true, 'message' => "Flight {$status_text} successfully", 'new_status' => $new_status]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update flight status']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Flight not found']);
                }
                break;

            case 'cancel_flight':
                $stmt = $pdo->prepare("UPDATE flights SET status = 'cancelled' WHERE flight_id = ?");
                $result = $stmt->execute([$_POST['flight_id']]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Flight cancelled successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to cancel flight']);
                }
                break;

            case 'delete_flight':
                // Check if flight has any bookings first
                $stmt = $pdo->prepare("SELECT COUNT(*) as booking_count FROM bookings WHERE flight_id = ?");
                $stmt->execute([$_POST['flight_id']]);
                $bookingCheck = $stmt->fetch();

                if ($bookingCheck['booking_count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete flight with existing bookings. Cancel the flight instead.']);
                } else {
                    // Safe to delete - no bookings exist
                    $stmt = $pdo->prepare("DELETE FROM flights WHERE flight_id = ?");
                    $result = $stmt->execute([$_POST['flight_id']]);

                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Flight deleted permanently']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to delete flight']);
                    }
                }
                break;

            case 'check_flight_exists':
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM flights WHERE flight_no = ?");
                $stmt->execute([$_POST['flight_no']]);
                $result = $stmt->fetch();
                echo json_encode(['exists' => $result['count'] > 0]);
                break;

            case 'check_seat_availability':
                $stmt = $pdo->prepare("SELECT economy_seats, business_seats, first_class_seats FROM flights WHERE flight_id = ?");
                $stmt->execute([$_POST['flight_id']]);
                $flight = $stmt->fetch();
                
                if ($flight) {
                    $class = $_POST['class'];
                    $passengers = intval($_POST['passengers']);
                    $available = 0;
                    
                    switch ($class) {
                        case 'economy':
                            $available = intval($flight['economy_seats']);
                            break;
                        case 'business':
                            $available = intval($flight['business_seats']);
                            break;
                        case 'first_class':
                            $available = intval($flight['first_class_seats']);
                            break;
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'available' => $available >= $passengers,
                        'available_seats' => $available,
                        'requested_seats' => $passengers
                    ]);
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

// Get all flights for display
$flights = [];
try {
    $stmt = $pdo->query("
        SELECT 
            flight_id, airline, flight_no, origin, destination, flight_date, departure_time, arrival_time, aircraft,
            economy_price, business_price, first_class_price,
            economy_seats, business_seats, first_class_seats,
            seats_available, status, created_at
        FROM flights 
        ORDER BY created_at DESC
    ");
    $flights = $stmt->fetchAll();
} catch (PDOException $e) {
    $flights = [];
}

// Get real notifications from database using existing tables
$notifications = [];
$notification_count = 0;

try {
    // Get recent bookings (last 24 hours) using existing bookings table
    $bookingNotifications = [];
    $stmt = $pdo->query("
        SELECT 
            b.booking_id,
            b.booking_ref,
            b.flight_id,
            f.flight_no,
            f.origin,
            f.destination,
            b.created_at,
            u.full_name as passenger_name,
            b.total_amount,
            b.class,
            b.passengers
        FROM bookings b 
        JOIN flights f ON b.flight_id = f.flight_id 
        JOIN users u ON b.user_id = u.user_id
        WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY b.created_at DESC 
        LIMIT 5
    ");
    $bookingNotifications = $stmt->fetchAll();

    // Get recent payments (last 24 hours) - if payments table exists
    $paymentNotifications = [];
    $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("
            SELECT 
                p.payment_id,
                p.booking_id,
                p.amount,
                p.payment_reference,
                p.created_at,
                b.booking_ref
            FROM payments p
            JOIN bookings b ON p.booking_id = b.booking_id
            WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND p.status = 'completed'
            ORDER BY p.created_at DESC 
            LIMIT 5
        ");
        $paymentNotifications = $stmt->fetchAll();
    }

    // Combine all notifications
    foreach ($bookingNotifications as $booking) {
        $timeAgo = time_elapsed_string($booking['created_at']);
        $passengerText = $booking['passengers'] > 1 ? "{$booking['passengers']} passengers" : "1 passenger";
        $notifications[] = [
            'type' => 'booking',
            'icon' => 'calendar',
            'title' => "New booking {$booking['flight_no']}",
            'meta' => "{$timeAgo} · {$booking['origin']} → {$booking['destination']} · {$passengerText} · {$booking['class']}",
            'time' => $booking['created_at']
        ];
    }

    foreach ($paymentNotifications as $payment) {
        $timeAgo = time_elapsed_string($payment['created_at']);
        $notifications[] = [
            'type' => 'payment',
            'icon' => 'credit-card',
            'title' => 'Payment received',
            'meta' => "{$timeAgo} · Ref: {$payment['booking_ref']} · ₦" . number_format($payment['amount'], 2),
            'time' => $payment['created_at']
        ];
    }

    // Sort notifications by time (newest first)
    usort($notifications, function ($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });

    // Limit to 10 most recent notifications
    $notifications = array_slice($notifications, 0, 10);
    $notification_count = count($notifications);
} catch (PDOException $e) {
    $notifications = [];
    $notification_count = 0;
}

// Helper function to calculate time elapsed
function time_elapsed_string($datetime, $full = false)
{
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

// Generate initial flight data for the form
$next_flight_no = generateNextFlightNumber($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Manage Flights - Speed of Light Airlines Admin" />
    <meta name="keywords" content="airline admin, manage flights, schedule, pricing, seats">
    <meta name="author" content="Speed of Light Airlines" />
    <title>Manage Flights | Speed of Light Airlines</title>
    <link rel="icon" href="../User/assets/images/airline-favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
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
            --economy: #10b981;
            --business: #f59e0b;
            --first-class: #8b5cf6;
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
            background: linear-gradient(135deg, #38a169, #2f855a);
            color: #fff;
            padding: 12px 16px;
            margin-left: 260px;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, .1);
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

        /* Content and cards */
        .content {
            margin-left: 260px;
            padding: 22px 18px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        .page-title h1 {
            font-weight: 800;
            letter-spacing: .5px;
            background: linear-gradient(45deg, #38a169, #2f855a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.2rem;
            margin-bottom: 8px;
        }

        .page-title .text-muted {
            font-size: 1.1rem;
            color: #64748b;
            font-weight: 500;
        }

        .smart-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
            border: 1px solid rgba(56, 161, 105, .15);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(56, 161, 105, .12);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            backdrop-filter: blur(10px);
        }

        .smart-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 50px rgba(56, 161, 105, .25);
            border-color: rgba(56, 161, 105, .3);
        }

        .smart-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #38a169, #2f855a, #38a169);
            border-radius: 20px 20px 0 0;
        }

        .smart-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(56, 161, 105, 0.1), transparent);
            transition: left 0.8s ease;
        }

        .smart-card:hover::after {
            left: 100%;
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

        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus, .form-select:focus {
            border-color: #38a169;
            box-shadow: 0 0 0 3px rgba(56, 161, 105, 0.1);
            background: #fff;
            transform: translateY(-2px);
        }

        .form-control:hover, .form-select:hover {
            border-color: #38a169;
        }

        .input-group-text {
            background: linear-gradient(135deg, #38a169, #2f855a);
            border: none;
            color: white;
            border-radius: 0 10px 10px 0;
        }

        .invalid-feedback {
            font-size: 0.85rem;
            color: #e53e3e;
            margin-top: 4px;
        }

        .badge-soft {
            background: linear-gradient(135deg, rgba(56, 161, 105, 0.1), rgba(47, 133, 90, 0.1));
            color: #2f855a;
            border: 1px solid rgba(56, 161, 105, 0.2);
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .table-container {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 8px 32px rgba(56, 161, 105, 0.15);
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(56, 161, 105, 0.1);
        }

        .table-responsive {
            border-radius: 16px;
        }

        .table {
            margin-bottom: 0;
            background: transparent;
        }

        .table thead th {
            background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
            color: white;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            padding: 18px 12px;
            position: relative;
            text-align: center;
        }

        .table thead th:first-child {
            border-top-left-radius: 16px;
            text-align: left;
            padding-left: 20px;
        }

        .table thead th:last-child {
            border-top-right-radius: 16px;
            text-align: center;
        }

        .table thead th::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.3));
        }

        .table tbody td {
            vertical-align: middle;
            font-size: 0.9rem;
            padding: 16px 12px;
            border: none;
            border-bottom: 1px solid rgba(56, 161, 105, 0.1);
            text-align: center;
        }

        .table tbody td:first-child {
            text-align: left;
            font-weight: 700;
            color: #2d3748;
            padding-left: 20px;
        }

        .table tbody td:last-child {
            text-align: center;
        }

        .table tbody tr {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: rgba(255, 255, 255, 0.5);
        }

        .table tbody tr:nth-child(even) {
            background: rgba(56, 161, 105, 0.02);
        }

        .table tbody tr:nth-child(odd) {
            background: rgba(255, 255, 255, 0.8);
        }

        .table tbody tr:hover {
            background: linear-gradient(135deg, rgba(56, 161, 105, 0.08), rgba(47, 133, 90, 0.08));
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 25px rgba(56, 161, 105, 0.15);
            border-radius: 12px;
            margin: 0 4px;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status badges in table */
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .status-disabled {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }

        .status-cancelled {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        /* Enhanced seat info styling */
        .seat-info {
            display: flex;
            gap: 12px;
            font-size: 0.85rem;
            margin-top: 6px;
            justify-content: center;
        }

        .seat-count {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(248, 250, 252, 0.8));
            border-radius: 12px;
            border: 1px solid rgba(56, 161, 105, 0.15);
            transition: all 0.3s ease;
        }

        .seat-count:hover {
            background: linear-gradient(135deg, rgba(56, 161, 105, 0.1), rgba(47, 133, 90, 0.1));
            transform: translateY(-1px);
        }

        /* Enhanced flight number styling */
        .flight-number {
            background: linear-gradient(135deg, #38a169, #2f855a);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(56, 161, 105, 0.3);
        }

        /* Enhanced route styling */
        .flight-route {
            background: linear-gradient(135deg, rgba(56, 161, 105, 0.1), rgba(47, 133, 90, 0.1));
            padding: 6px 12px;
            border-radius: 16px;
            font-weight: 600;
            color: #2d3748;
            border: 1px solid rgba(56, 161, 105, 0.2);
        }

        .route-arrow {
            font-size: 1.2rem;
            color: #38a169;
            margin: 0 8px;
            font-weight: 700;
        }

        /* Enhanced empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.6;
        }

        .empty-state .empty-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2d3748;
        }

        .empty-state .empty-subtitle {
            font-size: 0.95rem;
            color: #64748b;
        }

        /* Enhanced search input */
        .search-container {
            position: relative;
        }

        .search-input {
            border: 2px solid rgba(56, 161, 105, 0.2);
            border-radius: 25px;
            padding: 10px 20px 10px 45px;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .search-input:focus {
            border-color: #38a169;
            box-shadow: 0 0 0 3px rgba(56, 161, 105, 0.1);
            background: white;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1rem;
        }

        /* Enhanced table wrapper */
        .table-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 40px rgba(56, 161, 105, 0.15);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(56, 161, 105, 0.1);
        }

        .table-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #38a169, #2f855a, #38a169);
            border-radius: 20px 20px 0 0;
        }

        .action-chip {
            border: none;
            border-radius: 12px;
            padding: 8px 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .action-chip::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .action-chip:hover::before {
            left: 100%;
        }

        .action-chip:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .chip-blue {
            background: linear-gradient(135deg, #38a169, #2f855a);
            box-shadow: 0 6px 20px rgba(56, 161, 105, 0.3);
        }

        .chip-green {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .chip-red {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }

        .chip-gray {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.3);
        }

        .chip-purple {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.3);
        }

        .chip-orange {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
        }

        .loading {
            opacity: 0.6;
            pointer-events: none
        }

        .auto-gen-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 15px;
            margin-left: 8px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Enhanced class sections */
        .class-section {
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .class-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #38a169, #2f855a);
        }

        .class-section.economy {
            border-color: #10b981;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(5, 150, 105, 0.05));
        }

        .class-section.business {
            border-color: #f59e0b;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), rgba(217, 119, 6, 0.05));
        }

        .class-section.first-class {
            border-color: #8b5cf6;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.05), rgba(109, 40, 217, 0.05));
        }

        .class-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .class-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .class-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: 800;
        }

        .class-icon.economy {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .class-icon.business {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .class-icon.first-class {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .class-badge {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 15px;
            color: white;
            margin-left: 8px;
            font-weight: 600;
        }

        .class-badge.economy {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .class-badge.business {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .class-badge.first-class {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .seat-info {
            display: flex;
            gap: 16px;
            font-size: 0.9rem;
            margin-top: 8px;
        }

        .seat-count {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            border: 1px solid rgba(56, 161, 105, 0.1);
        }

        /* Enhanced buttons */
        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #38a169, #2f855a);
            box-shadow: 0 4px 15px rgba(56, 161, 105, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(56, 161, 105, 0.4);
        }

        .btn-outline-primary {
            border: 2px solid #38a169;
            color: #38a169;
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #38a169, #2f855a);
            color: white;
            transform: translateY(-2px);
        }

        .btn-outline-secondary {
            border: 2px solid #cbd5e0;
            color: #4a5568;
            background: transparent;
        }

        .btn-outline-secondary:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }

        /* Modal enhancements */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            background: linear-gradient(135deg, #38a169, #2f855a);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 20px 24px;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.2rem;
        }

        .btn-close {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            opacity: 0.8;
        }

        .btn-close:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.3);
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
            <a href="manage-flights.php" class="nav-link active"><i data-feather="navigation"></i> Manage Flight</a>
            <a href="bookings.php" class="nav-link"><i data-feather="calendar"></i> Bookings</a>
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
                <span>Manage Flights • Add new flights with class-specific pricing • Update schedule and seat availability • Cancel or disable flights • Welcome back <?php echo htmlspecialchars($admin_name); ?> • </span>
                <span>Manage Flights • Add new flights with class-specific pricing • Update schedule and seat availability • Cancel or disable flights • Welcome back <?php echo htmlspecialchars($admin_name); ?> • </span>
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
                            <a href="bookings.php" class="small" style="color:var(--brand)">View all</a>
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
        <div class="page-title reveal mb-3">
            <h1>Manage Flights</h1>
            <div class="text-muted">Create, update and control flight schedules with class-specific pricing and seat availability.</div>
        </div>

        <!-- Add New Flight -->
        <div class="smart-card reveal mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="fw-bold">Add New Flight</div>
                <div class="d-flex gap-2">
                    <button class="action-chip chip-purple btn-sm" id="regenerateBtn">
                        <i data-feather="refresh-cw"></i> Generate Flight No.
                    </button>
                    <button class="btn btn-sm btn-outline-primary" id="toggleAdd">Hide</button>
                </div>
            </div>
            <form id="addFlightForm" novalidate>
                <!-- Basic Flight Information -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Airline</label>
                        <input type="text" class="form-control" id="airline" placeholder="e.g., Air France" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">
                            Flight No.
                            <span class="auto-gen-badge">AUTO</span>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="flightNo" value="<?php echo $next_flight_no; ?>" readonly style="background-color:#f8f9fa;font-weight:600;">
                            <button class="btn btn-outline-secondary" type="button" id="editFlightNo" title="Edit flight number">
                                <i data-feather="edit-2" style="width:14px;height:14px;"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Flight number already exists</div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="text" class="form-control" id="from" placeholder="IATA e.g., LOS" required maxlength="3" style="text-transform:uppercase">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="text" class="form-control" id="to" placeholder="IATA e.g., CDG" required maxlength="3" style="text-transform:uppercase">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-2">
                        <label class="form-label">Departure</label>
                        <input type="time" class="form-control" id="dep" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Arrival</label>
                        <input type="time" class="form-control" id="arr" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Aircraft</label>
                        <input type="text" class="form-control" id="aircraft" placeholder="e.g., Boeing 777-200" required>
                    </div>
                </div>

                <!-- Class-specific Pricing and Seats -->
                <div class="mb-3">
                    <h6 class="fw-bold mb-3">Class Configuration</h6>
                    
                    <!-- Economy Class -->
                    <div class="class-section economy">
                        <div class="class-header">
                            <div class="class-icon economy">E</div>
                            <span>Economy Class</span>
                            <span class="class-badge economy">Standard</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Price (NGN)</label>
                                <input type="number" step="0.01" class="form-control" id="economyPrice" placeholder="e.g., 450.00" required min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Available Seats</label>
                                <input type="number" class="form-control" id="economySeats" placeholder="e.g., 150" required min="0">
                            </div>
                        </div>
                    </div>

                    <!-- Business Class -->
                    <div class="class-section business">
                        <div class="class-header">
                            <div class="class-icon business">B</div>
                            <span>Business Class</span>
                            <span class="class-badge business">Premium</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Price (NGN)</label>
                                <input type="number" step="0.01" class="form-control" id="businessPrice" placeholder="e.g., 1200.00" required min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Available Seats</label>
                                <input type="number" class="form-control" id="businessSeats" placeholder="e.g., 30" required min="0">
                            </div>
                        </div>
                    </div>

                    <!-- First Class -->
                    <div class="class-section first-class">
                        <div class="class-header">
                            <div class="class-icon first-class">F</div>
                            <span>First Class</span>
                            <span class="class-badge first-class">Luxury</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Price (NGN)</label>
                                <input type="number" step="0.01" class="form-control" id="firstClassPrice" placeholder="e.g., 2500.00" required min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Available Seats</label>
                                <input type="number" class="form-control" id="firstClassSeats" placeholder="e.g., 12" required min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button class="action-chip chip-green" type="submit" id="addFlightBtn">
                        <i data-feather="check"></i> Add Flight (Auto-Active)
                    </button>
                    <div class="mt-2 small text-muted">
                        <i data-feather="info" style="width:14px;height:14px;"></i>
                        Total seats will be calculated automatically from all classes
                    </div>
                </div>
            </form>
        </div>

        <!-- Flights Table -->
        <div class="smart-card reveal">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="fw-bold">All Flights (<?php echo count($flights); ?>)</div>
                <div class="d-flex gap-2">
                    <div class="search-container">
                        <i data-feather="search" class="search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search by Flight No., Airline or Route">
                    </div>
                </div>
            </div>
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="flightsTable">
                        <thead>
                            <tr>
                                <th>Flight No.</th>
                                <th>Airline</th>
                                <th>Route</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Aircraft</th>
                                <th>Class Pricing</th>
                                <th>Seat Availability</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="flightsBody">
                            <?php if (empty($flights)): ?>
                                <tr>
                                    <td colspan="10" class="empty-state">
                                        <i data-feather="inbox"></i>
                                        <div class="empty-title">No flights found</div>
                                        <div class="empty-subtitle">Add your first flight above to get started.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i data-feather="edit-2"></i> Update Flight</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editFlightForm" novalidate>
                        <input type="hidden" id="editId">
                        
                        <!-- Basic Information -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" id="editDate" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Departure</label>
                                <input type="time" class="form-control" id="editDep" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Arrival</label>
                                <input type="time" class="form-control" id="editArr" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label">Aircraft</label>
                                <input type="text" class="form-control" id="editAircraft" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select id="editStatus" class="form-select" required>
                                    <option value="Active">Active</option>
                                    <option value="Disabled">Disabled</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <!-- Class Configuration -->
                        <h6 class="fw-bold mb-3">Class Configuration</h6>
                        
                        <!-- Economy Class -->
                        <div class="class-section economy mb-3">
                            <div class="class-header">
                                <div class="class-icon economy">E</div>
                                <span>Economy Class</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Price (NGN)</label>
                                    <input type="number" step="0.01" class="form-control" id="editEconomyPrice" required min="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Available Seats</label>
                                    <input type="number" class="form-control" id="editEconomySeats" required min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Business Class -->
                        <div class="class-section business mb-3">
                            <div class="class-header">
                                <div class="class-icon business">B</div>
                                <span>Business Class</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Price (NGN)</label>
                                    <input type="number" step="0.01" class="form-control" id="editBusinessPrice" required min="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Available Seats</label>
                                    <input type="number" class="form-control" id="editBusinessSeats" required min="0">
                                </div>
                            </div>
                        </div>

                        <!-- First Class -->
                        <div class="class-section first-class mb-3">
                            <div class="class-header">
                                <div class="class-icon first-class">F</div>
                                <span>First Class</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Price (NGN)</label>
                                    <input type="number" step="0.01" class="form-control" id="editFirstClassPrice" required min="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Available Seats</label>
                                    <input type="number" class="form-control" id="editFirstClassSeats" required min="0">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="saveEditBtn" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        Save changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i data-feather="x-circle"></i> Cancel Flight</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">Provide a reason for cancellation. This will be logged for audit.</div>
                    <input type="hidden" id="cancelId">
                    <textarea id="cancelReason" class="form-control" rows="3" placeholder="e.g., Operational issues, weather, maintenance" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="confirmCancelBtn" class="btn btn-danger">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        Confirm Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i data-feather="trash-2"></i> Delete Flight</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i data-feather="alert-triangle" class="me-2"></i>
                        <strong>Warning!</strong> This action cannot be undone.
                    </div>
                    <div class="mb-2">Are you sure you want to permanently delete this flight?</div>
                    <div class="small text-muted">Note: Flights with existing bookings cannot be deleted and must be cancelled instead.</div>
                    <input type="hidden" id="deleteId">
                    <div id="deleteFlightInfo" class="mt-3 p-3 bg-light rounded"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        <i data-feather="trash-2"></i> Delete Permanently
                    </button>
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

        // Load flights from PHP
        let flights = <?php echo json_encode($flights); ?>;

        const tbody = document.getElementById('flightsBody');
        const searchInput = document.getElementById('searchInput');

        function statusBadge(st) {
            if (st === 'scheduled') return '<span class="status-badge status-active">Active</span>';
            if (st === 'disabled') return '<span class="status-badge status-disabled">Disabled</span>';
            return '<span class="status-badge status-cancelled">Cancelled</span>';
        }

        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString();
        }

        function formatClassPricing(flight) {
            return `
                <div class="seat-info">
                    <div class="seat-count">
                        <span class="class-badge economy">E</span>
                        <span>₦${Number(flight.economy_price || 0).toFixed(0)}</span>
                    </div>
                    <div class="seat-count">
                        <span class="class-badge business">B</span>
                        <span>₦${Number(flight.business_price || 0).toFixed(0)}</span>
                    </div>
                    <div class="seat-count">
                        <span class="class-badge first-class">F</span>
                        <span>₦${Number(flight.first_class_price || 0).toFixed(0)}</span>
                    </div>
                </div>
            `;
        }

        function formatSeatAvailability(flight) {
            return `
                <div class="seat-info">
                    <div class="seat-count">
                        <span class="class-badge economy">E</span>
                        <span>${flight.economy_seats || 0}</span>
                    </div>
                    <div class="seat-count">
                        <span class="class-badge business">B</span>
                        <span>${flight.business_seats || 0}</span>
                    </div>
                    <div class="seat-count">
                    <span class="class-badge first-class">F</span>
                        <span>${flight.first_class_seats || 0}</span>
                    </div>
                </div>
            `;
        }

        function renderTable() {
            const q = (searchInput.value || '').toLowerCase();
            tbody.innerHTML = '';

            const filteredFlights = flights.filter(f => !q ||
                (f.flight_no + f.airline + f.origin + f.destination).toLowerCase().includes(q)
            );

            if (filteredFlights.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="empty-state">
                            <i data-feather="search"></i>
                            <div class="empty-title">${q ? 'No flights match your search.' : 'No flights found'}</div>
                            <div class="empty-subtitle">${q ? 'Try adjusting your search terms.' : 'Add your first flight above to get started.'}</div>
                        </td>
                    </tr>
                `;
                feather.replace();
                return;
            }

            filteredFlights.forEach(f => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><span class="flight-number">${f.flight_no}</span></td>
                    <td>${f.airline}</td>
                    <td><span class="flight-route">${f.origin} <span class="route-arrow">→</span> ${f.destination}</span></td>
                    <td>${formatDate(f.flight_date)}</td>
                    <td>${f.departure_time} - ${f.arrival_time}</td>
                    <td>${f.aircraft}</td>
                    <td>${formatClassPricing(f)}</td>
                    <td>${formatSeatAvailability(f)}</td>
                    <td>${statusBadge(f.status)}</td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="#" data-action="edit" data-id="${f.flight_id}">
                                        <i data-feather="edit-2"></i><span>Edit</span>
                                    </a>
                                </li>
                                ${f.status==='cancelled' ? '' : `
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="#" data-action="toggle" data-id="${f.flight_id}">
                                        <i data-feather="power"></i><span>${f.status==='scheduled'?'Disable':'Enable'}</span>
                                    </a>
                                </li>`}
                                ${f.status!=='cancelled' ? `
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="#" data-action="cancel" data-id="${f.flight_id}">
                                        <i data-feather="x-circle"></i><span>Cancel</span>
                                    </a>
                                </li>` : ''}
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="#" data-action="delete" data-id="${f.flight_id}">
                                        <i data-feather="trash-2"></i><span>Delete</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>`;
                tbody.appendChild(tr);
            });
            feather.replace();
        }

        renderTable();

        // Search
        if (searchInput) searchInput.addEventListener('input', renderTable);

        // Toggle add form
        const toggleAddBtn = document.getElementById('toggleAdd');
        const addForm = document.getElementById('addFlightForm');
        toggleAddBtn.addEventListener('click', () => {
            const isHidden = addForm.classList.contains('d-none');
            addForm.classList.toggle('d-none');
            toggleAddBtn.textContent = isHidden ? 'Hide' : 'Show';
        });

        // Set minimum date to today
        document.getElementById('date').min = new Date().toISOString().split('T')[0];
        document.getElementById('editDate').min = new Date().toISOString().split('T')[0];

        // Generate new flight numbers
        document.getElementById('regenerateBtn').addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
            btn.disabled = true;

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=generate_flight_data'
                });
                const result = await response.json();

                if (result.success) {
                    document.getElementById('flightNo').value = result.flight_no;
                    notify('New flight number generated!');
                } else {
                    notify('Error generating flight data', 'error');
                }
            } catch (error) {
                notify('Error generating flight data', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        // Edit flight number
        document.getElementById('editFlightNo').addEventListener('click', function() {
            const flightNoInput = document.getElementById('flightNo');
            if (flightNoInput.readOnly) {
                flightNoInput.readOnly = false;
                flightNoInput.style.backgroundColor = '#fff';
                flightNoInput.style.fontWeight = 'normal';
                flightNoInput.focus();
                this.innerHTML = '<i data-feather="check" style="width:14px;height:14px;"></i>';
                this.title = 'Confirm edit';
            } else {
                flightNoInput.readOnly = true;
                flightNoInput.style.backgroundColor = '#f8f9fa';
                flightNoInput.style.fontWeight = '600';
                this.innerHTML = '<i data-feather="edit-2" style="width:14px;height:14px;"></i>';
                this.title = 'Edit flight number';
            }
            feather.replace();
        });

        // Flight number validation (when manually edited)
        document.getElementById('flightNo').addEventListener('blur', async function() {
            if (this.readOnly) return; // Skip validation if readonly

            const flightNo = this.value.trim();
            if (!flightNo) return;

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=check_flight_exists&flight_no=${encodeURIComponent(flightNo)}`
                });
                const result = await response.json();

                if (result.exists) {
                    this.setCustomValidity('Flight number already exists');
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                }
            } catch (error) {
                console.error('Error checking flight:', error);
            }
        });

        // Add flight
        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!addForm.checkValidity()) {
                addForm.classList.add('was-validated');
                notify('Please fill in all required fields correctly', 'error');
                return;
            }

            const addBtn = document.getElementById('addFlightBtn');
            const originalText = addBtn.innerHTML;
            addBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
            addBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'add_flight');
                formData.append('airline', document.getElementById('airline').value.trim());
                formData.append('flight_no', document.getElementById('flightNo').value.trim());
                formData.append('origin', document.getElementById('from').value.trim().toUpperCase());
                formData.append('destination', document.getElementById('to').value.trim().toUpperCase());
                formData.append('flight_date', document.getElementById('date').value);
                formData.append('departure_time', document.getElementById('dep').value);
                formData.append('arrival_time', document.getElementById('arr').value);
                formData.append('aircraft', document.getElementById('aircraft').value.trim());
                formData.append('economy_price', document.getElementById('economyPrice').value);
                formData.append('business_price', document.getElementById('businessPrice').value);
                formData.append('first_class_price', document.getElementById('firstClassPrice').value);
                formData.append('economy_seats', document.getElementById('economySeats').value);
                formData.append('business_seats', document.getElementById('businessSeats').value);
                formData.append('first_class_seats', document.getElementById('firstClassSeats').value);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    notify(result.message);
                    addForm.reset();
                    addForm.classList.remove('was-validated');
                    // Reload page to refresh flights list
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error adding flight. Please try again.', 'error');
                console.error('Error:', error);
            } finally {
                addBtn.innerHTML = originalText;
                addBtn.disabled = false;
            }
        });

        // Row actions
        tbody.addEventListener('click', async (e) => {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;

            const id = btn.getAttribute('data-id');
            const f = flights.find(x => x.flight_id == id);
            const action = btn.getAttribute('data-action');

            if (action === 'edit') {
                document.getElementById('editId').value = f.flight_id;
                document.getElementById('editDate').value = f.flight_date;
                document.getElementById('editDep').value = f.departure_time;
                document.getElementById('editArr').value = f.arrival_time;
                document.getElementById('editAircraft').value = f.aircraft;
                document.getElementById('editEconomyPrice').value = f.economy_price;
                document.getElementById('editBusinessPrice').value = f.business_price;
                document.getElementById('editFirstClassPrice').value = f.first_class_price;
                document.getElementById('editEconomySeats').value = f.economy_seats;
                document.getElementById('editBusinessSeats').value = f.business_seats;
                document.getElementById('editFirstClassSeats').value = f.first_class_seats;
                document.getElementById('editStatus').value = f.status === 'scheduled' ? 'Active' : (f.status === 'disabled' ? 'Disabled' : 'Cancelled');
                new bootstrap.Modal(document.getElementById('editModal')).show();
            }

            if (action === 'toggle') {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
                btn.disabled = true;

                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=toggle_status&flight_id=${id}`
                    });

                    const result = await response.json();

                    if (result.success) {
                        notify(result.message);
                        // Update local data
                        f.status = result.new_status;
                        renderTable();
                    } else {
                        notify(result.message, 'error');
                    }
                } catch (error) {
                    notify('Error updating flight status', 'error');
                } finally {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            }

            if (action === 'cancel') {
                document.getElementById('cancelId').value = f.flight_id;
                document.getElementById('cancelReason').value = '';
                new bootstrap.Modal(document.getElementById('cancelModal')).show();
            }

            if (action === 'delete') {
                document.getElementById('deleteId').value = f.flight_id;
                document.getElementById('deleteFlightInfo').innerHTML = `
                    <strong>Flight:</strong> ${f.flight_no}<br>
                    <strong>Route:</strong> ${f.origin} → ${f.destination}<br>
                    <strong>Date:</strong> ${formatDate(f.flight_date)}<br>
                    <strong>Status:</strong> ${f.status}
                `;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            }
        });

        // Save edit
        document.getElementById('saveEditBtn').addEventListener('click', async () => {
            const editForm = document.getElementById('editFlightForm');
            if (!editForm.checkValidity()) {
                editForm.classList.add('was-validated');
                return;
            }

            const saveBtn = document.getElementById('saveEditBtn');
            const spinner = saveBtn.querySelector('.spinner-border');
            spinner.classList.remove('d-none');
            saveBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'update_flight');
                formData.append('flight_id', document.getElementById('editId').value);
                formData.append('flight_date', document.getElementById('editDate').value);
                formData.append('departure_time', document.getElementById('editDep').value);
                formData.append('arrival_time', document.getElementById('editArr').value);
                formData.append('aircraft', document.getElementById('editAircraft').value);
                formData.append('economy_price', document.getElementById('editEconomyPrice').value);
                formData.append('business_price', document.getElementById('editBusinessPrice').value);
                formData.append('first_class_price', document.getElementById('editFirstClassPrice').value);
                formData.append('economy_seats', document.getElementById('editEconomySeats').value);
                formData.append('business_seats', document.getElementById('editBusinessSeats').value);
                formData.append('first_class_seats', document.getElementById('editFirstClassSeats').value);
                formData.append('status', document.getElementById('editStatus').value);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    notify(result.message);
                    const em = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                    if (em) em.hide();
                    // Reload page to refresh flights list
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error updating flight', 'error');
            } finally {
                spinner.classList.add('d-none');
                saveBtn.disabled = false;
            }
        });

        // Confirm cancel
        document.getElementById('confirmCancelBtn').addEventListener('click', async () => {
            const reason = document.getElementById('cancelReason').value.trim();
            if (!reason) {
                notify('Please provide a cancellation reason', 'error');
                return;
            }

            const cancelBtn = document.getElementById('confirmCancelBtn');
            const spinner = cancelBtn.querySelector('.spinner-border');
            spinner.classList.remove('d-none');
            cancelBtn.disabled = true;

            try {
                const id = document.getElementById('cancelId').value;
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=cancel_flight&flight_id=${id}&reason=${encodeURIComponent(reason)}`
                });

                const result = await response.json();

                if (result.success) {
                    notify(result.message);
                    const cm = bootstrap.Modal.getInstance(document.getElementById('cancelModal'));
                    if (cm) cm.hide();
                    // Reload page to refresh flights list
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error cancelling flight', 'error');
            } finally {
                spinner.classList.add('d-none');
                cancelBtn.disabled = false;
            }
        });

        // Confirm delete
        document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
            const deleteBtn = document.getElementById('confirmDeleteBtn');
            const spinner = deleteBtn.querySelector('.spinner-border');
            spinner.classList.remove('d-none');
            deleteBtn.disabled = true;

            try {
                const id = document.getElementById('deleteId').value;
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=delete_flight&flight_id=${id}`
                });

                const result = await response.json();

                if (result.success) {
                    notify(result.message);
                    const dm = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                    if (dm) dm.hide();
                    // Reload page to refresh flights list
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error deleting flight', 'error');
            } finally {
                spinner.classList.add('d-none');
                deleteBtn.disabled = false;
            }
        });

        // Auto-uppercase airport codes
        document.getElementById('from').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        document.getElementById('to').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });

        // Seat availability checker function for user bookings
        window.checkSeatAvailability = async function(flightId, classType, passengers) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=check_seat_availability&flight_id=${flightId}&class=${classType}&passengers=${passengers}`
                });

                const result = await response.json();
                return result;
            } catch (error) {
                console.error('Error checking seat availability:', error);
                return { success: false, message: 'Error checking seat availability' };
            }
        };
    </script>
</body>

</html>
</qodoArtifact>
