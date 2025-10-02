<?php
session_start();
header('Content-Type: application/json');

// Database configuration
$host = 'localhost';
$dbname = 'airlines';
$username = 'root'; // Change this to your database username
$password = '';     // Change this to your database password

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please try again later.'
    ]);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Get and sanitize input data
$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validation array to collect errors
$errors = [];

// Validate full name
if (empty($fullname)) {
    $errors[] = 'Full name is required.';
} elseif (strlen($fullname) < 2) {
    $errors[] = 'Full name must be at least 2 characters long.';
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $fullname)) {
    $errors[] = 'Full name can only contain letters and spaces.';
}

// Validate email
if (empty($email)) {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

// Validate password
if (empty($password)) {
    $errors[] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
} else {
    // Check password strength (must have 3 out of 4 character types)
    $score = 0;
    if (preg_match('/[a-z]/', $password)) $score++; // lowercase
    if (preg_match('/[A-Z]/', $password)) $score++; // uppercase
    if (preg_match('/[0-9]/', $password)) $score++; // numbers
    if (preg_match('/[@$!%*?&]/', $password)) $score++; // special chars
    
    if ($score < 3) {
        $errors[] = 'Password must contain at least 3 of the following: lowercase letters, uppercase letters, numbers, special characters.';
    }
}

// If there are validation errors, return them
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errors)
    ]);
    exit;
}

try {
    // Check if email already exists
    $checkEmailStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkEmailStmt->execute([$email]);
    
    if ($checkEmailStmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'An account with this email address already exists. Please use a different email or try logging in.'
        ]);
        exit;
    }
    
    // Hash the password securely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare insert statement
    $insertStmt = $pdo->prepare("
        INSERT INTO users (
            full_name, 
            email, 
            password, 
            membership, 
            created_at, 
            updated_at
        ) VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    
    // Set default membership level
    $defaultMembership = 'Standard';
    
    // Execute the insert
    $result = $insertStmt->execute([
        $fullname,
        $email,
        $hashedPassword,
        $defaultMembership
    ]);
    
    if ($result) {
        // Get the newly created user ID
        $userId = $pdo->lastInsertId();
        
        // Log the successful registration (optional)
        error_log("New user registered: ID=$userId, Email=$email, Name=$fullname");
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully! Redirecting to login page...',
            'redirect' => 'login.php',
            'user_id' => $userId
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create account. Please try again.'
        ]);
    }
    
} catch (PDOException $e) {
    // Log the actual error for debugging (don't show to user)
    error_log("Registration error: " . $e->getMessage());
    
    // Check if it's a duplicate entry error
    if ($e->getCode() == 23000) {
        echo json_encode([
            'success' => false,
            'message' => 'An account with this email address already exists.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'A database error occurred. Please try again later.'
        ]);
    }
    
} catch (Exception $e) {
    // Log any other errors
    error_log("Unexpected registration error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.'
    ]);
}
?>