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

// Handle AJAX requests for report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        if ($_POST['action'] === 'generate_report') {
            $period = $_POST['period'] ?? 'daily';
            $date_from = $_POST['date_from'] ?? '';
            $date_to = $_POST['date_to'] ?? '';
            $include_flights = isset($_POST['include_flights']) && $_POST['include_flights'] === 'true';
            $include_bookings = isset($_POST['include_bookings']) && $_POST['include_bookings'] === 'true';
            $include_revenue = isset($_POST['include_revenue']) && $_POST['include_revenue'] === 'true';

            // Build date conditions
            $date_conditions = [];
            $params = [];

            if (!empty($date_from)) {
                $date_conditions[] = "DATE(created_at) >= ?";
                $params[] = $date_from;
            }

            if (!empty($date_to)) {
                $date_conditions[] = "DATE(created_at) <= ?";
                $params[] = $date_to;
            }

            $date_where = !empty($date_conditions) ? 'WHERE ' . implode(' AND ', $date_conditions) : '';

            // Determine date format for grouping - FIXED: Use switch instead of match for PHP compatibility
            switch ($period) {
                case 'weekly':
                    $date_format = '%Y-%u';  // Year-Week
                    break;
                case 'monthly':
                    $date_format = '%Y-%m'; // Year-Month
                    break;
                default:
                    $date_format = '%Y-%m-%d'; // Daily
                    break;
            }

            $report_data = [];

            // Get flights data
            if ($include_flights) {
                $stmt = $pdo->prepare("
                    SELECT 
                        DATE_FORMAT(created_at, '$date_format') as period,
                        DATE_FORMAT(created_at, '%Y-%m-%d') as date,
                        COUNT(*) as count
                    FROM flights 
                    $date_where
                    GROUP BY DATE_FORMAT(created_at, '$date_format')
                    ORDER BY period ASC
                ");
                $stmt->execute($params);
                $flights_data = $stmt->fetchAll();

                foreach ($flights_data as $row) {
                    $key = $row['period'];
                    if (!isset($report_data[$key])) {
                        $report_data[$key] = [
                            'period' => $key,
                            'date' => $row['date'],
                            'flights' => 0,
                            'bookings' => 0,
                            'revenue' => 0
                        ];
                    }
                    $report_data[$key]['flights'] = (int)$row['count'];
                }
            }

            // Get bookings data - FIXED: Use paid_amount for revenue calculation
            if ($include_bookings || $include_revenue) {
                if ($include_revenue) {
                    // Use paid_amount for revenue where payment_status = 'Paid'
                    $revenue_select = ', SUM(CASE WHEN payment_status = "Paid" THEN COALESCE(paid_amount, 0) ELSE 0 END) as revenue';
                } else {
                    $revenue_select = ', 0 as revenue';
                }

                $stmt = $pdo->prepare("
                    SELECT 
                        DATE_FORMAT(created_at, '$date_format') as period,
                        DATE_FORMAT(created_at, '%Y-%m-%d') as date,
                        COUNT(*) as count
                        $revenue_select
                    FROM bookings 
                    $date_where
                    GROUP BY DATE_FORMAT(created_at, '$date_format')
                    ORDER BY period ASC
                ");
                $stmt->execute($params);
                $bookings_data = $stmt->fetchAll();

                foreach ($bookings_data as $row) {
                    $key = $row['period'];
                    if (!isset($report_data[$key])) {
                        $report_data[$key] = [
                            'period' => $key,
                            'date' => $row['date'],
                            'flights' => 0,
                            'bookings' => 0,
                            'revenue' => 0
                        ];
                    }
                    if ($include_bookings) {
                        $report_data[$key]['bookings'] = (int)$row['count'];
                    }
                    if ($include_revenue) {
                        $report_data[$key]['revenue'] = (float)($row['revenue'] ?? 0);
                    }
                }
            }

            // Format period labels for display - FIXED: Use switch instead of match
            $formatted_data = [];
            foreach ($report_data as $key => $data) {
                switch ($period) {
                    case 'weekly':
                        $label = 'Week ' . substr($key, -2) . ', ' . substr($key, 0, 4);
                        break;
                    case 'monthly':
                        $label = date('M Y', strtotime($key . '-01'));
                        break;
                    default:
                        $label = date('M j, Y', strtotime($data['date']));
                        break;
                }

                $formatted_data[] = [
                    'label' => $label,
                    'flights' => $data['flights'],
                    'bookings' => $data['bookings'],
                    'revenue' => $data['revenue']
                ];
            }

            // Sort by period
            ksort($report_data);

            echo json_encode([
                'success' => true,
                'data' => array_values($formatted_data)
            ]);
        } elseif ($_POST['action'] === 'get_summary_stats') {
            $date_from = $_POST['date_from'] ?? '';
            $date_to = $_POST['date_to'] ?? '';

            // Build date conditions
            $date_conditions = [];
            $params = [];

            if (!empty($date_from)) {
                $date_conditions[] = "DATE(created_at) >= ?";
                $params[] = $date_from;
            }

            if (!empty($date_to)) {
                $date_conditions[] = "DATE(created_at) <= ?";
                $params[] = $date_to;
            }

            $date_where = !empty($date_conditions) ? 'WHERE ' . implode(' AND ', $date_conditions) : '';

            // Get flights count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM flights $date_where");
            $stmt->execute($params);
            $flights_count = $stmt->fetch()['count'] ?? 0;

            // Get bookings count and revenue - FIXED: Use paid_amount for revenue
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as count, 
                    SUM(CASE WHEN payment_status = 'Paid' THEN COALESCE(paid_amount, 0) ELSE 0 END) as revenue 
                FROM bookings 
                $date_where
            ");
            $stmt->execute($params);
            $bookings_data = $stmt->fetch();
            $bookings_count = $bookings_data['count'] ?? 0;
            $total_revenue = $bookings_data['revenue'] ?? 0;

            echo json_encode([
                'success' => true,
                'flights' => (int)$flights_count,
                'bookings' => (int)$bookings_count,
                'revenue' => (float)$total_revenue
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Get recent notifications
$notifications = [];
$notification_count = 0;

try {
    // Get recent bookings and flights for notifications
    $stmt = $pdo->query("
        SELECT 
            'booking' as type,
            b.booking_ref as reference,
            COALESCE(b.paid_amount, b.total_amount, 0) as total_amount,
            b.created_at,
            u.full_name as user_name,
            f.flight_no
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN flights f ON b.flight_id = f.flight_id
        WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        
        UNION ALL
        
        SELECT 
            'flight' as type,
            f.flight_no as reference,
            0 as total_amount,
            f.created_at,
            '' as user_name,
            f.flight_no
        FROM flights f
        WHERE f.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $recent_activities = $stmt->fetchAll();

    foreach ($recent_activities as $activity) {
        $timeAgo = time_elapsed_string($activity['created_at']);

        if ($activity['type'] === 'booking') {
            $notifications[] = [
                'icon' => 'calendar',
                'title' => 'New booking',
                'meta' => "{$activity['flight_no']} · ₦" . number_format($activity['total_amount'], 2) . " · {$timeAgo}",
                'time' => $activity['created_at']
            ];
        } else {
            $notifications[] = [
                'icon' => 'navigation',
                'title' => 'New flight added',
                'meta' => "{$activity['flight_no']} · {$timeAgo}",
                'time' => $activity['created_at']
            ];
        }
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
    <meta name="description" content="Reports - Speed of Light Airlines Admin" />
    <meta name="keywords" content="airline admin, reports, flights, bookings, revenue, export">
    <meta name="author" content="Speed of Light Airlines" />
    <title>Reports | Speed of Light Airlines</title>
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

        .export-pdf {
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 8px 22px rgba(239, 68, 68, .25)
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Print */
        @media print {

            .sidebar,
            .topbar,
            .no-print {
                display: none !important;
            }

            body {
                background: #fff;
            }

            .print-area {
                margin: 0;
                padding: 0;
            }
        }

        /* Mobile responsive */
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
            <a href="payments.php" class="nav-link"><i data-feather="credit-card"></i> Payments</a>
            <a href="reports.php" class="nav-link active"><i data-feather="bar-chart-2"></i> Generate Reports</a>
            <a href="profile.php" class="nav-link"><i data-feather="user"></i> Profile</a>
            <a href="logout.php" class="nav-link"><i data-feather="log-out"></i> Logout</a>
        </nav>
    </aside>

    <!-- Topbar -->
    <header class="topbar d-flex align-items-center">
        <button class="btn btn-sm btn-light d-lg-none" id="menuToggle"><i data-feather="menu"></i></button>
        <div class="ticker ms-3 me-3 flex-grow-1 d-none d-md-block">
            <div class="ticker-track">
                <span>Reports • Generate daily / weekly / monthly reports • Flights • Bookings • Revenue • Export PDF / Excel • Welcome back <?php echo htmlspecialchars($admin_name); ?> • </span>
                <span>Reports • Generate daily / weekly / monthly reports • Flights • Bookings • Revenue • Export PDF / Excel • Welcome back <?php echo htmlspecialchars($admin_name); ?> • </span>
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
    <main class="content print-area">
        <div class="page-title reveal mb-3">
            <h1>Generate Reports</h1>
            <div class="text-muted">Create daily, weekly, or monthly reports for flights, bookings, and revenue. Export as PDF/Excel.</div>
        </div>

        <!-- Controls -->
        <div class="smart-card reveal no-print">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Report Period</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="period" id="pDaily" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="pDaily">Daily</label>
                        <input type="radio" class="btn-check" name="period" id="pWeekly" autocomplete="off">
                        <label class="btn btn-outline-primary" for="pWeekly">Weekly</label>
                        <input type="radio" class="btn-check" name="period" id="pMonthly" autocomplete="off">
                        <label class="btn btn-outline-primary" for="pMonthly">Monthly</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" id="dateFrom" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" id="dateTo" class="form-control">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Include</label>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="flights" id="repFlights" checked>
                            <label class="form-check-label" for="repFlights">Flights</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="bookings" id="repBookings" checked>
                            <label class="form-check-label" for="repBookings">Bookings</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="revenue" id="repRevenue" checked>
                            <label class="form-check-label" for="repRevenue">Revenue</label>
                        </div>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary" id="generateBtn"><i data-feather="bar-chart-2"></i> Generate</button>
                    <button class="export-pdf" id="exportPdf"><i data-feather="printer"></i> Export PDF</button>
                    <button class="export-btn" id="exportExcel"><i data-feather="download"></i> Export Excel</button>
                </div>
            </div>
        </div>

        <!-- KPIs -->
        <div class="row g-3 mt-1">
            <div class="col-12 col-md-4 reveal">
                <div class="kpi-card h-100">
                    <div class="kpi-title">Total Flights</div>
                    <div class="kpi-value" id="kpiFlights">0</div>
                    <div class="text-muted">in selected period</div>
                </div>
            </div>
            <div class="col-12 col-md-4 reveal">
                <div class="kpi-card h-100">
                    <div class="kpi-title">Total Bookings</div>
                    <div class="kpi-value" id="kpiBookings">0</div>
                    <div class="text-muted">in selected period</div>
                </div>
            </div>
            <div class="col-12 col-md-4 reveal">
                <div class="kpi-card h-100">
                    <div class="kpi-title">Total Revenue</div>
                    <div class="kpi-value" id="kpiRevenue">₦ 0.00</div>
                    <div class="text-muted">in selected period</div>
                </div>
            </div>
        </div>

        <!-- Chart & Table -->
        <div class="row g-3 mt-1" id="reportArea">
            <div class="col-xl-7 reveal">
                <div class="smart-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold">Report Preview</div>
                        <div class="text-muted small" id="reportSubtitle">Daily</div>
                    </div>
                    <div id="chartReport" style="height:320px"></div>
                </div>
            </div>
            <div class="col-xl-5 reveal">
                <div class="smart-card">
                    <div class="fw-bold mb-2">Aggregated Results</div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="reportTable">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th class="text-end">Flights</th>
                                    <th class="text-end">Bookings</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="reportBody">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i data-feather="bar-chart-2" style="width:48px;height:48px;margin-bottom:12px;"></i><br>
                                        Click "Generate" to create your report
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
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

        // Elements
        const pDaily = document.getElementById('pDaily');
        const pWeekly = document.getElementById('pWeekly');
        const pMonthly = document.getElementById('pMonthly');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const repFlights = document.getElementById('repFlights');
        const repBookings = document.getElementById('repBookings');
        const repRevenue = document.getElementById('repRevenue');
        const reportSubtitle = document.getElementById('reportSubtitle');
        const kpiFlights = document.getElementById('kpiFlights');
        const kpiBookings = document.getElementById('kpiBookings');
        const kpiRevenue = document.getElementById('kpiRevenue');
        const reportBody = document.getElementById('reportBody');
        const generateBtn = document.getElementById('generateBtn');

        let chart;
        let currentReportData = [];

        // Update summary stats
        async function updateSummaryStats() {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'get_summary_stats',
                        date_from: dateFrom.value,
                        date_to: dateTo.value
                    })
                });

                const result = await response.json();
                if (result.success) {
                    kpiFlights.textContent = result.flights;
                    kpiBookings.textContent = result.bookings;
                    kpiRevenue.textContent = `₦${result.revenue.toFixed(2)}`;
                } else {
                    console.error('Error updating summary stats:', result.message);
                }
            } catch (error) {
                console.error('Error updating summary stats:', error);
            }
        }

        // Generate report
        async function generateReport() {
            const period = pWeekly.checked ? 'weekly' : (pMonthly.checked ? 'monthly' : 'daily');
            reportSubtitle.textContent = period.charAt(0).toUpperCase() + period.slice(1);

            // Show loading state
            generateBtn.classList.add('loading');
            generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'generate_report',
                        period: period,
                        date_from: dateFrom.value,
                        date_to: dateTo.value,
                        include_flights: repFlights.checked,
                        include_bookings: repBookings.checked,
                        include_revenue: repRevenue.checked
                    })
                });

                const result = await response.json();
                if (result.success) {
                    currentReportData = result.data;
                    updateReportDisplay();
                    await updateSummaryStats();
                } else {
                    console.error('Error generating report:', result.message);
                    alert('Error generating report: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error generating report. Please try again.');
            } finally {
                generateBtn.classList.remove('loading');
                generateBtn.innerHTML = '<i data-feather="bar-chart-2"></i> Generate';
                feather.replace();
            }
        }

        // Update report display
        function updateReportDisplay() {
            // Update table
            reportBody.innerHTML = '';

            if (currentReportData.length === 0) {
                reportBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i data-feather="inbox" style="width:48px;height:48px;margin-bottom:12px;"></i><br>
                            No data found for the selected period and filters.
                        </td>
                    </tr>
                `;
                feather.replace();

                // Clear chart if no data
                if (chart) {
                    chart.updateSeries([]);
                }
                return;
            }

            currentReportData.forEach(row => {
                const tr = document.createElement('tr');
                tr.className = 'animate__animated animate__fadeInUp';
                tr.innerHTML = `
                    <td>${row.label}</td>
                    <td class="text-end">${row.flights}</td>
                    <td class="text-end">${row.bookings}</td>
                    <td class="text-end">₦${row.revenue.toFixed(2)}</td>
                `;
                reportBody.appendChild(tr);
            });

            // Update chart
            const categories = currentReportData.map(r => r.label);
            const series = [];

            if (repFlights.checked) {
                series.push({
                    name: 'Flights',
                    data: currentReportData.map(r => r.flights)
                });
            }

            if (repBookings.checked) {
                series.push({
                    name: 'Bookings',
                    data: currentReportData.map(r => r.bookings)
                });
            }

            if (repRevenue.checked) {
                series.push({
                    name: 'Revenue',
                    data: currentReportData.map(r => Number(r.revenue.toFixed(2)))
                });
            }

            if (!chart) {
                chart = new ApexCharts(document.querySelector('#chartReport'), {
                    chart: {
                        type: 'line',
                        height: 320,
                        toolbar: {
                            show: false
                        },
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        }
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 3
                    },
                    colors: ['#0ea5e9', '#7c3aed', '#22c55e'],
                    series: series,
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
                            }
                        }
                    },
                    grid: {
                        borderColor: 'rgba(100,116,139,.15)'
                    },
                    tooltip: {
                        y: {
                            formatter: function(val, opts) {
                                if (opts.seriesIndex === 2) { // Revenue series
                                    return `₦${val.toFixed(2)}`;
                                }
                                return val;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right'
                    }
                });
                chart.render();
            } else {
                chart.updateOptions({
                    xaxis: {
                        categories
                    }
                });
                chart.updateSeries(series);
            }
        }

        // Event listeners
        generateBtn.addEventListener('click', generateReport);

        // Export Excel (CSV) from table
        document.getElementById('exportExcel').addEventListener('click', () => {
            if (currentReportData.length === 0) {
                alert('Please generate a report first.');
                return;
            }

            const rows = [
                ['Period', 'Flights', 'Bookings', 'Revenue']
            ];

            currentReportData.forEach(row => {
                rows.push([
                    row.label,
                    row.flights,
                    row.bookings,
                    `₦${row.revenue.toFixed(2)}`
                ]);
            });

            const csv = rows.map(r =>
                r.map(v => `"${(v + '').replace(/"/g, '""')}"`).join(',')
            ).join('\n');

            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `report_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        });

        // Export PDF using print
        document.getElementById('exportPdf').addEventListener('click', () => {
            if (currentReportData.length === 0) {
                alert('Please generate a report first.');
                return;
            }
            window.print();
        });

        // Set default date range (last 30 days)
        const today = new Date();
        const thirtyDaysAgo = new Date(today);
        thirtyDaysAgo.setDate(today.getDate() - 30);

        dateTo.value = today.toISOString().split('T')[0];
        dateFrom.value = thirtyDaysAgo.toISOString().split('T')[0];

        // Initial load
        updateSummaryStats();
    </script>
</body>

</html>