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

// Get total users count
$total_users = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    $total_users = number_format($result['total'] ?? 0);
} catch (PDOException $e) {
    $total_users = "0";
}

// Get total flights count - UPDATED to match your table structure
$total_flights = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM flights");
    $result = $stmt->fetch();
    $total_flights = number_format($result['total'] ?? 0);
} catch (PDOException $e) {
    $total_flights = "0";
}

// Get total bookings count
$total_bookings = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $result = $stmt->fetch();
    $total_bookings = number_format($result['total'] ?? 0);
} catch (PDOException $e) {
    $total_bookings = "0";
}

// Get total revenue
$total_revenue = 0;
try {
    $stmt = $pdo->query("SELECT SUM(paid_amount) as revenue FROM bookings WHERE payment_status = 'Paid' AND paid_amount IS NOT NULL");
    $result = $stmt->fetch();
    $revenue = $result['revenue'] ?? 0;
    $total_revenue = "₦" . number_format($revenue, 2);
} catch (PDOException $e) {
    $total_revenue = "₦0.00";
}

// Get notification count (recent bookings in last 24 hours)
$notification_count = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $result = $stmt->fetch();
    $notification_count = $result['count'] ?? 0;
} catch (PDOException $e) {
    $notification_count = 0;
}

// Get recent bookings for the table (exclude expired flights)
$recent_bookings = [];
try {
    $stmt = $pdo->query("
        SELECT 
            b.booking_ref,
            b.total_amount,
            b.status,
            u.full_name,
            b.created_at
        FROM bookings b 
        LEFT JOIN users u ON b.user_id = u.user_id 
        LEFT JOIN flights f ON b.flight_id = f.flight_id
        WHERE f.flight_date >= CURDATE()
        ORDER BY b.created_at DESC 
        LIMIT 3
    ");
    $recent_bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_bookings = [];
}

// Get upcoming flights - UPDATED to match your actual table structure
$upcoming_flights = [];
try {
    $stmt = $pdo->query("
        SELECT 
            flight_id,
            airline,
            flight_no,
            origin,
            destination,
            flight_date,
            departure_time,
            arrival_time,
            aircraft,
            price,
            seats_available,
            status
        FROM flights 
        WHERE CONCAT(flight_date, ' ', departure_time) >= NOW() 
        AND status = 'scheduled'
        ORDER BY flight_date ASC, departure_time ASC 
        LIMIT 3
    ");
    $upcoming_flights = $stmt->fetchAll();
} catch (PDOException $e) {
    // If there's an error or no flights, keep empty array
    $upcoming_flights = [];
}

// Function to format flight time display - UPDATED for your table structure
function formatFlightTime($flight_date, $departure_time)
{
    $datetime_string = $flight_date . ' ' . $departure_time;
    $timestamp = strtotime($datetime_string);
    $today = strtotime('today');
    $tomorrow = strtotime('tomorrow');

    if (date('Y-m-d', $timestamp) == date('Y-m-d', $today)) {
        return 'Today ' . date('H:i', $timestamp);
    } elseif (date('Y-m-d', $timestamp) == date('Y-m-d', $tomorrow)) {
        return 'Tomorrow ' . date('H:i', $timestamp);
    } else {
        return date('D H:i', $timestamp);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Admin Dashboard - Speed of Light Airlines" />
    <meta name="keywords" content="airline admin, dashboard, flights, bookings, revenue">
    <meta name="author" content="Speed of Light Airlines" />
    <title>Admin Dashboard | Speed of Light Airlines</title>
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
        }

        * {
            box-sizing: border-box
        }

        body {
            background: var(--bg);
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
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
            max-width: 50%
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

        /* Content */
        .content {
            margin-left: 260px;
            padding: 22px 18px
        }

        .page-title {
            margin: 8px 0 18px 0
        }

        .page-title h1 {
            font-weight: 800;
            letter-spacing: .5px;
            background: linear-gradient(45deg, #38a169, #38a169);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent
        }

        .breadcrumbs {
            color: #64748b;
            font-size: .9rem
        }

        /* Cards */
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
            font-size: 1.9rem;
            font-weight: 800;
            color: var(--brand)
        }

        .kpi-trend {
            font-weight: 600
        }

        .kpi-trend.up {
            color: var(--success)
        }

        .kpi-trend.down {
            color: var(--danger)
        }

        .card-section {
            margin-top: 14px
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

        /* Table */
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

        /* Quick actions */
        .action-btn {
            border: none;
            border-radius: 12px;
            font-weight: 700;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            box-shadow: 0 8px 22px rgba(0, 83, 156, .25);
            transition: transform .25s
        }

        .action-btn:hover {
            transform: translateY(-3px)
        }

        .action-blue {
            background: linear-gradient(135deg, #38a169, #38a169)
        }

        .action-green {
            background: linear-gradient(135deg, #10b981, #059669)
        }

        .action-purple {
            background: linear-gradient(135deg, #7c3aed, #6d28d9)
        }

        /* Anim on scroll */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .6s ease, transform .6s ease
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0)
        }

        /* Responsive */
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
            <a href="dashboard.php" class="nav-link active"><i data-feather="home"></i> Dashboard</a>
            <a href="manage-flights.php" class="nav-link"><i data-feather="navigation"></i> Manage Flight</a>
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
                <span>Welcome back <?php echo htmlspecialchars($admin_name); ?> • Admin Dashboard • Manage flights • View bookings • Reconcile payments • </span>
                <span>Welcome back <?php echo htmlspecialchars($admin_name); ?> • Admin Dashboard • Manage flights • View bookings • Reconcile payments • </span>
            </div>
        </div>
        <div class="right ms-auto">
            <div class="dropdown">
                <button class="cart-icon-container btn p-0" id="notifToggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i data-feather="bell" class="cart-icon"></i>
                    <div class="cart-badge"><?php echo $notification_count; ?></div>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-menu" aria-labelledby="notifToggle">
                    <div class="notification-header">Notifications</div>
                    <?php if (!empty($recent_bookings)): ?>
                        <?php foreach (array_slice($recent_bookings, 0, 3) as $booking): ?>
                            <div class="notification-item">
                                <div class="notification-icon"><i data-feather="calendar" style="width:16px;height:16px"></i></div>
                                <div>
                                    <div class="fw-semibold">New booking <?php echo htmlspecialchars($booking['booking_ref']); ?></div>
                                    <div class="notification-meta">
                                        <?php echo date('M j, Y', strtotime($booking['created_at'])); ?> •
                                        <?php echo htmlspecialchars($booking['full_name'] ?? 'Guest'); ?> •
                                        ₦<?php echo number_format($booking['total_amount'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notification-item">
                            <div class="notification-icon"><i data-feather="info" style="width:16px;height:16px"></i></div>
                            <div>
                                <div class="fw-semibold">No recent notifications</div>
                                <div class="notification-meta">All caught up!</div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="text-center mt-2"><a href="bookings.php" class="small" style="color:var(--brand)">View all</a></div>
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
        <div class="page-title reveal">
            <h1>Welcome back</h1>
            <div class="breadcrumbs">View system overview and take actions quickly</div>
        </div>

        <!-- KPIs -->
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3 reveal">
                <div class="kpi-card h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="kpi-title">Total Users</div>
                            <div class="kpi-value" id="kpiUsers"><?php echo $total_users; ?></div>
                            <div class="kpi-trend up"><i data-feather="trending-up" style="width:16px;height:16px"></i> +3.2% this week</div>
                        </div>
                        <i data-feather="users" style="width:36px;height:36px;color:#94a3b8"></i>
                    </div>
                    <div id="sparkUsers" class="card-section"></div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3 reveal">
                <div class="kpi-card h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="kpi-title">Total Flights</div>
                            <div class="kpi-value" id="kpiFlights"><?php echo $total_flights; ?></div>
                            <div class="kpi-trend up"><i data-feather="trending-up" style="width:16px;height:16px"></i> +1.1% MTD</div>
                        </div>
                        <i data-feather="navigation" style="width:36px;height:36px;color:#94a3b8"></i>
                    </div>
                    <div id="sparkFlights" class="card-section"></div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3 reveal">
                <div class="kpi-card h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="kpi-title">Bookings</div>
                            <div class="kpi-value" id="kpiBookings"><?php echo $total_bookings; ?></div>
                            <div class="kpi-trend down"><i data-feather="trending-down" style="width:16px;height:16px"></i> -0.8% today</div>
                        </div>
                        <i data-feather="calendar" style="width:36px;height:36px;color:#94a3b8"></i>
                    </div>
                    <div id="sparkBookings" class="card-section"></div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3 reveal">
                <div class="kpi-card h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="kpi-title">Revenue</div>
                            <div class="kpi-value" id="kpiRevenue"><?php echo $total_revenue; ?></div>
                            <div class="kpi-trend up"><i data-feather="trending-up" style="width:16px;height:16px"></i> +6.4% MTD</div>
                        </div>
                        <i data-feather="dollar-sign" style="width:36px;height:36px;color:#94a3b8"></i>
                    </div>
                    <div id="sparkRevenue" class="card-section"></div>
                </div>
            </div>
        </div>

        <!-- Charts and Quick Actions -->
        <div class="row g-3 mt-1">
            <div class="col-xl-8 reveal">
                <div class="smart-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold">Bookings Trend</div>
                        <div class="text-muted small">Last 8 weeks</div>
                    </div>
                    <div id="chartBookings" style="height:280px"></div>
                </div>
            </div>
            <div class="col-xl-4 reveal">
                <div class="smart-card mb-3">
                    <div class="fw-bold mb-2">Quick Actions</div>
                    <div class="d-grid gap-2">
                        <button class="action-btn action-blue" onclick="location.href='manage-flights.php'"><i data-feather="plus"></i> Add New Flight</button>
                        <button class="action-btn action-green" onclick="location.href='bookings.php'"><i data-feather="list"></i> View Recent Bookings</button>
                        <button class="action-btn action-purple" onclick="location.href='reports.php'"><i data-feather="file-text"></i> Generate Report</button>
                    </div>
                </div>
                <!-- UPDATED: Dynamic Upcoming Flights with correct table structure -->
                <div class="smart-card">
                    <div class="fw-bold mb-2">Upcoming Flights</div>
                    <ul class="list-unstyled mb-0">
                        <?php if (!empty($upcoming_flights)): ?>
                            <?php foreach ($upcoming_flights as $index => $flight): ?>
                                <li class="d-flex justify-content-between align-items-center py-2 <?php echo $index < count($upcoming_flights) - 1 ? 'border-bottom' : ''; ?>">
                                    <span class="fw-semibold">
                                        <?php echo htmlspecialchars($flight['origin']); ?> → <?php echo htmlspecialchars($flight['destination']); ?>
                                        <small class="text-muted d-block">
                                            <?php echo htmlspecialchars($flight['airline']); ?> <?php echo htmlspecialchars($flight['flight_no']); ?>
                                            <?php if (isset($flight['aircraft'])): ?>
                                                • <?php echo htmlspecialchars($flight['aircraft']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </span>
                                    <span class="badge badge-soft">
                                        <?php echo formatFlightTime($flight['flight_date'], $flight['departure_time']); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="d-flex justify-content-center align-items-center py-3">
                                <span class="text-muted">
                                    <i data-feather="calendar-x" style="width:20px;height:20px;margin-right:8px;"></i>
                                    No upcoming flights scheduled
                                </span>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="text-center mt-2">
                        <a href="manage-flights.php" class="small" style="color:var(--brand)">
                            <?php echo !empty($upcoming_flights) ? 'View all flights' : 'Add new flight'; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Chart and Recent Bookings -->
        <div class="row g-3 mt-1">
            <div class="col-xl-6 reveal">
                <div class="smart-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold">Revenue Overview</div>
                        <div class="text-muted small">Monthly</div>
                    </div>
                    <div id="chartRevenue" style="height:280px"></div>
                </div>
            </div>
            <div class="col-xl-6 reveal">
                <div class="smart-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold">Recent Bookings</div>
                        <a href="bookings.php" class="small" style="color:var(--brand)">View all</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Route</th>
                                    <th>Passenger</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_bookings)): ?>
                                    <?php foreach ($recent_bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($booking['booking_ref']); ?></td>
                                            <td>Flight Route</td>
                                            <td><?php echo htmlspecialchars($booking['full_name'] ?? 'Guest'); ?></td>
                                            <td>
                                                <?php
                                                $status = $booking['status'];
                                                $badge_class = 'bg-secondary';
                                                if ($status === 'confirmed' || $status === 'paid') {
                                                    $badge_class = 'bg-success';
                                                } elseif ($status === 'pending') {
                                                    $badge_class = 'bg-warning text-dark';
                                                } elseif ($status === 'cancelled' || $status === 'refunded') {
                                                    $badge_class = 'bg-danger';
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span>
                                            </td>
                                            <td>₦<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No recent bookings found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-4 text-center text-muted small">&copy; <span id="year"></span> Speed of Light Airlines. Admin Panel.</footer>
    </main>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();
        document.getElementById('year').textContent = new Date().getFullYear();

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

        // Mini sparklines
        function spark(el, series, color) {
            new ApexCharts(document.querySelector(el), {
                chart: {
                    type: 'area',
                    height: 80,
                    sparkline: {
                        enabled: true
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                colors: [color],
                fill: {
                    type: 'gradient',
                    gradient: {
                        opacityFrom: .4,
                        opacityTo: .1,
                        stops: [0, 80, 100]
                    }
                },
                series: [{
                    data: series
                }],
                tooltip: {
                    enabled: false
                }
            }).render();
        }
        spark('#sparkUsers', [10, 14, 12, 18, 22, 20, 24, 26, 28, 30], '#0ea5e9');
        spark('#sparkFlights', [4, 5, 6, 6, 7, 7, 8, 8, 9, 10], '#22c55e');
        spark('#sparkBookings', [20, 18, 22, 19, 21, 20, 19, 18, 17, 18], '#f59e0b');
        spark('#sparkRevenue', [120, 140, 130, 160, 170, 180, 200, 220, 210, 240], '#6366f1');

        // Bookings trend
        const chartBookings = new ApexCharts(document.querySelector('#chartBookings'), {
            chart: {
                type: 'area',
                height: 280,
                toolbar: {
                    show: false
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            series: [{
                name: 'Bookings',
                data: [120, 142, 135, 160, 175, 168, 190, 210]
            }],
            colors: ['#0ea5e9'],
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: .45,
                    opacityTo: .05
                }
            },
            xaxis: {
                categories: ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8'],
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
            }
        });
        chartBookings.render();

        // Revenue chart
        const chartRevenue = new ApexCharts(document.querySelector('#chartRevenue'), {
            chart: {
                type: 'bar',
                height: 280,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: '45%'
                }
            },
            series: [{
                name: 'Revenue',
                data: [120, 150, 130, 160, 190, 210, 240, 220, 260, 300, 320, 340]
            }],
            colors: ['#22c55e'],
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
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
            }
        });
        chartRevenue.render();
    </script>
</body>

</html>
</qodoArtifact>