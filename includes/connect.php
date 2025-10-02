<?php 
require_once 'config.php';

// Enhanced database connection with error handling
try {
    $connect = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($connect->connect_error) {
        throw new Exception("Connection failed: " . $connect->connect_error);
    }
    
    // Set charset to UTF-8
    $connect->set_charset("utf8");
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Function to safely execute queries
function executeQuery($query, $params = [], $types = '') {
    global $connect;
    
    if (empty($params)) {
        $result = $connect->query($query);
        if (!$result) {
            error_log("Query error: " . $connect->error . " | Query: " . $query);
            return false;
        }
        return $result;
    }
    
    $stmt = $connect->prepare($query);
    if (!$stmt) {
        error_log("Prepare error: " . $connect->error . " | Query: " . $query);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    if (!$result) {
        error_log("Execute error: " . $stmt->error . " | Query: " . $query);
        $stmt->close();
        return false;
    }
    
    $queryResult = $stmt->get_result();
    $stmt->close();
    
    return $queryResult !== false ? $queryResult : true;
}
?>