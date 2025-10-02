<?php
session_start();

// Authentication Check
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

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'] ?? 'Admin User';
$first_name = explode(' ', $admin_name)[0];
$admin_initial = strtoupper(substr($first_name, 0, 1));

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'get_booking_details') {
            $booking_ref = $_POST['booking_ref'];
            
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
                    f.price as flight_base_price
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
        }
        
        if ($_POST['action'] === 'verify_payment') {
            $booking_ref = $_POST['booking_ref'];
            
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE booking_ref = ? AND status = 'pending'");
            $result = $stmt->execute([$booking_ref]);
            
            if ($result && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Payment verified successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Unable to verify payment']);
            }
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Get booking statistics
$total_bookings = 0;
$pending_bookings = 0;
$completed_bookings = 0;

try {
    // Get total bookings count
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    $total_bookings = $stmt->fetchColumn();
    
    // Debug: Check what status values exist
    $stmt = $pdo->query("SELECT DISTINCT status FROM bookings WHERE status IS NOT NULL");
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Booking statuses found: " . implode(', ', $statuses));
    
    // Get pending bookings count (handle different status formats)
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM bookings 
        WHERE status IN ('pending', 'Pending', 'PENDING') 
        OR payment_status IN ('pending', 'Pending', 'PENDING')
        OR (status IS NULL AND payment_status IS NULL)
    ");
    $pending_bookings = $stmt->fetchColumn();
    
    // Get completed bookings count (handle different status formats)
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM bookings 
        WHERE status IN ('completed', 'Completed', 'COMPLETED', 'confirmed', 'Confirmed', 'CONFIRMED', 'paid', 'Paid', 'PAID')
        OR payment_status IN ('completed', 'Completed', 'COMPLETED', 'confirmed', 'Confirmed', 'CONFIRMED', 'paid', 'Paid', 'PAID')
    ");
    $completed_bookings = $stmt->fetchColumn();
    
    // Debug output
    error_log("=== BOOKING STATS DEBUG ===");
    error_log("Total bookings: " . $total_bookings);
    error_log("Pending bookings: " . $pending_bookings);
    error_log("Completed bookings: " . $completed_bookings);
    
    // If still getting zeros, let's see sample data
    if ($total_bookings > 0 && ($pending_bookings + $completed_bookings) == 0) {
        $stmt = $pdo->query("SELECT booking_ref, status, payment_status, total_amount FROM bookings LIMIT 5");
        $sample_bookings = $stmt->fetchAll();
        error_log("Sample bookings data: " . json_encode($sample_bookings));
        
        // If no specific status found, assume all are pending
        if ($pending_bookings == 0 && $completed_bookings == 0) {
            $pending_bookings = $total_bookings;
            error_log("No status found, assuming all bookings are pending");
        }
    }
    
} catch (PDOException $e) {
    error_log("Booking stats error: " . $e->getMessage());
    $total_bookings = 0;
    $pending_bookings = 0;
    $completed_bookings = 0;
}

// Get all bookings
$bookings = [];
try {
    $stmt = $pdo->query("
        SELECT 
            b.booking_id,
            b.booking_ref,
            b.user_id,
            b.flight_id,
            b.passengers,
            COALESCE(b.class, 'economy') as class,
            b.total_amount,
            COALESCE(b.status, 'pending') as status,
            b.created_at,
            COALESCE(b.paystack_reference, '') as paystack_reference,
            COALESCE(u.full_name, 'Unknown Passenger') as passenger_name,
            COALESCE(u.email, 'No email') as passenger_email,
            COALESCE(u.phone, 'No phone') as passenger_phone,
            COALESCE(f.flight_no, CONCAT('FL', LPAD(b.flight_id, 3, '0'))) as flight_number,
            COALESCE(f.airline, 'Speed of Light Airlines') as airline,
            CASE 
                WHEN f.origin IS NOT NULL AND f.destination IS NOT NULL 
                THEN CONCAT(f.origin, ' → ', f.destination)
                ELSE 'Route Not Available'
            END as route
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN flights f ON b.flight_id = f.flight_id
        ORDER BY b.created_at DESC
    ");
    $bookings = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error fetching bookings: " . $e->getMessage());
    $bookings = [];
}

// Get recent notifications
$notifications = [];
try {
    $stmt = $pdo->query("
        SELECT 
            b.booking_ref,
            b.total_amount,
            b.status,
            b.created_at,
            u.full_name as user_name,
            f.flight_no
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN flights f ON b.flight_id = f.flight_id
        WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $recent_bookings = $stmt->fetchAll();

    foreach ($recent_bookings as $booking) {
        $notifications[] = [
            'icon' => $booking['status'] === 'completed' ? 'check-circle' : 'clock',
            'title' => $booking['status'] === 'completed' ? 'Booking confirmed' : 'New booking',
            'meta' => ($booking['flight_no'] ?: 'Flight') . " • ₦" . number_format($booking['total_amount'], 2),
            'time' => $booking['created_at']
        ];
    }
} catch (PDOException $e) {
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings | Speed of Light Airlines</title>
    <link rel="icon" href="../User/assets/images/airline-favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Feather Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --brand: #00539C;
            --brand-dark: #003366;
            --bg: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.96);
            --muted: #64748b;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--bg);
            font-family: 'Inter', sans-serif;
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
            padding: 20px 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .brand img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .3);
        }

        .brand-title {
            font-weight: 800;
            letter-spacing: 1px;
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
            text-decoration: none;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--brand);
            color: #fff;
            transform: translateX(4px);
            box-shadow: 0 6px 18px rgba(0, 83, 156, .25);
        }

        .nav-link i {
            width: 18px;
            height: 18px;
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
            box-shadow: 0 2px 20px rgba(0, 0, 0, .1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, .12);
            padding: 6px 12px;
            border-radius: 10px;
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
            color: #fff;
        }

        /* Content */
        .content {
            margin-left: 260px;
            padding: 22px 18px;
        }

        .page-title h1 {
            font-weight: 800;
            letter-spacing: .5px;
            background: linear-gradient(45deg, #00539C, #003366);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Cards */
        .smart-card {
            background: var(--card-bg);
            border: 1px solid rgba(0, 83, 156, .08);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(0, 83, 156, .10);
            transition: transform .35s ease, box-shadow .35s ease;
        }

        .smart-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 50px rgba(0, 83, 156, .20);
        }

        .smart-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00539C, #003366);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .stat-card.total::before {
            background: linear-gradient(90deg, #00539C, #003366);
        }

        .stat-card.pending::before {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .stat-card.completed::before {
            background: linear-gradient(90deg, #10b981, #059669);
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

        /* Table */
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

        /* Status badges */
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

        /* Action buttons */
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
            background: linear-gradient(135deg, #00539C, #003366);
        }

        .chip-green {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        /* Badge */
        .badge-soft {
            background: rgba(0, 83, 156, .08);
            color: var(--brand-dark);
            border: 1px solid rgba(0, 83, 156, .15);
        }

        /* Paystack reference */
        .paystack-ref {
            font-family: 'Courier New', monospace;
            background: rgba(0, 83, 156, 0.1);
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.85rem;
            color: var(--brand-dark);
            font-weight: 600;
        }

        /* Modal improvements */
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

        /* Notification dropdown */
        .notification-menu {
            min-width: 300px;
            border: 1px solid rgba(0, 83, 156, .12);
            border-radius: 14px;
            padding: 10px;
            background: #fff;
        }

        .notification-header {
            font-weight: 700;
            color: var(--brand-dark);
            padding: 6px 10px;
            border-bottom: 1px solid rgba(0, 83, 156, .1);
            margin-bottom: 6px;
        }

        .notification-item {
            display: flex;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 10px;
            transition: background .2s;
        }

        .notification-item:hover {
            background: rgba(0, 83, 156, .06);
        }

        .notification-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(0, 83, 156, .1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--brand);
        }

        .notification-meta {
            font-size: .78rem;
            color: #64748b;
        }

        .no-notifications {
            text-align: center;
            padding: 20px;
            color: #64748b;
        }

        /* Responsive */
        @media(max-width:991px) {
            .sidebar {
                left: -260px;
                transition: left .3s;
            }

            .sidebar.show {
                left: 0;
            }

            .topbar,
            .content {
                margin-left: 0;
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
            <div class="brand-title">SOLA</div>
        </div>
        <nav>
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
        <button class="btn btn-sm btn-light d-lg-none" id="menuToggle">
            <i data-feather="menu"></i>
        </button>
        <div class="ms-3 me-3 flex-grow-1">
            <h6 class="mb-0">Bookings Management</h6>
            <small class="text-light opacity-75">Manage all flight bookings and payments</small>
        </div>
        <div class="ms-auto">
            <div class="dropdown">
                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                    <i data-feather="bell"></i>
                    <?php if (!empty($notifications)): ?>
                        <span class="badge bg-danger"><?php echo count($notifications); ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-menu">
                    <div class="notification-header">Recent Activity</div>
                    <?php if (empty($notifications)): ?>
                        <div class="no-notifications">
                            <i data-feather="bell-off"></i>
                            <div class="fw-semibold">No new notifications</div>
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
        <div class="page-title mb-4">
            <h1>Bookings Management</h1>
            <div class="text-muted">Monitor and manage all flight bookings and payment statuses.</div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number"><?php echo $total_bookings; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number"><?php echo $pending_bookings; ?></div>
                <div class="stat-label">Pending Payment</div>
            </div>
            <div class="stat-card completed">
                <div class="stat-number"><?php echo $completed_bookings; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="smart-card mb-3">
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
        <div class="smart-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="fw-bold">All Bookings</div>
                <div class="d-flex align-items-center gap-3">
                    <div class="small text-muted" id="countText"><?php echo count($bookings); ?> booking(s) found</div>
                    <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        <i data-feather="refresh-cw"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Passenger</th>
                            <th>Flight Route</th>
                            <th>Class</th>
                            <th>Passengers</th>
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
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i data-feather="calendar" style="width:48px;height:48px;margin-bottom:12px;"></i><br>
                                    No bookings found. Bookings will appear here once customers start making reservations.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['booking_ref']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['passenger_name']); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($booking['route']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($booking['airline']); ?></small>
                                    </td>
                                    <td><span class="badge badge-soft"><?php echo ucfirst(htmlspecialchars($booking['class'])); ?></span></td>
                                    <td><?php echo $booking['passengers']; ?></td>
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
                                        <button class="action-chip chip-blue btn-sm" onclick="showBookingDetails('<?php echo htmlspecialchars($booking['booking_ref']); ?>')">
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
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i data-feather="eye"></i> Booking Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                            <span class="detail-label">Class:</span>
                            <span class="detail-value" id="detailClass">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Passengers:</span>
                            <span class="detail-value" id="detailPassengers">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Amount:</span>
                            <span class="detail-value" id="detailAmount">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="verifyPaymentBtn">
                        <i data-feather="shield-check"></i> Verify Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
        <div id="liveToast" class="toast" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="toastMsg">Action completed</div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();

        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }

        // Load bookings data
        let bookings = <?php echo json_encode($bookings); ?>;

        // Elements
        const tbody = document.getElementById('bookingsBody');
        const countText = document.getElementById('countText');
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const classFilter = document.getElementById('classFilter');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');

        // Toast
        const toastEl = document.getElementById('liveToast');
        const toast = new bootstrap.Toast(toastEl);

        function notify(msg, type = 'success') {
            const toastBody = document.getElementById('toastMsg');
            toastBody.textContent = msg;
            toastEl.className = `toast text-bg-${type === 'error' ? 'danger' : 'success'}`;
            toast.show();
        }

        // Helper functions
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
            if (status === 'pending') {
                return '<span class="status-pending">Pending</span>';
            } else {
                return '<span class="status-completed">Completed</span>';
            }
        }

        // Filter and render bookings
        function renderBookings() {
            const q = (searchInput.value || '').toLowerCase();
            const sf = statusFilter.value;
            const cf = classFilter.value;
            const df = dateFrom.value ? new Date(dateFrom.value) : null;
            const dt = dateTo.value ? new Date(dateTo.value) : null;

            const filtered = bookings.filter(b => {
                if (q && !(b.booking_ref + b.passenger_name + b.route + b.flight_number + (b.paystack_reference || '')).toLowerCase().includes(q)) return false;
                if (sf && b.status !== sf) return false;
                if (cf && b.class !== cf) return false;
                if (df && new Date(b.created_at) < df) return false;
                if (dt && new Date(b.created_at) > dt) return false;
                return true;
            });

            countText.textContent = `${filtered.length} booking(s) found`;
            tbody.innerHTML = '';

            if (filtered.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i data-feather="search" style="width:48px;height:48px;margin-bottom:12px;"></i><br>
                            No bookings match your search criteria.
                        </td>
                    </tr>
                `;
                feather.replace();
                return;
            }

            filtered.forEach(b => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${b.booking_ref}</strong></td>
                    <td>${b.passenger_name}</td>
                    <td>
                        <div>${b.route}</div>
                        <small class="text-muted">${b.airline}</small>
                    </td>
                    <td><span class="badge badge-soft">${b.class.charAt(0).toUpperCase() + b.class.slice(1)}</span></td>
                    <td>${b.passengers}</td>
                    <td><strong>₦${parseInt(b.total_amount).toLocaleString()}</strong></td>
                    <td>${b.paystack_reference ? `<span class="paystack-ref">${b.paystack_reference}</span>` : '<span class="text-muted">-</span>'}</td>
                    <td>${statusBadge(b.status)}</td>
                    <td>${formatDate(b.created_at)}</td>
                    <td>
                        <button class="action-chip chip-blue btn-sm" onclick="showBookingDetails('${b.booking_ref}')">
                            <i data-feather="eye"></i> View Details
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            feather.replace();
        }
        // Add missing helper functions
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-NG', {
                style: 'currency',
                currency: 'NGN'
            }).format(amount);
        }

        function getClassMultiplier(className) {
            const multipliers = {
                'economy': 1.0,
                'business': 2.5,
                'first': 4.0
            };
            return multipliers[className.toLowerCase()] || 1.0;
        }

        function paymentStatusBadge(status) {
            const badges = {
                'paid': '<span class="badge bg-success"><i data-feather="check-circle" style="width:14px;height:14px;"></i> Paid</span>',
                'pending': '<span class="badge bg-warning"><i data-feather="clock" style="width:14px;height:14px;"></i> Pending</span>',
                'failed': '<span class="badge bg-danger"><i data-feather="x-circle" style="width:14px;height:14px;"></i> Failed</span>'
            };
            return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
        }

        // Add time elapsed function for notifications
        function time_elapsed_string(datetime, full = false) {
            const now = new Date();
            const ago = new Date(datetime);
            const diff = Math.floor((now - ago) / 1000);

            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
            if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
            return Math.floor(diff / 86400) + ' days ago';
        }

       // Event listeners for filters
        [searchInput, statusFilter, classFilter, dateFrom, dateTo].forEach(el => {
            el.addEventListener('input', renderBookings);
        });

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', () => {
            location.reload();
        });

        // Booking details modal
        const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

        // Show booking details function
        async function showBookingDetails(bookingRef) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_booking_details');
                formData.append('booking_ref', bookingRef);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const booking = result.booking;
                    
                    // Populate booking details
                    document.getElementById('detailRef').textContent = booking.booking_ref;
                    document.getElementById('detailStatus').innerHTML = statusBadge(booking.status);
                    document.getElementById('detailBookingDate').textContent = formatDateTime(booking.created_at);

                    // Passenger information
                    document.getElementById('detailPassengerName').textContent = booking.passenger_name || 'Unknown';
                    document.getElementById('detailPassengerEmail').textContent = booking.passenger_email || 'Not available';
                    document.getElementById('detailPassengerPhone').textContent = booking.passenger_phone || 'Not available';

                    // Flight information
                    document.getElementById('detailAirline').textContent = booking.airline || 'Speed of Light Airlines';
                    document.getElementById('detailFlightNumber').textContent = booking.flight_no || 'Not available';
                    document.getElementById('detailRoute').textContent = booking.origin && booking.destination ? 
                        `${booking.origin} → ${booking.destination}` : 'Route Not Available';
                    document.getElementById('detailAircraft').textContent = booking.aircraft || 'Not specified';
                    document.getElementById('detailClass').textContent = booking.class ? booking.class.charAt(0).toUpperCase() + booking.class.slice(1) : 'Economy';
                    document.getElementById('detailPassengers').textContent = booking.passengers || '1';
                    
                    // Format departure and arrival times
                    const departureDateTime = booking.flight_date && booking.departure_time ? 
                        `${booking.flight_date} ${booking.departure_time}` : 'Not scheduled';
                    const arrivalDateTime = booking.flight_date && booking.arrival_time ? 
                        `${booking.flight_date} ${booking.arrival_time}` : 'Not scheduled';
                    
                    document.getElementById('detailDeparture').textContent = departureDateTime !== 'Not scheduled' ? 
                        formatDateTime(departureDateTime) : departureDateTime;
                    document.getElementById('detailArrival').textContent = arrivalDateTime !== 'Not scheduled' ? 
                        formatDateTime(arrivalDateTime) : arrivalDateTime;

                    // Payment information
                    const basePrice = parseFloat(booking.flight_base_price) || 0;
                    const classMultiplier = getClassMultiplier(booking.class || 'economy');
                    
                    document.getElementById('detailBasePrice').textContent = formatCurrency(basePrice);
                    document.getElementById('detailClassMultiplier').textContent = `${classMultiplier}x (${(booking.class || 'economy').charAt(0).toUpperCase() + (booking.class || 'economy').slice(1)} Class)`;
                    document.getElementById('detailAmount').textContent = formatCurrency(booking.total_amount);
                    document.getElementById('detailPaymentStatus').innerHTML = paymentStatusBadge(booking.status === 'completed' ? 'paid' : 'pending');
                    document.getElementById('detailPaystackRef').textContent = booking.paystack_reference || booking.payment_reference || 'Not available';
                    document.getElementById('detailPaymentDate').textContent = booking.status === 'completed' ? 
                        formatDateTime(booking.created_at) : 'Not paid';

                    // Store current booking reference for actions
                    detailsModal._currentBookingRef = booking.booking_ref;

                    detailsModal.show();
                    feather.replace();
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error loading booking details', 'error');
                console.error('Error:', error);
            }
        }

        // Table click handler for view details buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('button[data-action="details"]')) {
                const btn = e.target.closest('button[data-action="details"]');
                const bookingRef = btn.getAttribute('data-ref');
                showBookingDetails(bookingRef);
            }
        });

        // Verify payment button
        document.getElementById('verifyPaymentBtn').addEventListener('click', async () => {
            const bookingRef = detailsModal._currentBookingRef;
            if (!bookingRef) return;

            const btn = document.getElementById('verifyPaymentBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying...';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'verify_payment');
                formData.append('booking_ref', bookingRef);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    notify(result.message);
                    detailsModal.hide();
                    // Reload page to refresh data
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error verifying payment', 'error');
                console.error('Error:', error);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        // Initial render
        renderBookings();

        // Add missing notifications variable for the topbar
        <?php
        // Get recent notifications for the notification dropdown
        $notifications = [];
        $notification_count = 0;

        try {
            $stmt = $pdo->query("
                SELECT 
                    b.booking_ref,
                    b.total_amount,
                    b.status,
                    b.created_at,
                    u.full_name as user_name,
                    f.flight_no
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.user_id
                LEFT JOIN flights f ON b.flight_id = f.flight_id
                WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY b.created_at DESC
                LIMIT 5
            ");
            $recent_bookings = $stmt->fetchAll();

            foreach ($recent_bookings as $booking) {
                $timeAgo = time_elapsed_string($booking['created_at']);
                $icon = $booking['status'] === 'completed' ? 'check-circle' : 'clock';
                $notifications[] = [
                    'icon' => $icon,
                    'title' => $booking['status'] === 'completed' ? 'Booking confirmed' : 'New booking',
                    'meta' => ($booking['flight_no'] ?: 'Flight') . " • ₦" . number_format($booking['total_amount'], 2) . " • {$timeAgo}",
                    'time' => $booking['created_at']
                ];
            }

            $notification_count = count($notifications);
        } catch (PDOException $e) {
            $notifications = [];
            $notification_count = 0;
        }
        ?>
    </script>
</body>
</html>