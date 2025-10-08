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
try {
    $stmt = $pdo->prepare("SELECT full_name, email FROM admins WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();

    if ($admin) {
        $admin_name = $admin['full_name'];
        $admin_email = $admin['email'];
    } else {
        $admin_name = 'Admin';
        $admin_email = '';
    }
} catch (PDOException $e) {
    $admin_name = 'Admin';
    $admin_email = '';
}

// Get admin's first name for display
$first_name = explode(' ', $admin_name)[0];
$admin_initial = strtoupper(substr($first_name, 0, 1));

// Handle AJAX requests for filtering
if (isset($_POST['action']) && $_POST['action'] === 'get_transactions') {
    $search = $_POST['search'] ?? '';
    $status = $_POST['status'] ?? '';
    $method = $_POST['method'] ?? '';
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';

    // Build query with filters
    $where_conditions = ['b.total_amount > 0']; // Only show transactions with amounts
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(b.booking_ref LIKE ? OR u.full_name LIKE ? OR b.payment_reference LIKE ? OR b.paystack_reference LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status)) {
        if ($status === 'confirmed') {
            $where_conditions[] = "(b.status IN ('confirmed', 'completed', 'Confirmed', 'Completed') OR b.payment_status IN ('paid', 'Paid', 'completed', 'Completed', 'success', 'Success'))";
        } else if ($status === 'pending') {
            $where_conditions[] = "(b.status = 'pending' OR b.payment_status = 'pending')";
        } else {
            $where_conditions[] = "b.status = ?";
            $params[] = $status;
        }
    }

    if (!empty($date_from)) {
        $where_conditions[] = "DATE(b.created_at) >= ?";
        $params[] = $date_from;
    }

    if (!empty($date_to)) {
        $where_conditions[] = "DATE(b.created_at) <= ?";
        $params[] = $date_to;
    }

    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

    $stmt = $pdo->prepare("
        SELECT 
            b.booking_ref,
            u.full_name as user_name,
            COALESCE(b.payment_reference, b.paystack_reference, 'N/A') as paystack_reference,
            b.created_at,
            CASE 
                WHEN b.status IN ('confirmed', 'completed', 'Confirmed', 'Completed') 
                     OR b.payment_status IN ('paid', 'Paid', 'completed', 'Completed', 'success', 'Success') 
                THEN 'confirmed'
                WHEN b.status = 'pending' OR b.payment_status = 'pending' THEN 'pending'
                WHEN b.status IN ('cancelled', 'canceled') THEN 'cancelled'
                ELSE COALESCE(b.status, 'pending')
            END as status,
            b.total_amount,
            f.flight_no,
            f.origin,
            f.destination
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN flights f ON b.flight_id = f.flight_id
        $where_clause
        ORDER BY b.created_at DESC
    ");

    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $transactions]);
    exit();
}

// Get payment statistics - CORRECTED VERSION
$payment_stats = [
    'total_revenue' => 0,
    'completed_count' => 0,
    'pending_count' => 0
];

