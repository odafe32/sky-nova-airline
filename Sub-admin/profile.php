<?php
session_start();

// Authentication Check - Redirect if not logged in or not a sub-admin
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

// Handle AJAX requests for password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        if ($_POST['action'] === 'change_password') {
            $sub_admin_id = $_SESSION['user_id'];
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';

            // Validate inputs
            if (empty($current_password) || empty($new_password)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit();
            }

            if (strlen($new_password) < 8) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long']);
                exit();
            }

            // Get current password from database
            $stmt = $pdo->prepare("SELECT password FROM sub_admins WHERE sub_admin_id = ?");
            $stmt->execute([$sub_admin_id]);
            $admin = $stmt->fetch();

            if (!$admin) {
                echo json_encode(['success' => false, 'message' => 'Admin not found']);
                exit();
            }

            // Verify current password
            if (!password_verify($current_password, $admin['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit();
            }

            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password in database
            $stmt = $pdo->prepare("
                UPDATE sub_admins 
                SET password = ?, updated_at = NOW(), last_password_change = NOW() 
                WHERE sub_admin_id = ?
            ");
            $result = $stmt->execute([$hashed_password, $sub_admin_id]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update password']);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Get sub-admin information
$sub_admin_id = $_SESSION['user_id'];
$sub_admin_data = null;

try {
    $stmt = $pdo->prepare("
        SELECT 
            sub_admin_id,
            full_name, 
            email, 
            status, 
            created_at, 
            updated_at, 
            last_password_change 
        FROM sub_admins 
        WHERE sub_admin_id = ?
    ");
    $stmt->execute([$sub_admin_id]);
    $sub_admin_data = $stmt->fetch();

    if (!$sub_admin_data) {
        // Redirect to login if admin not found
        header("Location: ../login.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error fetching admin data: " . $e->getMessage());
}

// Extract admin info
$admin_name = $sub_admin_data['full_name'];
$admin_email = $sub_admin_data['email'];
$admin_status = $sub_admin_data['status'];
$created_at = $sub_admin_data['created_at'];
$last_password_change = $sub_admin_data['last_password_change'];

// Get admin's first name and initial for display
$first_name = explode(' ', $admin_name)[0];
$admin_initial = strtoupper(substr($first_name, 0, 1));

// Calculate account age
$account_age = '';
if ($created_at) {
    $created_date = new DateTime($created_at);
    $now = new DateTime();
    $diff = $now->diff($created_date);

    if ($diff->y > 0) {
        $account_age = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
    } elseif ($diff->m > 0) {
        $account_age = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
    } else {
        $account_age = $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
    }
}

// Calculate last password change
$password_change_text = 'Never';
if ($last_password_change) {
    $password_date = new DateTime($last_password_change);
    $now = new DateTime();
    $diff = $now->diff($password_date);

    if ($diff->d == 0) {
        $password_change_text = 'Today';
    } elseif ($diff->d == 1) {
        $password_change_text = 'Yesterday';
    } elseif ($diff->d < 30) {
        $password_change_text = $diff->d . ' days ago';
    } elseif ($diff->m == 1) {
        $password_change_text = '1 month ago';
    } elseif ($diff->m < 12) {
        $password_change_text = $diff->m . ' months ago';
    } else {
        $password_change_text = $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
}

// Get activity stats for this sub-admin (if we track admin actions)
$actions_performed = 0;
$last_login = 'Today'; // This would come from a login log table if implemented

// For now, we'll use some sample data based on account age
if ($created_at) {
    $days_since_creation = (new DateTime())->diff(new DateTime($created_at))->days;
    $actions_performed = min(999, $days_since_creation * 2); // Simulate activity
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Profile - Speed of Light Airlines Sub Admin" />
    <meta name="keywords" content="airline admin, profile, settings, password">
    <meta name="author" content="Speed of Light Airlines" />
    <title>Profile | Speed of Light Airlines</title>
    <link rel="icon" href="../User/assets/images/airline-favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <!-- Feather Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

        /* Profile Header */
        .profile-header {
            background: linear-gradient(135deg, #38a169 0%, #38a169 100%);
            color: #fff;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            text-align: center
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, .05);
            border-radius: 50%;
            transform: rotate(45deg)
        }

        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, .03);
            border-radius: 50%
        }

        .profile-content {
            position: relative;
            z-index: 2
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #48bb78, #38a169);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 800;
            color: #fff;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .2);
            border: 4px solid rgba(255, 255, 255, .2)
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px
        }

        .profile-role {
            font-size: 1.1rem;
            opacity: .9;
            margin-bottom: 15px
        }

        .profile-status {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: .9rem;
            font-weight: 600
        }

        .profile-status.active {
            background: rgba(34, 197, 94, .2);
            color: #22c55e
        }

        .profile-status.inactive {
            background: rgba(239, 68, 68, .2);
            color: #ef4444
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 25px
        }

        .form-label {
            font-weight: 600;
            color: var(--brand-dark);
            margin-bottom: 8px;
            display: block
        }

        .form-control {
            border: 2px solid rgba(0, 83, 156, .1);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all .3s ease;
            background: var(--card-bg)
        }

        .form-control:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(0, 83, 156, .1);
            background: #fff
        }

        .form-control:read-only {
            background: rgba(0, 83, 156, .03);
            color: var(--muted);
            cursor: not-allowed
        }

        .input-group {
            position: relative
        }

        .input-group-text {
            background: rgba(0, 83, 156, .05);
            border: 2px solid rgba(0, 83, 156, .1);
            border-right: none;
            color: var(--brand-dark)
        }

        .input-group .form-control {
            border-left: none
        }

        /* Password Field */
        .password-field {
            position: relative
        }

        .password-display {
            background: rgba(0, 83, 156, .03);
            border: 2px solid rgba(0, 83, 156, .1);
            border-radius: 12px;
            padding: 12px 16px;
            color: var(--muted);
            cursor: not-allowed;
            display: flex;
            align-items: center;
            justify-content: space-between
        }

        .password-dots {
            letter-spacing: 3px;
            font-size: 1.2rem
        }

        .change-password-btn {
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: .85rem;
            font-weight: 600;
            transition: all .3s ease
        }

        .change-password-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 83, 156, .3)
        }

        /* Security Info */
        .security-info {
            background: rgba(34, 197, 94, .05);
            border: 1px solid rgba(34, 197, 94, .2);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px
        }

        .security-info-title {
            font-weight: 600;
            color: #16a34a;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px
        }

        .security-info-text {
            font-size: .9rem;
            color: #059669;
            margin: 0
        }

        /* Modal Styling */
        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .2)
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 83, 156, .1);
            padding: 20px 25px
        }

        .modal-title {
            font-weight: 700;
            color: var(--brand-dark)
        }

        .modal-body {
            padding: 25px
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 83, 156, .1);
            padding: 20px 25px
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 10px
        }

        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: rgba(0, 0, 0, .1);
            overflow: hidden;
            margin-bottom: 8px
        }

        .strength-fill {
            height: 100%;
            transition: all .3s ease;
            border-radius: 2px
        }

        .strength-weak {
            background: #ef4444;
            width: 25%
        }

        .strength-fair {
            background: #f59e0b;
            width: 50%
        }

        .strength-good {
            background: #3b82f6;
            width: 75%
        }

        .strength-strong {
            background: #22c55e;
            width: 100%
        }

        .strength-text {
            font-size: .8rem;
            font-weight: 600
        }

        .text-weak {
            color: #ef4444
        }

        .text-fair {
            color: #f59e0b
        }

        .text-good {
            color: #3b82f6
        }

        .text-strong {
            color: #22c55e
        }

        /* Action Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all .3s ease
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 83, 156, .3)
        }

        .btn-secondary {
            background: #6b7280;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600
        }

        /* Toast */
        .toast {
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .15)
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

            .profile-name {
                font-size: 1.6rem
            }

            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem
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
            <a href="users.php" class="nav-link"><i data-feather="users"></i>Users</a>
            <a href="profile.php" class="nav-link active"><i data-feather="user"></i> Profile</a>
            <a href="logout.php" class="nav-link"><i data-feather="log-out"></i> Logout</a>
        </nav>
    </aside>

    <!-- Topbar -->
    <header class="topbar d-flex align-items-center">
        <button class="btn btn-sm btn-light d-lg-none" id="menuToggle"><i data-feather="menu"></i></button>

        <div class="right ms-auto">
            <div class="user-info ms-2">
                <div class="user-avatar"><?php echo $admin_initial; ?></div>
                <div class="fw-bold"><?php echo htmlspecialchars($first_name); ?></div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="content">
        <!-- Profile Header -->
        <div class="profile-header reveal">
            <div class="profile-content">
                <div class="profile-avatar"><?php echo $admin_initial; ?></div>
                <div class="profile-name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="profile-role">Sub Administrator</div>
                <div class="profile-status <?php echo strtolower($admin_status); ?>">
                    <i data-feather="<?php echo $admin_status === 'active' ? 'check-circle' : 'x-circle'; ?>" style="width:16px;height:16px"></i>
                    <?php echo ucfirst($admin_status); ?>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Profile Information -->
                <div class="smart-card reveal">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">
                            <i data-feather="user" style="width:20px;height:20px" class="me-2"></i>
                            Profile Information
                        </h5>
                        <small class="text-muted">Last updated: <?php echo date('M j, Y', strtotime($sub_admin_data['updated_at'] ?? $sub_admin_data['created_at'])); ?></small>
                    </div>

                    <form id="profileForm">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i data-feather="user" style="width:16px;height:16px" class="me-1"></i>
                                        Full Name
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i data-feather="user" style="width:16px;height:16px"></i>
                                        </span>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_name); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i data-feather="mail" style="width:16px;height:16px" class="me-1"></i>
                                        Email Address
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i data-feather="mail" style="width:16px;height:16px"></i>
                                        </span>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($admin_email); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i data-feather="lock" style="width:16px;height:16px" class="me-1"></i>
                                        Password
                                    </label>
                                    <div class="password-field">
                                        <div class="password-display">
                                            <span class="password-dots">••••••••••••</span>
                                            <button type="button" class="change-password-btn" id="changePasswordBtn">
                                                <i data-feather="edit-2" style="width:14px;height:14px" class="me-1"></i>
                                                Change Password
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Security Information -->
                    <div class="security-info">
                        <div class="security-info-title">
                            <i data-feather="shield" style="width:18px;height:18px"></i>
                            Security Information
                        </div>
                        <p class="security-info-text">
                            Your account is secured with strong encryption. Only you can change your password.
                            Last password change: <strong><?php echo $password_change_text; ?></strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Change Password Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i data-feather="lock" style="width:20px;height:20px" class="me-2"></i>
                        Change Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="passwordForm" novalidate>
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i data-feather="lock" style="width:16px;height:16px"></i>
                                </span>
                                <input type="password" id="currentPassword" class="form-control" placeholder="Enter current password" required>
                                <button type="button" class="btn btn-outline-secondary" id="toggleCurrent">
                                    <i data-feather="eye" style="width:16px;height:16px"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Please enter your current password</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i data-feather="key" style="width:16px;height:16px"></i>
                                </span>
                                <input type="password" id="newPassword" class="form-control" placeholder="Enter new password" required>
                                <button type="button" class="btn btn-outline-secondary" id="toggleNew">
                                    <i data-feather="eye" style="width:16px;height:16px"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Password must be at least 8 characters</div>
                        </div>

                        <!-- Password Strength Indicator -->
                        <div class="password-strength" id="passwordStrength" style="display:none">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-text" id="strengthText"></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i data-feather="check" style="width:16px;height:16px"></i>
                                </span>
                                <input type="password" id="confirmPassword" class="form-control" placeholder="Confirm new password" required>
                                <button type="button" class="btn btn-outline-secondary" id="toggleConfirm">
                                    <i data-feather="eye" style="width:16px;height:16px"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Passwords do not match</div>
                        </div>

                        <div class="alert alert-info d-flex align-items-start">
                            <i data-feather="info" style="width:16px;height:16px;margin-top:2px" class="me-2"></i>
                            <div>
                                <strong>Password Requirements:</strong><br>
                                • At least 8 characters long<br>
                                • Include uppercase and lowercase letters<br>
                                • Include at least one number<br>
                                • Include at least one special character
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePasswordBtn">
                        <i data-feather="save" style="width:16px;height:16px" class="me-1"></i>
                        Update Password
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

        // Reveal on scroll animation
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

            // Set toast color based on type
            toastEl.className = `toast align-items-center border-0 text-bg-${type === 'error' ? 'danger' : 'success'}`;
            toast.show();
        }

        // Password Modal
        const passwordModal = new bootstrap.Modal(document.getElementById('passwordModal'));
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        const savePasswordBtn = document.getElementById('savePasswordBtn');
        const passwordForm = document.getElementById('passwordForm');

        // Open password modal
        changePasswordBtn.addEventListener('click', () => {
            passwordModal.show();
            // Clear form
            passwordForm.reset();
            passwordForm.classList.remove('was-validated');
            document.getElementById('passwordStrength').style.display = 'none';
            // Clear validation classes
            document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                el.classList.remove('is-valid', 'is-invalid');
            });
        });

        // Password visibility toggles
        function setupPasswordToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            const icon = toggle.querySelector('i');

            toggle.addEventListener('click', () => {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.setAttribute('data-feather', isPassword ? 'eye-off' : 'eye');
                feather.replace();
            });
        }

        setupPasswordToggle('toggleCurrent', 'currentPassword');
        setupPasswordToggle('toggleNew', 'newPassword');
        setupPasswordToggle('toggleConfirm', 'confirmPassword');

        // Password strength checker
        const newPasswordInput = document.getElementById('newPassword');
        const strengthIndicator = document.getElementById('passwordStrength');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');

        function checkPasswordStrength(password) {
            let score = 0;
            let feedback = '';

            if (password.length >= 8) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            strengthFill.className = 'strength-fill';
            strengthText.className = 'strength-text';

            switch (score) {
                case 0:
                case 1:
                    strengthFill.classList.add('strength-weak');
                    strengthText.classList.add('text-weak');
                    feedback = 'Very Weak';
                    break;
                case 2:
                    strengthFill.classList.add('strength-fair');
                    strengthText.classList.add('text-fair');
                    feedback = 'Fair';
                    break;
                case 3:
                case 4:
                    strengthFill.classList.add('strength-good');
                    strengthText.classList.add('text-good');
                    feedback = 'Good';
                    break;
                case 5:
                    strengthFill.classList.add('strength-strong');
                    strengthText.classList.add('text-strong');
                    feedback = 'Strong';
                    break;
            }

            strengthText.textContent = feedback;
            return score;
        }

        newPasswordInput.addEventListener('input', (e) => {
            const password = e.target.value;
            if (password.length > 0) {
                strengthIndicator.style.display = 'block';
                checkPasswordStrength(password);
            } else {
                strengthIndicator.style.display = 'none';
            }
        });

        // Form validation and submission
        savePasswordBtn.addEventListener('click', async () => {
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            // Reset validation
            passwordForm.classList.remove('was-validated');

            let isValid = true;

            // Validate current password
            if (!currentPassword) {
                document.getElementById('currentPassword').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('currentPassword').classList.remove('is-invalid');
                document.getElementById('currentPassword').classList.add('is-valid');
            }

            // Validate new password
            if (!newPassword || newPassword.length < 8) {
                document.getElementById('newPassword').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('newPassword').classList.remove('is-invalid');
                document.getElementById('newPassword').classList.add('is-valid');
            }

            // Validate password confirmation
            if (!confirmPassword || confirmPassword !== newPassword) {
                document.getElementById('confirmPassword').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('confirmPassword').classList.remove('is-invalid');
                document.getElementById('confirmPassword').classList.add('is-valid');
            }

            // Check password strength
            const strength = checkPasswordStrength(newPassword);
            if (strength < 3) {
                notify('Please choose a stronger password', 'error');
                isValid = false;
            }

            if (isValid) {
                // Show loading state
                const originalText = savePasswordBtn.innerHTML;
                savePasswordBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
                savePasswordBtn.disabled = true;

                try {
                    // Send AJAX request to update password
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'change_password',
                            current_password: currentPassword,
                            new_password: newPassword
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        passwordModal.hide();
                        notify('Password updated successfully!');

                        // Update the last password change text in the security info
                        setTimeout(() => {
                            location.reload(); // Refresh to show updated "last password change" info
                        }, 1500);
                    } else {
                        notify(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    notify('An error occurred. Please try again.', 'error');
                } finally {
                    // Reset button
                    savePasswordBtn.innerHTML = originalText;
                    savePasswordBtn.disabled = false;
                }
            }
        });

        // Clear validation on modal close
        document.getElementById('passwordModal').addEventListener('hidden.bs.modal', () => {
            passwordForm.reset();
            passwordForm.classList.remove('was-validated');
            document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                el.classList.remove('is-valid', 'is-invalid');
            });
            strengthIndicator.style.display = 'none';
        });
    </script>
</body>

</html>
</qodoArtifact>