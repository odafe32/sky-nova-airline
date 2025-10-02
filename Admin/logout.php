<?php
session_start();

// Security headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Verify admin role - FIXED: Check what's actually stored in session
$user_role = $_SESSION['user_role'] ?? '';
if (!in_array($user_role, ['admin', 'sub_admin'])) {
    // If not admin or sub_admin, redirect to login
    header("Location: ../login.php");
    exit();
}

// Get admin information before clearing session
$admin_id = $_SESSION['user_id'] ?? null;
$admin_name = $_SESSION['user_name'] ?? 'Unknown Admin';
$admin_email = $_SESSION['user_email'] ?? 'Unknown Email';
$admin_role = $_SESSION['user_role'] ?? 'Unknown Role';

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

// Set admin-specific logout success message
$_SESSION['logout_success'] = "You have been successfully logged out from the admin panel.";

// Redirect to login page
header("Location: ../login.php");
exit();
