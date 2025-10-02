<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // If not logged in, redirect to login page
    header("Location: ../login.php");
    exit();
} else {
    // User is logged in, so log them out

    // Optional: Log the logout activity (you can add this to a logs table later)
    $user_name = $_SESSION['user_name'] ?? 'Unknown User';
    $logout_time = date('Y-m-d H:i:s');

    // Clear all session variables
    session_unset();

    // Destroy the session
    session_destroy();

    // Optional: Set a logout success message
    session_start(); // Start new session for the message
    $_SESSION['logout_success'] = "You have been successfully logged out. Thank you for using SOLA!";

    // Redirect to login page
    header("Location: ../login.php");
    exit();
}
