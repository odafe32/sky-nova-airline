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
$admin_data = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $admin_data = $stmt->fetch();
    
    if (!$admin_data) {
        // Admin not found, logout
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error fetching admin data: " . $e->getMessage());
}

// Check if preferences columns exist, if not add them
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM admins LIKE 'email_notifications'");
    if ($stmt->rowCount() == 0) {
        // Add preference columns
        $pdo->exec("ALTER TABLE admins ADD COLUMN email_notifications TINYINT(1) DEFAULT 1");
        $pdo->exec("ALTER TABLE admins ADD COLUMN booking_alerts TINYINT(1) DEFAULT 1");
        $pdo->exec("ALTER TABLE admins ADD COLUMN dark_mode TINYINT(1) DEFAULT 0");
        $pdo->exec("ALTER TABLE admins ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
        $pdo->exec("ALTER TABLE admins ADD COLUMN timezone VARCHAR(50) DEFAULT 'Africa/Lagos'");
        
        // Set default values for current admin
        $stmt = $pdo->prepare("UPDATE admins SET email_notifications = 1, booking_alerts = 1, dark_mode = 0 WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        
        // Refresh admin data
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $admin_data = $stmt->fetch();
    }
} catch (PDOException $e) {
    // Columns might already exist, continue
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'update_profile':
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']) ?: null;
                $timezone = $_POST['timezone'];
                
                // Validate inputs
                if (empty($full_name) || empty($email)) {
                    echo json_encode(['success' => false, 'message' => 'Full name and email are required']);
                    exit();
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                    exit();
                }
                
                // Check if email is already taken by another admin
                $stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE email = ? AND admin_id != ?");
                $stmt->execute([$email, $admin_id]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Email is already taken by another admin']);
                    exit();
                }
                
                // Update profile
                $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, email = ?, phone = ?, timezone = ? WHERE admin_id = ?");
                $result = $stmt->execute([$full_name, $email, $phone, $timezone, $admin_id]);
                
                if ($result) {
                    // Update session data
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['user_email'] = $email;
                    
                    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validate inputs
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    echo json_encode(['success' => false, 'message' => 'All password fields are required']);
                    exit();
                }
                
                if ($new_password !== $confirm_password) {
                    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
                    exit();
                }
                
                if (strlen($new_password) < 8) {
                    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
                    exit();
                }
                
                // Verify current password
                if (!password_verify($current_password, $admin_data['password'])) {
                    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                    exit();
                }
                
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                $result = $stmt->execute([$hashed_password, $admin_id]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to change password']);
                }
                break;
                
            case 'update_preferences':
                $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
                $booking_alerts = isset($_POST['booking_alerts']) ? 1 : 0;
                $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
                
                // Update preferences
                $stmt = $pdo->prepare("UPDATE admins SET email_notifications = ?, booking_alerts = ?, dark_mode = ? WHERE admin_id = ?");
                $result = $stmt->execute([$email_notifications, $booking_alerts, $dark_mode, $admin_id]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Preferences saved successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save preferences']);
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

// Get admin's first name and initial for display
$first_name = explode(' ', $admin_data['full_name'])[0];
$admin_initial = strtoupper(substr($first_name, 0, 1));

// Get recent notifications
$notifications = [];
$notification_count = 0;

try {
    // Get recent activities for notifications
    $stmt = $pdo->query("
        SELECT 
            'booking' as type,
            b.booking_ref as reference,
            b.total_amount,
            b.created_at,
            u.full_name as user_name,
            f.flight_no
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN flights f ON b.flight_id = f.flight_id
        WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $recent_activities = $stmt->fetchAll();
    
    foreach ($recent_activities as $activity) {
        $timeAgo = time_elapsed_string($activity['created_at']);
        $notifications[] = [
            'icon' => 'calendar',
            'title' => 'New booking',
            'meta' => "{$activity['flight_no']} · ₦" . number_format($activity['total_amount'], 2) . " · {$timeAgo}",
            'time' => $activity['created_at']
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
    <meta name="description" content="Profile - Speed of Light Airlines Admin" />
    <meta name="keywords" content="airline admin, profile, account, preferences">
    <meta name="author" content="Speed of Light Airlines" />
    <title>Profile | Speed of Light Airlines</title>
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
        :root{ --brand:#38a169; --brand-dark:#38a169; --bg:#f8fafc; --card-bg:rgba(255,255,255,0.96); --muted:#64748b; --success:#22c55e; --warning:#f59e0b; --danger:#ef4444; --info:#0ea5e9; }
        *{box-sizing:border-box}
        body{background:var(--bg);font-family:'Inter','Segoe UI',Arial,sans-serif;min-height:100vh;overflow-x:hidden}
        /* Sidebar */
        .sidebar{position:fixed;left:0;top:0;bottom:0;width:260px;background:var(--brand-dark);box-shadow:2px 0 20px rgba(0,0,0,.08);z-index:1000;color:#fff;padding:20px 16px}
        .brand{display:flex;align-items:center;gap:10px;margin-bottom:18px}
        .brand img{width:38px;height:38px;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,.3)}
        .brand-title{font-weight:800;letter-spacing:1px}
        .nav-section{margin-top:8px}
        .nav-link{color:#e5e7eb;border-radius:12px;padding:12px 14px;margin-bottom:8px;display:flex;align-items:center;gap:12px;transition:all .25s ease;position:relative;overflow:hidden}
        .nav-link:hover,.nav-link.active{background:var(--brand);color:#fff;transform:translateX(4px);box-shadow:0 6px 18px rgba(0,83,156,.25)}
        .nav-link i{width:18px;height:18px}
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

        .topbar .right{display:flex;align-items:center;gap:14px;margin-left:auto}
        .cart-icon-container{position:relative;cursor:pointer;transition:all .3s ease;padding:6px 10px;border-radius:10px;background:rgba(255,255,255,.12)}
        .cart-icon-container:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(0,0,0,.2)}
        .cart-badge{position:absolute;top:-6px;right:-6px;background:linear-gradient(135deg,#ff6b6b,#ee5a52);color:#fff;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;box-shadow:0 2px 8px rgba(255,107,107,.4)}
        .user-info{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.12);padding:6px 12px;border-radius:10px}
        .user-avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#48bb78,#38a169);display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff}
        /* Ticker */
        .ticker{display:none;overflow:hidden;white-space:nowrap;max-width:55%}
        @media(min-width:768px){.ticker{display:block}}
        .ticker-track{display:inline-block;padding-left:100%;animation:ticker 22s linear infinite;color:#e2e8f0;font-weight:600}
        @keyframes ticker{0%{transform:translateX(0)}100%{transform:translateX(-100%)}}
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
        .content{margin-left:260px;padding:22px 18px}
        .page-title h1{font-weight:800;letter-spacing:.5px;background:linear-gradient(45deg,#38a169,#38a169);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .smart-card{background:var(--card-bg);border:1px solid rgba(0,83,156,.08);border-radius:20px;padding:18px;box-shadow:0 8px 24px rgba(0,83,156,.10);position:relative;overflow:hidden;transition:transform .35s cubic-bezier(.175,.885,.32,1.275),box-shadow .35s}
        .smart-card:hover{transform:translateY(-6px) scale(1.01);box-shadow:0 18px 50px rgba(0,83,156,.20)}
        .smart-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#38a169,#38a169)}
        .reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease, transform .6s ease}
        .reveal.visible{opacity:1;transform:translateY(0)}
        .form-label{font-weight:600;color:#334155}
        .btn-gradient{border:none;border-radius:12px;font-weight:700;padding:12px 16px;display:inline-flex;align-items:center;gap:8px;color:#fff;box-shadow:0 8px 22px rgba(0,83,156,.25);background:linear-gradient(135deg,#38a169,#38a169)}
        .btn-green{background:linear-gradient(135deg,#10b981,#059669)}
        .badge-soft{background:rgba(0,83,156,.08);color:var(--brand-dark);border:1px solid rgba(0,83,156,.15)}
        .password-visibility{cursor:pointer;color:#64748b}
        .strength{height:8px;border-radius:6px;background:#e5e7eb;overflow:hidden}
        .strength-bar{height:8px;width:0;background:#ef4444;transition:width .3s ease, background .3s ease}
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        @media(max-width:991px){.sidebar{left:-260px;transition:left .3s}.sidebar.show{left:0}.topbar,.content{margin-left:0}}
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
            <a href="reports.php" class="nav-link"><i data-feather="bar-chart-2"></i> Generate Reports</a>
            <a href="profile.php" class="nav-link active"><i data-feather="user"></i> Profile</a>
            <a href="logout.php" class="nav-link"><i data-feather="log-out"></i> Logout</a>
        </nav>
    </aside>

    <!-- Topbar -->
    <header class="topbar d-flex align-items-center">
        <button class="btn btn-sm btn-light d-lg-none" id="menuToggle"><i data-feather="menu"></i></button>
        <div class="ticker ms-3 me-3 flex-grow-1 d-none d-md-block">
            <div class="ticker-track">
                <span>Profile • Update personal details • Change password • Manage preferences • Welcome back <?php echo htmlspecialchars($admin_data['full_name']); ?> • </span>
                <span>Profile • Update personal details • Change password • Manage preferences • Welcome back <?php echo htmlspecialchars($admin_data['full_name']); ?> • </span>
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
        <!-- Profile Hero -->
        <div class="smart-card reveal mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="user-avatar" style="width:64px;height:64px;font-size:1.3rem"><?php echo $admin_initial; ?></div>
                <div>
                    <div class="fw-bold" style="font-size:1.25rem">Welcome back, <?php echo htmlspecialchars($admin_data['full_name']); ?></div>
                    <div class="text-muted">Manage your personal information and account preferences</div>
                    <div class="small text-muted mt-1">
                        <i data-feather="calendar" style="width:14px;height:14px;"></i>
                        Member since <?php echo date('M Y', strtotime($admin_data['created_at'])); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Personal Details -->
            <div class="col-12 col-xl-6 reveal">
                <div class="smart-card h-100">
                    <div class="fw-bold mb-2"><i data-feather="user"></i> Personal Details</div>
                    <form id="detailsForm" class="row g-3" novalidate>
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullName" placeholder="e.g., John Doe" value="<?php echo htmlspecialchars($admin_data['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" placeholder="e.g., admin@example.com" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone (optional)</label>
                            <input type="text" class="form-control" id="phone" placeholder="e.g., +234 903 316 2442" value="<?php echo htmlspecialchars($admin_data['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Timezone</label>
                            <select id="timezone" class="form-select">
                                <option value="Africa/Lagos" <?php echo ($admin_data['timezone'] ?? 'Africa/Lagos') === 'Africa/Lagos' ? 'selected' : ''; ?>>Africa/Lagos (WAT)</option>
                                <option value="UTC" <?php echo ($admin_data['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                <option value="Europe/Paris" <?php echo ($admin_data['timezone'] ?? '') === 'Europe/Paris' ? 'selected' : ''; ?>>Europe/Paris (CET)</option>
                                <option value="America/New_York" <?php echo ($admin_data['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>America/New_York (ET)</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn-gradient" id="updateProfileBtn">
                                <i data-feather="save"></i> Save Changes
                            </button>
                            <button type="reset" class="btn btn-outline-secondary"><i data-feather="rotate-ccw"></i> Reset</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="col-12 col-xl-6 reveal">
                <div class="smart-card h-100">
                    <div class="fw-bold mb-2"><i data-feather="lock"></i> Change Password</div>
                    <form id="passwordForm" class="row g-3" novalidate>
                        <div class="col-12">
                            <label class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="currPass" required>
                                <span class="input-group-text password-visibility" data-target="currPass"><i data-feather="eye"></i></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="newPass" required>
                                <span class="input-group-text password-visibility" data-target="newPass"><i data-feather="eye"></i></span>
                            </div>
                            <div class="strength mt-2"><div id="strengthBar" class="strength-bar"></div></div>
                            <small class="text-muted">Use at least 8 characters, mixing letters, numbers and symbols.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPass" required>
                                <span class="input-group-text password-visibility" data-target="confirmPass"><i data-feather="eye"></i></span>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn-gradient btn-green" id="changePasswordBtn">
                                <i data-feather="check"></i> Update Password
                            </button>
                            <button type="reset" class="btn btn-outline-secondary"><i data-feather="rotate-ccw"></i> Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Preferences -->
        <div class="smart-card reveal mt-3">
            <div class="fw-bold mb-2"><i data-feather="settings"></i> Account Preferences</div>
            <form id="prefsForm" class="row g-3" novalidate>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="prefEmail" <?php echo ($admin_data['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="prefEmail">Email notifications</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="prefBooking" <?php echo ($admin_data['booking_alerts'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="prefBooking">Booking alerts</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="prefDark" <?php echo ($admin_data['dark_mode'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="prefDark">Dark mode (preview only)</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-gradient" id="savePreferencesBtn">
                        <i data-feather="save"></i> Save Preferences
                    </button>
                </div>
            </form>
        </div>

        <footer class="mt-4 text-center text-muted small">&copy; <span id="year"></span> Speed of Light Airlines. Admin Panel.</footer>
    </main>

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
        document.getElementById('year').textContent = new Date().getFullYear();

        // Sidebar toggle for mobile
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        if (menuToggle) menuToggle.addEventListener('click', () => { sidebar.classList.toggle('show'); });

        // Reveal on scroll
        const revealEls = document.querySelectorAll('.reveal');
        const io = new IntersectionObserver((entries)=>{ entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('visible'); io.unobserve(e.target);} }); },{threshold:.1, rootMargin:'0px 0px -50px 0px'});
        revealEls.forEach(el=>io.observe(el));

        // Toast helper
        const toastEl = document.getElementById('liveToast');
        const toast = toastEl ? new bootstrap.Toast(toastEl) : null;
        function notify(msg, type = 'success'){ 
            if(!toast) return; 
            document.getElementById('toastMsg').textContent = msg; 
            toastEl.className = `toast align-items-center border-0 text-bg-${type === 'error' ? 'danger' : 'success'}`;
            toast.show(); 
        }

        // Personal details form
        const detailsForm = document.getElementById('detailsForm');
        const updateProfileBtn = document.getElementById('updateProfileBtn');
        
        detailsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!detailsForm.checkValidity()) { 
                detailsForm.classList.add('was-validated'); 
                notify('Please fill in required fields correctly', 'error'); 
                return; 
            }
            
            // Show loading state
            const originalText = updateProfileBtn.innerHTML;
            updateProfileBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
            updateProfileBtn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'update_profile');
                formData.append('full_name', document.getElementById('fullName').value);
                formData.append('email', document.getElementById('email').value);
                formData.append('phone', document.getElementById('phone').value);
                formData.append('timezone', document.getElementById('timezone').value);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    notify(result.message);
                    detailsForm.classList.remove('was-validated');
                    // Update the welcome message
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error updating profile. Please try again.', 'error');
                console.error('Error:', error);
            } finally {
                updateProfileBtn.innerHTML = originalText;
                updateProfileBtn.disabled = false;
            }
        });

        // Password visibility toggle
        document.querySelectorAll('.password-visibility').forEach(el=>{
            el.addEventListener('click', ()=>{
                const id = el.getAttribute('data-target');
                const input = document.getElementById(id);
                input.type = (input.type==='password')? 'text' : 'password';
                el.querySelector('i').setAttribute('data-feather', input.type==='password' ? 'eye' : 'eye-off');
                feather.replace();
            });
        });

        // Password strength meter
        const newPass = document.getElementById('newPass');
        const strengthBar = document.getElementById('strengthBar');
        
        function evaluateStrength(v){
            let score=0; 
            if(v.length>=8) score++; 
            if(/[A-Z]/.test(v)) score++; 
            if(/[0-9]/.test(v)) score++; 
            if(/[^A-Za-z0-9]/.test(v)) score++;
            return score; // 0-4
        }
        
        newPass.addEventListener('input', ()=>{
            const s = evaluateStrength(newPass.value);
            strengthBar.style.width = `${(s/4)*100}%`;
            strengthBar.style.background = s<=1? '#ef4444' : (s===2? '#f59e0b' : (s===3? '#10b981' : '#059669'));
        });

        // Change password form
        const passwordForm = document.getElementById('passwordForm');
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        
        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!passwordForm.checkValidity()) { 
                passwordForm.classList.add('was-validated'); 
                notify('Please fill in all password fields', 'error'); 
                return; 
            }
            
            const newPassword = document.getElementById('newPass').value;
            const confirmPassword = document.getElementById('confirmPass').value;
            
            if (newPassword !== confirmPassword) { 
                notify('New passwords do not match', 'error'); 
                return; 
            }
            
            if (evaluateStrength(newPassword) < 2) { 
                notify('Choose a stronger password', 'error'); 
                return; 
            }
            
            // Show loading state
            const originalText = changePasswordBtn.innerHTML;
            changePasswordBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
            changePasswordBtn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'change_password');
                formData.append('current_password', document.getElementById('currPass').value);
                formData.append('new_password', newPassword);
                formData.append('confirm_password', confirmPassword);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    notify(result.message);
                    passwordForm.reset();
                    passwordForm.classList.remove('was-validated');
                    strengthBar.style.width = '0%';
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error changing password. Please try again.', 'error');
                console.error('Error:', error);
            } finally {
                changePasswordBtn.innerHTML = originalText;
                changePasswordBtn.disabled = false;
            }
        });

        // Preferences form
        const prefsForm = document.getElementById('prefsForm');
        const prefDark = document.getElementById('prefDark');
        const savePreferencesBtn = document.getElementById('savePreferencesBtn');
        
        prefsForm.addEventListener('submit', async (e) => { 
            e.preventDefault(); 
            
            // Show loading state
            const originalText = savePreferencesBtn.innerHTML;
            savePreferencesBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            savePreferencesBtn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'update_preferences');
                if (document.getElementById('prefEmail').checked) formData.append('email_notifications', '1');
                if (document.getElementById('prefBooking').checked) formData.append('booking_alerts', '1');
                if (document.getElementById('prefDark').checked) formData.append('dark_mode', '1');
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    notify(result.message);
                } else {
                    notify(result.message, 'error');
                }
            } catch (error) {
                notify('Error saving preferences. Please try again.', 'error');
                console.error('Error:', error);
            } finally {
                savePreferencesBtn.innerHTML = originalText;
                savePreferencesBtn.disabled = false;
            }
        });
        
        // Dark mode preview
        prefDark.addEventListener('change', ()=>{
            document.body.style.background = prefDark.checked ? '#0b1220' : 'var(--bg)';
        });
        
        // Apply dark mode on load if enabled
        if (prefDark.checked) {
            document.body.style.background = '#0b1220';
        }
    </script>
</body>
</html>