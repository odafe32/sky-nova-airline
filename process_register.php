<?php
session_start();

// Database connection to your 'airlines' database
$host = 'localhost';
$dbname = 'airlines';  // Your database name
$username = 'root';    // Your database username
$password = '';        // Your database password (usually empty for XAMPP)

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $errors = [];
    
    // Get the form data
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $userPassword = $_POST['password'] ?? '';
    
    // Simple validation
    if (empty($fullname)) {
        $errors[] = "Full name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (empty($userPassword)) {
        $errors[] = "Password is required.";
    } elseif (strlen($userPassword) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    // If no errors, try to save to database
    if (empty($errors)) {
        try {
            // Connect to database
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if email already exists
            $checkEmail = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $checkEmail->execute([$email]);
            
            if ($checkEmail->rowCount() > 0) {
                $errors[] = "An account with this email already exists.";
            } else {
                // Hash the password for security
                $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);
                
                // Insert new user into database - FIXED: Set created_by and updated_by to NULL
                $insertUser = $pdo->prepare("
                    INSERT INTO users (
                        full_name, 
                        email, 
                        password, 
                        avatar, 
                        membership, 
                        phone, 
                        date_of_birth, 
                        gender, 
                        nationality, 
                        address, 
                        emergency_contact, 
                        passport_number, 
                        created_at, 
                        updated_at, 
                        created_by, 
                        updated_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?)
                ");
                
                $result = $insertUser->execute([
                    $fullname,              // full_name
                    $email,                 // email
                    $hashedPassword,        // password (hashed)
                    null,                   // avatar (empty for now)
                    'basic',                // membership (default to basic)
                    null,                   // phone (empty for now)
                    null,                   // date_of_birth (empty for now)
                    null,                   // gender (empty for now)
                    null,                   // nationality (empty for now)
                    null,                   // address (empty for now)
                    null,                   // emergency_contact (empty for now)
                    null,                   // passport_number (empty for now)
                    null,                   // created_by - FIXED: Set to NULL instead of text
                    null                    // updated_by - FIXED: Set to NULL instead of text
                ]);
                
                if ($result) {
                    // Success! Redirect to login page
                    $_SESSION['register_success'] = "Account created successfully! You can now log in.";
                    header("Location: login.php");
                    exit();
                } else {
                    $errors[] = "Something went wrong. Please try again.";
                }
            }
            
        } catch (PDOException $e) {
            // SHOW THE ACTUAL ERROR (temporarily for debugging)
            $errors[] = "Database error: " . $e->getMessage();
            error_log("Registration Error: " . $e->getMessage());
        }
    }
    
    // If there are errors, go back to register page with errors
    if (!empty($errors)) {
        $_SESSION['register_error'] = implode('<br>', $errors);
        $_SESSION['form_data'] = $_POST; // Keep the form data so user doesn't have to retype
        header("Location: register.php");
        exit();
    }
    
} else {
    // If someone tries to access this file directly, send them to register page
    header("Location: register.php");
    exit();
}
?>