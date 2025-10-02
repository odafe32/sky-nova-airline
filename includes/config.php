<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'airlines');

// Email Configuration (using PHPMailer or mail() function)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'iamstarkeys@gmail.com'); // Replace with your email
define('SMTP_PASSWORD', 'dmxymrtrpsojjmij');     // Replace with your app password
define('FROM_EMAIL', 'iamstarkeys@gmail.com');
define('FROM_NAME', 'Speed of Light Airlines');

// Enable SMTP for real email sending (set to false for development mode)
define('USE_SMTP', false); // Change to true to enable real email sending

// Site Configuration
define('SITE_URL', 'http://localhost/airlines/');
define('SITE_NAME', 'Speed of Light Airlines');

// Security Configuration
define('ENCRYPTION_KEY', 'SOLA_2025_SECURE_KEY_' . md5('airlines_system'));

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
session_start();

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>