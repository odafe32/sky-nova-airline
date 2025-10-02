<?php
session_start();

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
    $_SESSION['login_error'] = "Database connection failed. Please try again later.";
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

// Get and sanitize input
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Basic validation
if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = "Please enter both email and password.";
    $_SESSION['login_email'] = $email;
    header("Location: login.php");
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['login_error'] = "Please enter a valid email address.";
    $_SESSION['login_email'] = $email;
    header("Location: login.php");
    exit();
}

try {
    $user = null;
    $userType = null;
    $redirectPath = null;

    // 1. First check ADMINS table (main admins)
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin) {
        // Check if password is hashed or plain text
        if (password_verify($password, $admin['password'])) {
            $user = $admin;
            $userType = 'admin';
            $redirectPath = 'Admin/dashboard.php';
        } elseif ($password === $admin['password']) {
            $user = $admin;
            $userType = 'admin';
            $redirectPath = 'Admin/dashboard.php';

            // Hash the password for future use
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
            $updateStmt->execute([$hashedPassword, $admin['admin_id']]);
        }
    }

    // 2. If not admin, check SUB_ADMINS table
    if (!$user) {
        $stmt = $pdo->prepare("SELECT * FROM sub_admins WHERE email = ?");
        $stmt->execute([$email]);
        $subAdmin = $stmt->fetch();

        if ($subAdmin) {
            // Check password first
            $passwordMatch = false;
            if (password_verify($password, $subAdmin['password'])) {
                $passwordMatch = true;
            } elseif ($password === $subAdmin['password']) {
                $passwordMatch = true;

                // Hash the password for future use
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE sub_admins SET password = ? WHERE sub_admin_id = ?");
                $updateStmt->execute([$hashedPassword, $subAdmin['sub_admin_id']]);
            }

            if ($passwordMatch) {
                // FIXED: Case-insensitive status check
                $status = strtolower(trim($subAdmin['status'] ?? ''));

                if ($status === 'active' || empty($status) || is_null($subAdmin['status'])) {
                    $user = $subAdmin;
                    $userType = 'sub_admin';
                    $redirectPath = 'Sub-admin/dashboard.php';
                } else {
                    $_SESSION['login_error'] = "Your account has been deactivated. Please contact the administrator.";
                    $_SESSION['login_email'] = $email;
                    header("Location: login.php");
                    exit();
                }
            } else {
                $_SESSION['login_error'] = "Invalid email or password.";
                $_SESSION['login_email'] = $email;
                header("Location: login.php");
                exit();
            }
        }
    }

    // 3. If not admin or sub_admin, check USERS table
    if (!$user) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $regularUser = $stmt->fetch();

        if ($regularUser) {
            // Check password
            $passwordMatch = false;
            if (password_verify($password, $regularUser['password'])) {
                $passwordMatch = true;
            } elseif ($password === $regularUser['password']) {
                $passwordMatch = true;

                // Hash the password for future use
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $updateStmt->execute([$hashedPassword, $regularUser['user_id']]);
            }

            if ($passwordMatch) {
                // Case-insensitive status check for users too
                $userStatus = strtolower(trim($regularUser['status'] ?? 'active'));
                if ($userStatus === 'active') {
                    $user = $regularUser;
                    $userType = 'user';
                    $redirectPath = 'User/dashboard.php';
                } else {
                    $_SESSION['login_error'] = "Your account has been deactivated. Please contact support.";
                    $_SESSION['login_email'] = $email;
                    header("Location: login.php");
                    exit();
                }
            }
        }
    }

    // 4. If no user found in any table
    if (!$user) {
        $_SESSION['login_error'] = "Invalid email or password.";
        $_SESSION['login_email'] = $email;
        header("Location: login.php");
        exit();
    }

    // 5. Check if redirect path file exists
    if (!file_exists($redirectPath)) {
        $_SESSION['login_error'] = "Dashboard file not found. Please contact support.";
        $_SESSION['login_email'] = $email;
        header("Location: login.php");
        exit();
    }

    // 6. Successful login - Set session variables
    $_SESSION['logged_in'] = true;
    $_SESSION['user_role'] = $userType;
    $_SESSION['user_email'] = $user['email'];

    // Set user ID and name based on user type
    switch ($userType) {
        case 'admin':
            $_SESSION['user_id'] = $user['admin_id'];
            $_SESSION['user_name'] = $user['full_name'];
            break;
        case 'sub_admin':
            $_SESSION['user_id'] = $user['sub_admin_id'];
            $_SESSION['user_name'] = $user['full_name'];
            break;
        case 'user':
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            break;
    }

    // Update last login time (optional)
    try {
        $currentTime = date('Y-m-d H:i:s');
        switch ($userType) {
            case 'admin':
                $stmt = $pdo->prepare("SHOW COLUMNS FROM admins LIKE 'updated_at'");
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $stmt = $pdo->prepare("UPDATE admins SET updated_at = ? WHERE admin_id = ?");
                    $stmt->execute([$currentTime, $user['admin_id']]);
                }
                break;
            case 'sub_admin':
                $stmt = $pdo->prepare("UPDATE sub_admins SET updated_at = ? WHERE sub_admin_id = ?");
                $stmt->execute([$currentTime, $user['sub_admin_id']]);
                break;
            case 'user':
                $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'updated_at'");
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $stmt = $pdo->prepare("UPDATE users SET updated_at = ? WHERE user_id = ?");
                    $stmt->execute([$currentTime, $user['user_id']]);
                }
                break;
        }
    } catch (PDOException $e) {
        // Don't fail login if update fails, just log it
        error_log("Failed to update last login time: " . $e->getMessage());
    }

    // Clear any previous error messages
    unset($_SESSION['login_error'], $_SESSION['login_email']);

    // Redirect to appropriate dashboard
    header("Location: " . $redirectPath);
    exit();
} catch (PDOException $e) {
    // Log the actual error for debugging
    error_log("Login error: " . $e->getMessage());

    // Show generic error to user
    $_SESSION['login_error'] = "An error occurred during login. Please try again.";
    $_SESSION['login_email'] = $email;
    header("Location: login.php");
    exit();
}
