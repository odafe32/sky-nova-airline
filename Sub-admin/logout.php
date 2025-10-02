<?php
session_start();

// Security headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in and is a sub admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // If not logged in, redirect to login page
    header("Location: ../login.php");
    exit();
}

// Verify sub admin role (security check)
$user_role = $_SESSION['user_role'] ?? '';
if ($user_role !== 'sub_admin') {
    // If not sub_admin, redirect to appropriate login
    header("Location: ../login.php");
    exit();
}

// Get sub admin information before clearing session (for logging purposes)
$sub_admin_id = $_SESSION['user_id'] ?? null;
$sub_admin_name = $_SESSION['user_name'] ?? 'Unknown Sub Admin';
$sub_admin_email = $_SESSION['user_email'] ?? 'Unknown Email';
$logout_time = date('Y-m-d H:i:s');

// Optional: Log sub admin logout activity to database
try {
    // Database connection (only if you want to log logout activities)
    $host = 'localhost';
    $dbname = 'airlines';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Create sub_admin_activity_log table if it doesn't exist (optional)
    
    
    // Log the logout activity
    $stmt = $pdo->prepare("
        INSERT INTO sub_admin_activity_log 
        (sub_admin_id, sub_admin_name, sub_admin_email, activity_type, activity_description, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, 'logout', ?, ?, ?, ?)
    ");
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $description = "Sub admin logged out successfully from sub admin panel";
    
    $stmt->execute([
        $sub_admin_id,
        $sub_admin_name,
        $sub_admin_email,
        $description,
        $ip_address,
        $user_agent,
        $logout_time
    ]);
    
} catch (PDOException $e) {
    // Don't fail logout if logging fails, just continue
    error_log("Sub admin logout logging failed: " . $e->getMessage());
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a new clean session for the success message
session_start();

// Set sub admin-specific logout success message
$_SESSION['logout_success'] = "You have been successfully logged out from the Sub Admin panel. Thank you for using Speed of Light Airlines Sub Admin System!";

// Redirect to main login page (go up one level from Sub-admin folder)
header("Location: ../login.php");
exit();
?>