try {
    // Debug: Check what data exists in tables
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings");
    $bookings_count = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM cart");
    $cart_count = $stmt->fetchColumn();
    
    error_log("=== PAYMENT STATS DEBUG ===");
    error_log("Total bookings: " . $bookings_count);
    error_log("Total cart items: " . $cart_count);

    // 1. TOTAL REVENUE - Sum from completed bookings only
    if ($bookings_count > 0) {
        // Check what status values exist in bookings
        $stmt = $pdo->query("SELECT DISTINCT status FROM bookings WHERE status IS NOT NULL");
        $booking_statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Booking statuses found: " . implode(', ', $booking_statuses));
        
        $stmt = $pdo->query("SELECT DISTINCT payment_status FROM bookings WHERE payment_status IS NOT NULL");
        $payment_statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Payment statuses found: " . implode(', ', $payment_statuses));

        // Calculate total revenue from completed/confirmed bookings
        $stmt = $pdo->query("
            SELECT SUM(total_amount) as total_revenue 
            FROM bookings 
            WHERE (
                status IN ('confirmed', 'completed', 'Confirmed', 'Completed') 
                OR payment_status IN ('paid', 'Paid', 'completed', 'Completed', 'success', 'Success')
            )
            AND total_amount > 0
        ");
        $result = $stmt->fetch();
        $payment_stats['total_revenue'] = floatval($result['total_revenue'] ?? 0);
        
        // If still 0, let's see what data we have
        if ($payment_stats['total_revenue'] == 0) {
            $stmt = $pdo->query("SELECT booking_ref, total_amount, status, payment_status FROM bookings WHERE total_amount > 0 LIMIT 5");
            $sample_bookings = $stmt->fetchAll();
            error_log("Sample bookings with amounts: " . json_encode($sample_bookings));
            
            // Try summing ALL bookings to see if there's any revenue at all
            $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM bookings WHERE total_amount > 0");
            $total_all = $stmt->fetch();
            error_log("Total of ALL bookings: " . ($total_all['total'] ?? 0));
        }

        // 2. COMPLETED COUNT - Count of successful bookings
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE (
                status IN ('confirmed', 'completed', 'Confirmed', 'Completed') 
                OR payment_status IN ('paid', 'Paid', 'completed', 'Completed', 'success', 'Success')
            )
        ");
        $result = $stmt->fetch();
        $payment_stats['completed_count'] = intval($result['count']);
    }

    // 3. PENDING COUNT - Count from cart table (items waiting to be booked)
    if ($cart_count > 0) {
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM cart 
            WHERE status IS NULL OR status = 'pending' OR status = 'active'
        ");
        $result = $stmt->fetch();
        $payment_stats['pending_count'] = intval($result['count']);
        
        // If no pending in cart, check for pending bookings
        if ($payment_stats['pending_count'] == 0 && $bookings_count > 0) {
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM bookings 
                WHERE status = 'pending' OR payment_status = 'pending'
            ");
            $result = $stmt->fetch();
            $payment_stats['pending_count'] = intval($result['count']);
        }
    } else {
        // No cart table data, check bookings for pending
        if ($bookings_count > 0) {
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM bookings 
                WHERE status = 'pending' OR payment_status = 'pending'
            ");
            $result = $stmt->fetch();
            $payment_stats['pending_count'] = intval($result['count']);
        }
    }

    // Debug final results
    error_log("Final calculated stats:");
    error_log("Total revenue: " . $payment_stats['total_revenue']);
    error_log("Completed count: " . $payment_stats['completed_count']);
    error_log("Pending count: " . $payment_stats['pending_count']);

} catch (PDOException $e) {
    error_log("Payment stats error: " . $e->getMessage());
    // Set default values on error
    $payment_stats = [
        'total_revenue' => 0,
        'completed_count' => 0,
        'pending_count' => 0
    ];
}

// Get all transactions for initial load - FIXED QUERY
$transactions = [];
try {
    $stmt = $pdo->query("
        SELECT 
            b.booking_ref,
            u.full_name as user_name,
            COALESCE(b.payment_reference, b.paystack_reference, 'N/A') as paystack_reference,
            b.created_at,
            CASE 
                WHEN b.status IN ('confirmed', 'completed', 'Confirmed', 'Completed') 
                     OR b.payment_status IN ('paid', 'Paid', 'completed', 'Completed', 'success', 'Success') 
                THEN 'confirmed'
                WHEN b.status = 'pending' OR b.payment_status = 'pending' THEN 'pending'
                WHEN b.status IN ('cancelled', 'canceled') THEN 'cancelled'
                ELSE COALESCE(b.status, 'pending')
            END as status,
            b.total_amount,
            f.flight_no,
            f.origin,
            f.destination
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN flights f ON b.flight_id = f.flight_id
        WHERE b.total_amount > 0
        ORDER BY b.created_at DESC
        LIMIT 50
    ");
    $transactions = $stmt->fetchAll();
    
    error_log("Loaded " . count($transactions) . " transactions");
    
} catch (PDOException $e) {
    error_log("Transactions query error: " . $e->getMessage());
    $transactions = [];
}

// Get monthly revenue data for chart - FIXED QUERY
$monthly_revenue = [];
try {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(total_amount) as revenue
        FROM bookings 
        WHERE (
            status IN ('confirmed', 'completed', 'Confirmed', 'Completed') 
            OR payment_status IN ('paid', 'Paid', 'completed', 'Completed', 'success', 'Success')
        )
        AND total_amount > 0
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthly_revenue = $stmt->fetchAll();
    
    error_log("Monthly revenue data points: " . count($monthly_revenue));
    
} catch (PDOException $e) {
    error_log("Monthly revenue query error: " . $e->getMessage());
    $monthly_revenue = [];
}

// Get recent notifications
$notifications = [];
$notification_count = 0;

try {
    // Get recent bookings (last 24 hours)
    $stmt = $pdo->query("
        SELECT 
            b.booking_ref,
            b.total_amount,
            b.status,
            b.created_at,
            u.full_name as user_name,
            f.flight_no
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN flights f ON b.flight_id = f.flight_id
        WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $recent_bookings = $stmt->fetchAll();

    foreach ($recent_bookings as $booking) {
        $timeAgo = time_elapsed_string($booking['created_at']);
        $icon = $booking['status'] === 'confirmed' ? 'check-circle' : 'clock';
        $notifications[] = [
            'icon' => $icon,
            'title' => $booking['status'] === 'confirmed' ? 'Payment confirmed' : 'New booking',
            'meta' => "{$booking['flight_no']} · ₦" . number_format($booking['total_amount'], 2) . " · {$timeAgo}",
            'time' => $booking['created_at']
        ];
    }

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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Payments - Speed of Light Airlines Admin" />
    <meta name="keywords" content="airline admin, payments, transactions, revenue">
    <meta name="author" content="Speed of Light Airlines" />
    <title>Payments | Speed of Light Airlines</title>
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
            --info: #0ea5e9;
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
            background:#318a5d;
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

        .kpi-card {
            background: var(--card-bg);
            border: 1px solid rgba(0, 83, 156, .08);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(0, 83, 156, .10);
            position: relative;
            overflow: hidden;
            transition: transform .35s cubic-bezier(.175, .885, .32, 1.275), box-shadow .35s
        }

        .kpi-card:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 18px 50px rgba(0, 83, 156, .20)
        }

        .kpi-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 50%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255, 255, 255, .25), transparent);
            transform: skewX(-20deg);
            transition: left 1s ease
        }

        .kpi-card:hover::after {
            left: 150%
        }

        .kpi-title {
            color: var(--muted);
            font-weight: 600
        }

        .kpi-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--brand)
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

        .table thead th {
            color: var(--brand-dark);
            font-weight: 700;
            border-bottom: 2px solid rgba(0, 83, 156, .15)
        }

        .table tbody td {
            vertical-align: middle
        }

        .badge-soft {
            background: rgba(0, 83, 156, .08);
            color: var(--brand-dark);
            border: 1px solid rgba(0, 83, 156, .15)
        }

        .export-btn {
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            box-shadow: 0 8px 22px rgba(14, 165, 233, .25)
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
            <a href="manage-flights.php" class="nav-link"><i data-feather="navigation"></i> Manage Flight</a>
            <a href="bookings.php" class="nav-link"><i data-feather="calendar"></i> Bookings</a>
            <a href="users.php" class="nav-link"><i data-feather="users"></i> User Management</a>
            <a href="payments.php" class="nav-link active"><i data-feather="credit-card"></i> Payments</a>
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
                <span>Payments • View completed, pending transactions • Monitor total revenue from bookings • Welcome back <?php echo htmlspecialchars($admin_name); ?> • </span>
                <span>Payments • View completed, pending transactions • Monitor total revenue from bookings • Welcome back <?php echo htmlspecialchars($admin_name); ?> • </span>
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
            <h1>Payments</h1>
            <div class="text-muted">View all completed and pending transactions. Monitor total revenue from bookings.</div>
        </div>

        <!-- KPIs (Removed Failed Card) -->
        <div class="row g-3">
            <div class="col-12 col-md-4 reveal">
                <div class="kpi-card h-100">
                    <div class="kpi-title">Total Revenue</div>
                    <div class="kpi-value" id="kpiRevenue">₦<?php echo number_format($payment_stats['total_revenue'], 2); ?></div>
                    <div class="text-success fw-semibold"><i data-feather="trending-up" style="width:16px;height:16px"></i> Live</div>
                </div>
            </div>
            <div class="col-12 col-md-4 reveal">
                <div class="kpi-card h-100">
                    <div class="kpi-title">Completed</div>
                    <div class="kpi-value" id="kpiCompleted"><?php echo $payment_stats['completed_count']; ?></div>
                    <div class="text-success fw-semibold">Settled payments</div>
                </div>
            </div>
            <div class="col-12 col-md-4 reveal">
                <div class="kpi-card h-100">
                    <div class="kpi-title">Pending</div>
                    <div class="kpi-value" id="kpiPending"><?php echo $payment_stats['pending_count']; ?></div>
                    <div class="text-warning fw-semibold">Awaiting confirmation</div>
                </div>
            </div>
        </div>

        <!-- Revenue Chart & Filters -->
        <div class="row g-3 mt-1">
            <div class="col-xl-8 reveal">
                <div class="smart-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold">Revenue Overview</div>
                        <div class="text-muted small">Monthly</div>
                    </div>
                    <div id="chartRevenue" style="height:300px"></div>
                </div>
            </div>
            <div class="col-xl-4 reveal">
                <div class="smart-card">
                    <div class="fw-bold mb-2">Filters</div>
                    <div class="row g-2">
                        <div class="col-12">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search by ref, user, flight">
                        </div>
                        <div class="col-12">
                            <select id="statusFilter" class="form-select">
                                <option value="">All Status</option>
                                <option value="confirmed">Completed</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <input type="date" id="dateFrom" class="form-control" placeholder="From">
                        </div>
                        <div class="col-6">
                            <input type="date" id="dateTo" class="form-control" placeholder="To">
                        </div>
                        <div class="col-6 d-grid">
                            <button class="btn btn-primary" id="applyFilters"><i data-feather="filter"></i> Apply</button>
                        </div>
                        <div class="col-6 d-grid">
                            <button class="export-btn" id="exportCsv"><i data-feather="download"></i> Export CSV</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="smart-card reveal mt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-bold">Transactions</div>
                <div class="small text-muted" id="countText"><?php echo count($transactions); ?> transaction(s)</div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="txTable">
                    <thead>
                        <tr>
                            <th>Booking Ref</th>
                            <th>User</th>
                            <th>Flight</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="txBody">
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i data-feather="inbox" style="width:48px;height:48px;margin-bottom:12px;"></i><br>
                                    No transactions found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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

        // Load transactions from PHP
        let transactions = <?php echo json_encode($transactions); ?>;
        let monthlyRevenue = <?php echo json_encode($monthly_revenue); ?>;

        // Elements
        const txBody = document.getElementById('txBody');
        const countText = document.getElementById('countText');
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');

        // Status badges
        function statusBadge(status) {
            // Convert to lowercase for comparison
            const statusLower = (status || '').toLowerCase();

            if (statusLower === 'confirmed' || statusLower === 'completed') {
                return '<span class="badge bg-success">Confirmed</span>';
            }
            if (statusLower === 'pending') {
                return '<span class="badge bg-warning text-dark">Pending</span>';
            }
            if (statusLower === 'cancelled' || statusLower === 'canceled') {
                return '<span class="badge bg-danger">Cancelled</span>';
            }

            // Show the actual status value if it doesn't match expected values
            return '<span class="badge bg-secondary">' + status + '</span>';
        }

        // Format date
        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString();
        }

        // Render table
        function renderTable(data = transactions) {
            txBody.innerHTML = '';
            countText.textContent = `${data.length} transaction(s)`;

            if (data.length === 0) {
                txBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i data-feather="search" style="width:48px;height:48px;margin-bottom:12px;"></i><br>
                            No transactions match your filters.
                        </td>
                    </tr>
                `;
                feather.replace();
                return;
            }

            data.forEach(tx => {
                const tr = document.createElement('tr');
                tr.className = 'animate__animated animate__fadeInUp';
                tr.innerHTML = `
                    <td><strong>${tx.booking_ref}</strong></td>
                    <td>${tx.user_name}</td>
                    <td>${tx.flight_no} <small class="text-muted">(${tx.origin} → ${tx.destination})</small></td>
                    <td>${formatDate(tx.created_at)}</td>
                    <td>${statusBadge(tx.status)}</td>
                    <td class="text-end"><strong>NGN ${Number(tx.total_amount).toFixed(2)}</strong></td>
                `;
                txBody.appendChild(tr);
            });
            feather.replace();
            updateKPIs(data);
        }

        // Update KPIs based on filtered data
        function updateKPIs(data) {
            const completed = data.filter(x => x.status === 'confirmed');
            const pending = data.filter(x => x.status === 'pending');
            const revenue = completed.reduce((sum, x) => sum + Number(x.total_amount), 0);

            document.getElementById('kpiRevenue').textContent = `NGN ${revenue.toFixed(2)}`;
            document.getElementById('kpiCompleted').textContent = completed.length;
            document.getElementById('kpiPending').textContent = pending.length;
        }

        // Revenue chart
        let chart;

        function initRevenueChart() {
            const categories = monthlyRevenue.map(item => item.month);
            const series = monthlyRevenue.map(item => Number(item.revenue));

            const options = {
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: {
                        show: false
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                colors: ['#22c55e'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        opacityFrom: 0.45,
                        opacityTo: 0.05
                    }
                },
                series: [{
                    name: 'Revenue',
                    data: series
                }],
                xaxis: {
                    categories: categories,
                    labels: {
                        style: {
                            colors: '#64748b'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#64748b'
                        },
                        formatter: (val) => `NGN ${val.toFixed(0)}`
                    }
                },
                grid: {
                    borderColor: 'rgba(100,116,139,.15)'
                },
                tooltip: {
                    y: {
                        formatter: (val) => `NGN ${val.toFixed(2)}`
                    }
                }
            };

            chart = new ApexCharts(document.querySelector('#chartRevenue'), options);
            chart.render();
        }

        // Apply filters
        async function applyFilters() {
            const filters = {
                action: 'get_transactions',
                search: searchInput.value,
                status: statusFilter.value,
                date_from: dateFrom.value,
                date_to: dateTo.value
            };

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(filters)
                });

                const result = await response.json();
                if (result.success) {
                    transactions = result.data;
                    renderTable(transactions);
                } else {
                    console.error('Error fetching transactions:', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // Export CSV
        document.getElementById('exportCsv').addEventListener('click', () => {
            const rows = [
                ['Booking Ref', 'User', 'Flight', 'Date', 'Status', 'Amount']
            ];

            transactions.forEach(tx => {
                rows.push([
                    tx.booking_ref,
                    tx.user_name,
                    `${tx.flight_no} (${tx.origin} → ${tx.destination})`,
                    formatDate(tx.created_at),
                    tx.status,
                    `NGN ${Number(tx.total_amount).toFixed(2)}`
                ]);
            });

            const csv = rows.map(row =>
                row.map(value => `"${(value + '').replace(/"/g, '""')}"`).join(',')
            ).join('\n');

            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'transactions.csv';
            a.click();
            URL.revokeObjectURL(url);
        });

        // Event listeners
        document.getElementById('applyFilters').addEventListener('click', applyFilters);
        [searchInput, statusFilter].forEach(el => el.addEventListener('input', applyFilters));

        // Initial render
        renderTable();
        initRevenueChart();
    </script>
</body>

</html>
