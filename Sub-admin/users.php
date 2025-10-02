<?php
session_start();

// Authentication Check - Redirect if not logged in or not a sub admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'sub_admin') {
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

// Get sub admin information
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'];
$admin_email = $_SESSION['user_email'];

// Get admin's first name for display
$first_name = explode(' ', $admin_name)[0];
$admin_initial = strtoupper(substr($first_name, 0, 1));

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'add_user':
                $role = $_POST['role'];
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $status = $_POST['status'] === 'Active' ? 'active' : 'inactive';

                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Only allow Customer role for sub-admin
                if ($role === 'Customer') {
                    // Check if email exists in users table
                    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(['success' => false, 'message' => 'Email already exists in customers']);
                        break;
                    }

                    // Insert into users table - set created_by to sub_admin_id
                    $stmt = $pdo->prepare("
    INSERT INTO users (full_name, email, password, status, membership, created_at, updated_at, created_by, updated_by) 
    VALUES (?, ?, ?, ?, 'basic', NOW(), NOW(), NULL, NULL)
");
                    $result = $stmt->execute([$name, $email, $hashedPassword, $status]);

                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Customer added successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to add customer']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Sub-admins can only add customers']);
                }
                break;

            case 'toggle_user_status':
    $type = $_POST['type']; // Only 'customer' allowed
    $id = $_POST['id'];
    $currentStatus = $_POST['current_status'];
    $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';

    if ($type === 'customer') {
        // Update users table - set updated_by to NULL
        $stmt = $pdo->prepare("
            UPDATE users 
            SET status = ?, updated_at = NOW(), updated_by = NULL
            WHERE user_id = ?
        ");
        $result = $stmt->execute([$newStatus, $id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Customer status updated', 'new_status' => $newStatus]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update customer status']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Sub-admins can only manage customers']);
    }
    break;

            case 'update_user':
    $type = $_POST['type'];
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $status = $_POST['status'] === 'Active' ? 'active' : 'inactive';
    $newPassword = trim($_POST['password']);

    if ($type === 'customer') {
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, password = ?, status = ?, updated_at = NOW(), updated_by = NULL
                WHERE user_id = ?
            ");
            $result = $stmt->execute([$name, $email, $hashedPassword, $status, $id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, status = ?, updated_at = NOW(), updated_by = NULL
                WHERE user_id = ?
            ");
            $result = $stmt->execute([$name, $email, $status, $id]);
        }

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update customer']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Sub-admins can only manage customers']);
    }
    break;

            case 'reset_password':
    $type = $_POST['type'];
    $id = $_POST['id'];
    $newPassword = trim($_POST['password']);

    if (empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'Password cannot be empty']);
        break;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    if ($type === 'customer') {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, updated_at = NOW(), updated_by = NULL
            WHERE user_id = ?
        ");
        $result = $stmt->execute([$hashedPassword, $id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Sub-admins can only manage customers']);
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

// Get all customers (users) - now including status column
$customers = [];
try {
    $stmt = $pdo->query("
        SELECT 
            user_id,
            full_name,
            email,
            COALESCE(status, 'active') as status,
            created_at,
            updated_at,
            created_by,
            updated_by,
            membership
        FROM users 
        ORDER BY created_at DESC
    ");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $customers = [];
}

// Get notifications count (recent customer registrations only)
$notification_count = 0;
$notifications = [];
try {
    // Get recent user registrations (last 24 hours)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $userRegistrations = $stmt->fetch()['count'];

    $notification_count = $userRegistrations;

    // Get actual notifications
    if ($userRegistrations > 0) {
        $notifications[] = [
            'icon' => 'user-plus',
            'title' => 'New customer registrations',
            'meta' => "$userRegistrations new customer(s) today"
        ];
    }
} catch (PDOException $e) {
    $notification_count = 0;
    $notifications = [];
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
    <meta name="description" content="User Management - Speed of Light Airlines Sub-Admin" />
    <meta name="keywords" content="airline sub-admin, user management, customers">
    <meta name="author" content="Speed of Light Airlines" />
    <title>User Management | Speed of Light Airlines Sub-Admin</title>
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
            overflow: hidden;
            text-decoration: none;
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
            background: linear-gradient(45deg, #00539C, #003366);
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
            background: linear-gradient(90deg, #00539C, #003366)
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
            border-bottom: 2px solid rgba(0, 83, 156, .15)
        }

        .table tbody td {
            vertical-align: middle
        }

        .action-chip {
            border: none;
            border-radius: 10px;
            padding: 8px 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #fff;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-chip:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .chip-blue {
            background: linear-gradient(135deg, #00539C, #003366)
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

        /* Force table visibility */
        #customersTable {
            visibility: visible !important;
            opacity: 1 !important;
            display: table !important;
        }

        #customersBody {
            visibility: visible !important;
            opacity: 1 !important;
            display: table-row-group !important;
        }

        /* Prevent any animations from hiding tables */
        .smart-card .table-responsive,
        .smart-card .table-container,
        .smart-card table,
        .smart-card tbody {
            visibility: visible !important;
            opacity: 1 !important;
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
            <div class="brand-title">SOLA</div>
        </div>
        <nav class="nav-section">
            <a href="dashboard.php" class="nav-link"><i data-feather="home"></i> Dashboard</a>
            <a href="manage-flights.php" class="nav-link"><i data-feather="navigation"></i> Manage Flights</a>
            <a href="bookings.php" class="nav-link"><i data-feather="calendar"></i> Bookings</a>
            <a href="users.php" class="nav-link active"><i data-feather="users"></i> User Management</a>
            <a href="profile.php" class="nav-link"><i data-feather="user"></i> Profile</a>
            <a href="logout.php" class="nav-link"><i data-feather="log-out"></i> Logout</a>
        </nav>
    </aside>

    <!-- Topbar -->
    <header class="topbar d-flex align-items-center">
        <button class="btn btn-sm btn-light d-lg-none" id="menuToggle"><i data-feather="menu"></i></button>
        <div class="ticker ms-3 me-3 flex-grow-1 d-none d-md-block">
            <div class="ticker-track">
                <span>Customer Management • View and manage customers • Add / update / deactivate accounts • Welcome back <?php echo htmlspecialchars($admin_name); ?> • </span>
                <span>Customer Management • View and manage customers • Add / update / deactivate accounts • Welcome back <?php echo htmlspecialchars($admin_name); ?> • </span>
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
                            <a href="users.php" class="small" style="color:var(--brand)">View all</a>
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
            <h1>Customer Management</h1>
            <div class="text-muted">View all registered customers, add/update/deactivate accounts, and manage customer information.</div>
        </div>

        <!-- Add New Customer -->
        <div class="smart-card reveal mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-bold">Add New Customer</div>
                <button class="btn btn-sm btn-outline-primary" id="toggleAdd">Hide</button>
            </div>
            <form id="addUserForm" class="row g-3" novalidate>
                <div class="col-md-4">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="uName" placeholder="e.g., John Doe" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="uEmail" placeholder="e.g., user@example.com" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select id="uStatus" class="form-select" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" id="uPass" required minlength="6">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="uPass2" required minlength="6">
                </div>
                <div class="col-12 mt-2">
                    <button class="action-chip chip-green" type="submit" id="addUserBtn">
                        <i data-feather="check"></i> Add Customer
                    </button>
                </div>
            </form>
        </div>

        <!-- Customers Table -->
        <div class="smart-card reveal">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                <div class="fw-bold">Registered Customers (<?php echo count($customers); ?>)</div>
                <div class="d-flex gap-2 align-items-center">
                    <input type="text" id="searchCustomers" class="form-control form-control-sm" placeholder="Search customers">
                    <select id="statusCustomers" class="form-select form-select-sm" style="width:auto;min-width:130px">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="customersTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Membership</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="customersBody">
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="small text-muted mt-2" id="customersCount"><?php echo count($customers); ?> customer(s)</div>
        </div>
    </main>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i data-feather="edit-2"></i> Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm" class="row g-3" novalidate>
                        <input type="hidden" id="editType" value="customer">
                        <input type="hidden" id="editId">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="editName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select id="editStatus" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password (optional)</label>
                            <input type="password" class="form-control" id="editPass" placeholder="Leave blank to keep same">
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

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i data-feather="key"></i> Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">Enter a new password for this customer.</div>
                    <input type="hidden" id="resetType" value="customer">
                    <input type="hidden" id="resetId">
                    <input type="password" id="resetPass" class="form-control" placeholder="New password" required minlength="6">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="confirmResetBtn" class="btn btn-warning">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        Reset Password
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

        // Immediately show all reveal elements
        document.addEventListener('DOMContentLoaded', function() {
            const revealEls = document.querySelectorAll('.reveal');
            revealEls.forEach(el => {
                el.classList.add('visible');
            });
        });

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

        // Load data from PHP
        let customers = <?php echo json_encode($customers); ?>;

        console.log('Customers loaded:', customers.length);

        // Elements
        const customersBody = document.getElementById('customersBody');
        const customersCount = document.getElementById('customersCount');
        const searchCustomers = document.getElementById('searchCustomers');
        const statusCustomers = document.getElementById('statusCustomers');
        const addUserForm = document.getElementById('addUserForm');

        // Helpers
        function statusBadge(s) {
            if (s === 'active' || s === 'Active') return '<span class="badge bg-success">Active</span>';
            return '<span class="badge bg-secondary">Inactive</span>';
        }

        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString();
        }

        function actions(type, id, u) {
            const currentStatus = u.status || 'active';
            const toggleBtn = `<button class="action-chip ${currentStatus==='active'?'chip-gray':'chip-green'} btn-sm me-1 mb-1" data-action="toggle" data-type="${type}" data-id="${id}" data-status="${currentStatus}"><i data-feather="power"></i> ${currentStatus==='active'?'Deactivate':'Activate'}</button>`;
            const editBtn = `<button class="action-chip chip-blue btn-sm me-1 mb-1" data-action="edit" data-type="${type}" data-id="${id}"><i data-feather="edit"></i> Edit</button>`;
            const resetBtn = `<button class="action-chip chip-orange btn-sm mb-1" data-action="reset" data-type="${type}" data-id="${id}"><i data-feather="key"></i> Reset</button>`;
            return editBtn + toggleBtn + resetBtn;
        }

        function renderCustomers() {
            console.log('Rendering customers...');
            const q = (searchCustomers.value || '').toLowerCase();
            const sf = statusCustomers.value;
            const filtered = customers.filter(u => {
                if (q && !(u.full_name + u.email).toLowerCase().includes(q)) return false;
                if (sf && u.status !== sf) return false;
                return true;
            });
            customersCount.textContent = `${filtered.length} customer(s)`;

            if (!customersBody) {
                console.error('customersBody not found');
                return;
            }

            // Force visibility
            customersBody.style.display = 'table-row-group';
            customersBody.style.visibility = 'visible';
            customersBody.innerHTML = '';

            if (filtered.length === 0) {
                customersBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i data-feather="search" style="width:48px;height:48px;margin-bottom:12px;"></i><br>
                            ${q || sf ? 'No customers match your search.' : 'No customers found.'}
                        </td>
                    </tr>
                `;
                feather.replace();
                return;
            }

            filtered.forEach((u) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${u.full_name}</td>
                    <td>${u.email}</td>
                    <td><span class="badge badge-soft">${u.membership || 'Basic'}</span></td>
                    <td>${formatDate(u.created_at)}</td>
                    <td>${statusBadge(u.status)}</td>
                    <td>${actions('customer', u.user_id, u)}</td>`;
                customersBody.appendChild(tr);
            });
            feather.replace();
        }

        // Force table visibility on load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, forcing table visibility...');

            const table = document.getElementById('customersTable');
            const tbody = document.getElementById('customersBody');

            if (table) {
                table.style.display = 'table';
                table.style.visibility = 'visible';
                table.style.opacity = '1';
            }
            if (tbody) {
                tbody.style.display = 'table-row-group';
                tbody.style.visibility = 'visible';
                tbody.style.opacity = '1';
            }

            // Render data
            renderCustomers();
        });

        // Initial render
        renderCustomers();

        // Filters listeners
        if (searchCustomers) searchCustomers.addEventListener('input', renderCustomers);
        if (statusCustomers) statusCustomers.addEventListener('input', renderCustomers);

        // Toggle add form
        document.getElementById('toggleAdd').addEventListener('click', () => {
            const isHidden = addUserForm.classList.contains('d-none');
            addUserForm.classList.toggle('d-none');
            document.getElementById('toggleAdd').textContent = isHidden ? 'Hide' : 'Show';
        });

        // Add user
        addUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!addUserForm.checkValidity()) {
                addUserForm.classList.add('was-validated');
                notify('Please fill in all required fields', 'error');
                return;
            }

            const pass = document.getElementById('uPass').value;
            const pass2 = document.getElementById('uPass2').value;
            if (pass !== pass2) {
                notify('Passwords do not match', 'error');
                return;
            }

            const addBtn = document.getElementById('addUserBtn');
            const originalText = addBtn.innerHTML;
            addBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
            addBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'add_user');
                formData.append('name', document.getElementById('uName').value.trim());
                formData.append('email', document.getElementById('uEmail').value.trim());
                formData.append('role', 'Customer'); // Fixed to Customer only
                formData.append('status', document.getElementById('uStatus').value);
                formData.append('password', pass);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    notify(result.message);
                    addUserForm.reset();
                    addUserForm.classList.remove('was-validated');
                    // Reload page to refresh data
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error adding customer. Please try again.', 'error');
                console.error('Error:', error);
            } finally {
                addBtn.innerHTML = originalText;
                addBtn.disabled = false;
            }
        });

        // Table actions
        function handleActionClick(e) {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;

            const action = btn.getAttribute('data-action');
            const type = btn.getAttribute('data-type');
            const id = btn.getAttribute('data-id');

            console.log('Action clicked:', action, type, id);

            if (action === 'toggle') {
                toggleUserStatus(type, id, btn.getAttribute('data-status'), btn);
            }
            if (action === 'edit') {
                openEdit(type, id);
            }
            if (action === 'reset') {
                openReset(type, id);
            }
        }

        // Add event listener to table body
        if (customersBody) customersBody.addEventListener('click', handleActionClick);

        // Toggle user status
        async function toggleUserStatus(type, id, currentStatus, btn) {
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_user_status');
                formData.append('type', type);
                formData.append('id', id);
                formData.append('current_status', currentStatus);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    notify(result.message);
                    // Update local data
                    const customer = customers.find(c => c.user_id == id);
                    if (customer) customer.status = result.new_status;
                    renderCustomers();
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error updating customer status', 'error');
                console.error('Toggle error:', error);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // Edit modal
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));

        function openEdit(type, id) {
            const user = customers.find(c => c.user_id == id);

            if (!user) {
                console.error('Customer not found:', id);
                return;
            }

            document.getElementById('editType').value = type;
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = user.full_name;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editStatus').value = (user.status === 'active') ? 'Active' : 'Inactive';
            document.getElementById('editPass').value = '';
            editModal.show();
        }

        document.getElementById('saveEditBtn').addEventListener('click', async () => {
            const saveBtn = document.getElementById('saveEditBtn');
            const spinner = saveBtn.querySelector('.spinner-border');
            spinner.classList.remove('d-none');
            saveBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'update_user');
                formData.append('type', document.getElementById('editType').value);
                formData.append('id', document.getElementById('editId').value);
                formData.append('name', document.getElementById('editName').value.trim());
                formData.append('email', document.getElementById('editEmail').value.trim());
                formData.append('status', document.getElementById('editStatus').value);
                formData.append('password', document.getElementById('editPass').value.trim());

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    notify(result.message);
                    editModal.hide();
                    // Reload page to refresh data
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error updating customer', 'error');
                console.error('Edit error:', error);
            } finally {
                spinner.classList.add('d-none');
                saveBtn.disabled = false;
            }
        });

        // Reset modal
        const resetModal = new bootstrap.Modal(document.getElementById('resetModal'));

        function openReset(type, id) {
            document.getElementById('resetType').value = type;
            document.getElementById('resetId').value = id;
            document.getElementById('resetPass').value = '';
            resetModal.show();
        }

        document.getElementById('confirmResetBtn').addEventListener('click', async () => {
            const p = document.getElementById('resetPass').value.trim();
            if (!p) {
                notify('Please enter a new password', 'error');
                return;
            }

            const resetBtn = document.getElementById('confirmResetBtn');
            const spinner = resetBtn.querySelector('.spinner-border');
            spinner.classList.remove('d-none');
            resetBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'reset_password');
                formData.append('type', document.getElementById('resetType').value);
                formData.append('id', document.getElementById('resetId').value);
                formData.append('password', p);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    notify(result.message);
                    resetModal.hide();
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error resetting password', 'error');
                console.error('Reset error:', error);
            } finally {
                spinner.classList.add('d-none');
                resetBtn.disabled = false;
            }
        });
    </script>
</body>

</html>