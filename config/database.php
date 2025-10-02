<?php
// Database configuration
$host = 'localhost';        // Your database host
$dbname = 'airlines'; // Your database name - CHANGE THIS TO YOUR ACTUAL DATABASE NAME
$username = 'root';         // Your database username
$db_password = '';          // Your database password

// PDO options for better security and error handling
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

// Create DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

// Test connection function (optional - for debugging)
function testDatabaseConnection() {
    global $dsn, $username, $db_password, $options;
    
    try {
        $pdo = new PDO($dsn, $username, $db_password, $options);
        return true;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        return false;
    }
}

// Function to get database connection
function getDatabaseConnection() {
    global $dsn, $username, $db_password, $options;
    
    try {
        $pdo = new PDO($dsn, $username, $db_password, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}
?>