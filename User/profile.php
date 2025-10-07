<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'airlines';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $nationality = $_POST['nationality'];
        $address = trim($_POST['address']);
        $emergency_contact = trim($_POST['emergency_contact']);
        $passport_number = trim($_POST['passport_number']);

        // Handle avatar upload
        $avatar_path = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions) && $_FILES['avatar']['size'] <= 5 * 1024 * 1024) {
                $avatar_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
                $avatar_path = $upload_dir . $avatar_filename;

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
                    // Remove old avatar if exists
                    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $old_avatar = $stmt->fetchColumn();
                    if ($old_avatar && file_exists($old_avatar)) {
                        unlink($old_avatar);
                    }
                } else {
                    $avatar_path = null;
                }
            }
        }

        // Update user profile
        if ($avatar_path) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, phone = ?, date_of_birth = ?, gender = ?, 
                    nationality = ?, address = ?, emergency_contact = ?, passport_number = ?, 
                    avatar = ?, updated_at = NOW(), updated_by = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $full_name,
                $email,
                $phone,
                $date_of_birth,
                $gender,
                $nationality,
                $address,
                $emergency_contact,
                $passport_number,
                $avatar_path,
                $user_id,
                $user_id
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, phone = ?, date_of_birth = ?, gender = ?, 
                    nationality = ?, address = ?, emergency_contact = ?, passport_number = ?, 
                    updated_at = NOW(), updated_by = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $full_name,
                $email,
                $phone,
                $date_of_birth,
                $gender,
                $nationality,
                $address,
                $emergency_contact,
                $passport_number,
                $user_id,
                $user_id
            ]);
        }

        $success_message = "Profile updated successfully!";
    } catch (Exception $e) {
        $error_message = "Failed to update profile: " . $e->getMessage();
    }
}

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: ../login.php");
    exit();
}

// Get cart count for navbar
$stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart_count_result = $stmt->fetch(PDO::FETCH_ASSOC);
$cart_count = $cart_count_result['cart_count'];

// Get user statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN b.status = 'Confirmed' THEN 1 END) as total_bookings,
        COUNT(CASE WHEN b.status = 'Confirmed' AND f.flight_date >= CURDATE() THEN 1 END) as upcoming_flights,
        COUNT(CASE WHEN b.status = 'Confirmed' AND f.flight_date < CURDATE() THEN 1 END) as completed_flights
    FROM bookings b
    JOIN flights f ON b.flight_id = f.flight_id
    WHERE b.user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's first name and avatar initial
$full_name = $user['full_name'];
$first_name = explode(' ', $full_name)[0];
$avatar_initial = strtoupper(substr($full_name, 0, 1));
$user_avatar = $user['avatar'] ? $user['avatar'] : null;

// Format date of birth for display
$formatted_dob = $user['date_of_birth'] ? date('F j, Y', strtotime($user['date_of_birth'])) : 'Not provided';
$dob_input_value = $user['date_of_birth'] ? date('Y-m-d', strtotime($user['date_of_birth'])) : '';

// Determine membership level based on bookings
$membership_level = 'Bronze Member';
if ($stats['total_bookings'] >= 10) {
    $membership_level = 'Gold Member';
} elseif ($stats['total_bookings'] >= 5) {
    $membership_level = 'Silver Member';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Profile | Speed of Light Airlines</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Profile - Speed of Light Airlines" />
    <meta name="keywords" content="airline, booking, flights, dashboard, profile">
    <meta name="author" content="Speed of Light Airlines" />
    <link rel="icon" href="assets/images/airline-favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <!-- Feather Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8fafc;
            min-height: 100vh;
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(0, 83, 156, 0.06);
            border-radius: 50%;
            animation: float 18s ease-in-out infinite;
        }

        .particle:nth-child(1) {
            width: 60px;
            height: 60px;
            left: 5%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            width: 40px;
            height: 40px;
            left: 25%;
            animation-delay: 4s;
        }

        .particle:nth-child(3) {
            width: 80px;
            height: 80px;
            left: 45%;
            animation-delay: 8s;
        }

        .particle:nth-child(4) {
            width: 30px;
            height: 30px;
            left: 65%;
            animation-delay: 12s;
        }

        .particle:nth-child(5) {
            width: 50px;
            height: 50px;
            left: 85%;
            animation-delay: 16s;
        }

        .particle:nth-child(6) {
            width: 35px;
            height: 35px;
            left: 15%;
            animation-delay: 2s;
        }

        .particle:nth-child(7) {
            width: 70px;
            height: 70px;
            left: 75%;
            animation-delay: 10s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.6;
            }

            50% {
                transform: translateY(-250px) rotate(180deg);
                opacity: 0.2;
            }
        }

        .navbar {
            background: #38a169;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 83, 156, 0.15);
        }

        .navbar-brand {
            color: #fff !important;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 1.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .navbar-toggler {
            border: none;
            color: #fff;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        /* Enhanced Header User Section */
        .navbar-user-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-left: auto;
        }

        .cart-icon-container {
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .cart-icon-container:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .cart-icon {
            color: #fff;
            font-size: 1.3rem;
            transition: all 0.3s ease;
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: #fff;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            animation: pulse 2s infinite;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.4);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .user-avatar-nav {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #48bb78, #38a169);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            object-fit: cover;
        }

        .user-name {
            font-size: 1rem;
            font-weight: 600;
        }

        .sidebar {
            background: #38a169;
            min-height: 100vh;
            padding-top: 30px;
            z-index: 100;
            box-shadow: 2px 0 20px rgba(0, 51, 102, 0.1);
        }

        .sidebar .nav-link {
            color: #fff;
            font-weight: 500;
            margin-bottom: 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            padding: 12px 20px;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 83, 156, 0.3), transparent);
            transition: left 0.5s;
        }

        .sidebar .nav-link:hover::before {
            left: 100%;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: #38a169;
            color: #fff;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .main-content {
            padding: 40px 20px 80px 20px;
            position: relative;
            z-index: 10;
        }

        .profile-header {
            color: #38a169;
            text-align: center;
            margin-bottom: 50px;
            animation: fadeInDown 1s ease-out;
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.05) 0%, rgba(0, 51, 102, 0.05) 100%);
            padding: 40px 20px;
            border-radius: 24px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 83, 156, 0.1);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #38a169, #38a169);
        }

        .profile-header h1 {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #38a169, #38a169);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-header p {
            font-size: 1.2rem;
            opacity: 0.8;
            font-weight: 400;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 28px;
            box-shadow: 0 12px 40px rgba(0, 83, 156, 0.12);
            padding: 40px 30px;
            margin: 0 auto 40px auto;
            max-width: 900px;
            animation: fadeInUp 1s ease-out;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(0, 83, 156, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #38a169, #38a169, #48bb78);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .profile-card:hover::before {
            transform: scaleX(1);
        }

        .profile-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: 0 20px 60px rgba(0, 83, 156, 0.2);
            background: rgba(255, 255, 255, 1);
        }

        .profile-avatar-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #38a169;
            box-shadow: 0 8px 32px rgba(0, 83, 156, 0.2);
            background: #f6f9fc;
            transition: all 0.3s ease;
            animation: pulse 3s infinite;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 40px rgba(0, 83, 156, 0.3);
        }

        .avatar-upload-overlay {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #38a169, #38a169);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
        }

        .avatar-upload-overlay:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 83, 156, 0.4);
        }

        .profile-badge {
            background: linear-gradient(135deg, #e3fcec, #c8f7c5);
            color: #1b5e20;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 20px;
            padding: 8px 20px;
            margin-top: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(27, 94, 32, 0.2);
            animation: glow 3s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                box-shadow: 0 4px 15px rgba(27, 94, 32, 0.2);
            }

            to {
                box-shadow: 0 4px 25px rgba(27, 94, 32, 0.4), 0 0 30px rgba(27, 94, 32, 0.1);
            }
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: #38a169;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 83, 156, 0.1);
        }

        .profile-email {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .profile-stat-card {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.05) 0%, rgba(0, 51, 102, 0.05) 100%);
            border-radius: 20px;
            padding: 25px 20px;
            text-align: center;
            min-width: 140px;
            box-shadow: 0 6px 25px rgba(0, 83, 156, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 83, 156, 0.1);
            position: relative;
            overflow: hidden;
        }

        .profile-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 83, 156, 0.1), transparent);
            transition: left 0.5s;
        }

        .profile-stat-card:hover::before {
            left: 100%;
        }

        .profile-stat-card:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 12px 35px rgba(0, 83, 156, 0.15);
        }

        .profile-stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #38a169;
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0, 83, 156, 0.1);
        }

        .profile-stat-label {
            color: #666;
            font-size: 1rem;
            font-weight: 500;
        }

        .edit-btn {
            background: linear-gradient(135deg, #38a169 0%, #38a169 100%);
            color: #fff;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            padding: 14px 30px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 83, 156, 0.3);
            animation: pulse-glow 2s ease-in-out infinite alternate;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes pulse-glow {
            from {
                box-shadow: 0 6px 20px rgba(0, 83, 156, 0.3);
            }

            to {
                box-shadow: 0 6px 30px rgba(0, 83, 156, 0.6), 0 0 40px rgba(0, 83, 156, 0.2);
            }
        }

        .edit-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .edit-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .edit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 83, 156, 0.4);
            color: #fff;
        }

        .profile-info-section {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.03) 0%, rgba(0, 51, 102, 0.03) 100%);
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            border: 1px solid rgba(0, 83, 156, 0.1);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #38a169;
            margin-bottom: 25px;
            position: relative;
            padding-left: 20px;
        }

        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: linear-gradient(135deg, #38a169, #38a169);
            border-radius: 2px;
        }

        .profile-info-item {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 83, 156, 0.1);
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.05);
        }

        .profile-info-item:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 83, 156, 0.1);
        }

        .profile-info-label {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 600;
        }

        .profile-info-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #38a169;
        }

        .footer {
            background: #38a169;
            color: #fff;
            text-align: center;
            padding: 20px 0 12px 0;
            margin-top: 50px;
            letter-spacing: 1px;
        }

        /* Enhanced Modal Styling */
        .modal-content {
            border-radius: 24px;
            border: none;
            box-shadow: 0 25px 70px rgba(0, 83, 156, 0.25);
            backdrop-filter: blur(15px);
        }

        .modal-header {
            border-radius: 24px 24px 0 0;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            padding: 25px 30px;
        }

        .modal-body {
            padding: 35px;
        }

        .modal-footer {
            border-top: 2px solid rgba(0, 83, 156, 0.1);
            padding: 25px 35px;
        }

        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #38a169;
            box-shadow: 0 0 0 0.2rem rgba(0, 83, 156, 0.15);
            background: #fff;
            transform: translateY(-2px);
        }

        .save-btn {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            padding: 12px 24px;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
        }

        .save-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .save-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
            color: #fff;
        }

        .avatar-upload-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.03) 0%, rgba(0, 51, 102, 0.03) 100%);
            border-radius: 16px;
            border: 1px solid rgba(0, 83, 156, 0.1);
        }

        .modal-avatar {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #38a169;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .modal-avatar:hover {
            transform: scale(1.05);
        }

        .upload-btn {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 8px 16px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.3);
            color: #fff;
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Success Animation */
        .success-checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: #48bb78;
            stroke-miterlimit: 10;
            margin: 10% auto;
            box-shadow: inset 0px 0px 0px #48bb78;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .success-checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #48bb78;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .success-checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {

            0%,
            100% {
                transform: none;
            }

            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #48bb78;
            }
        }

        /* Alert Styles */
        .alert {
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .sidebar {
                min-height: auto;
                padding-top: 0;
            }

            .main-content {
                padding: 30px 15px 80px 15px;
            }

            .navbar-user-section {
                gap: 15px;
            }
        }

        @media (max-width: 767px) {
            .profile-header h1 {
                font-size: 2.2rem;
            }

            .profile-card {
                padding: 25px 20px;
            }

            .profile-stats {
                flex-direction: column;
                gap: 15px;
            }

            .profile-stat-card {
                min-width: auto;
            }

            .particles {
                display: none;
            }

            .navbar-user-section {
                flex-direction: column;
                gap: 10px;
            }

            .user-info {
                order: 2;
            }

            .cart-icon-container {
                order: 1;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 83, 156, 0.1);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(0, 83, 156, 0.3);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #38a169;
        }
    </style>
</head>

<body>
    <!-- Animated Background Particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- Enhanced Navbar with Cart Icon and User Info -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../pexels-sevenstormphotography-728824 (1).jpg" alt="Logo" style="width:38px; margin-right:10px; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                SKYNOVA
            </a>

            <!-- Enhanced User Section with Cart Icon -->
            <div class="navbar-user-section">
                <div class="cart-icon-container" onclick="window.location.href='cart.php'">
                    <svg class="cart-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="m1 1 4 4 2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <?php if ($cart_count > 0): ?>
                        <div class="cart-badge"><?php echo $cart_count; ?></div>
                    <?php endif; ?>
                </div>

                <div class="user-info">
                    <?php if ($user_avatar): ?>
                        <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar" class="user-avatar-nav">
                    <?php else: ?>
                        <div class="user-avatar-nav"><?php echo $avatar_initial; ?></div>
                    <?php endif; ?>
                    <div class="user-name"><?php echo htmlspecialchars($first_name); ?></div>
                </div>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span><i data-feather="menu"></i></span>
            </button>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-lg-2 col-md-3 d-lg-block sidebar collapse">
                <div class="position-sticky">
                    <ul class="nav flex-column mt-4">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i data-feather="home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="book-flight.php">
                                <i data-feather="send"></i> Book a Flight
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my-bookings.php">
                                <i data-feather="calendar"></i> My Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="profile.php">
                                <i data-feather="user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i data-feather="log-out"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-lg-10 col-md-9 ms-sm-auto main-content">
                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success animate__animated animate__fadeInDown">
                        <i data-feather="check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger animate__animated animate__fadeInDown">
                        <i data-feather="alert-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="profile-header animate__animated animate__fadeInDown">
                    <h1>My Profile</h1>
                    <p>Manage your account and personal information with ease.</p>
                </div>

                <div class="profile-card animate__animated animate__fadeInUp text-center">
                    <div class="profile-avatar-container">
                        <?php if ($user_avatar): ?>
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Profile Avatar" class="profile-avatar" id="profileAvatarDisplay">
                        <?php else: ?>
                            <div class="profile-avatar d-flex align-items-center justify-content-center" style="font-size: 3rem; font-weight: 700; color: #38a169;" id="profileAvatarDisplay">
                                <?php echo $avatar_initial; ?>
                            </div>
                        <?php endif; ?>
                        <div class="avatar-upload-overlay" onclick="document.getElementById('avatarUpload').click()">
                            <i data-feather="camera"></i>
                        </div>
                        <input type="file" id="avatarUpload" accept="image/*" style="display: none;">
                    </div>

                    <div class="profile-badge">
                        <i data-feather="star"></i> <?php echo $membership_level; ?>
                    </div>

                    <h4 class="profile-name" id="displayName"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <div class="profile-email" id="displayEmail"><?php echo htmlspecialchars($user['email']); ?></div>

                    <!-- Profile Stats -->
                    <div class="profile-stats">
                        <div class="profile-stat-card">
                            <div class="profile-stat-value"><?php echo $stats['total_bookings']; ?></div>
                            <div class="profile-stat-label">Total Bookings</div>
                        </div>
                        <div class="profile-stat-card">
                            <div class="profile-stat-value"><?php echo $stats['upcoming_flights']; ?></div>
                            <div class="profile-stat-label">Upcoming Flights</div>
                        </div>
                        <div class="profile-stat-card">
                            <div class="profile-stat-value"><?php echo $stats['completed_flights']; ?></div>
                            <div class="profile-stat-label">Completed Flights</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i data-feather="edit-2"></i> Edit Profile
                        </button>
                    </div>

                    <div class="profile-info-section">
                        <div class="section-title">
                            <i data-feather="info"></i> Personal Information
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Phone Number</div>
                                    <div class="profile-info-value" id="displayPhone"><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Date of Birth</div>
                                    <div class="profile-info-value" id="displayDob"><?php echo $formatted_dob; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Gender</div>
                                    <div class="profile-info-value" id="displayGender"><?php echo htmlspecialchars($user['gender'] ?: 'Not specified'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Nationality</div>
                                    <div class="profile-info-value" id="displayNationality"><?php echo htmlspecialchars($user['nationality'] ?: 'Not specified'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Address</div>
                                    <div class="profile-info-value" id="displayAddress"><?php echo htmlspecialchars($user['address'] ?: 'Not provided'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Emergency Contact</div>
                                    <div class="profile-info-value" id="displayEmergency"><?php echo htmlspecialchars($user['emergency_contact'] ?: 'Not provided'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Passport Number</div>
                                    <div class="profile-info-value" id="displayPassport"><?php echo htmlspecialchars($user['passport_number'] ?: 'Not provided'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <footer class="footer">
        &copy; <span id="year"></span> SKYNOVA Airlines. All Rights Reserved.  
    </footer>

    <!-- Enhanced Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="editProfileForm" method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editProfileModalLabel">
                            <i data-feather="edit"></i> Edit Profile
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Avatar Upload Section -->
                        <div class="avatar-upload-section">
                            <?php if ($user_avatar): ?>
                                <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Profile Avatar" class="modal-avatar" id="modalAvatarPreview">
                            <?php else: ?>
                                <div class="modal-avatar d-flex align-items-center justify-content-center" style="font-size: 2rem; font-weight: 700; color: #38a169;" id="modalAvatarPreview">
                                    <?php echo $avatar_initial; ?>
                                </div>
                            <?php endif; ?>
                            <br>
                            <button type="button" class="upload-btn" onclick="document.getElementById('modalAvatarUpload').click()">
                                <i data-feather="upload"></i> Change Photo
                            </button>
                            <input type="file" name="avatar" id="modalAvatarUpload" accept="image/*" style="display: none;">
                            <div class="mt-2 text-muted small">
                                <i data-feather="info"></i> Supported formats: JPG, PNG, GIF (Max 5MB)
                            </div>
                        </div>

                        <input type="hidden" name="update_profile" value="1">

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" id="editFullName" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="form-control" name="email" id="editEmail" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" name="phone" id="editPhone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth" id="editDob" value="<?php echo $dob_input_value; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender" id="editGender">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo ($user['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($user['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($user['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    <option value="Prefer not to say" <?php echo ($user['gender'] === 'Prefer not to say') ? 'selected' : ''; ?>>Prefer not to say</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nationality</label>
                                <select class="form-select" name="nationality" id="editNationality">
                                    <option value="">Select Nationality</option>
                                    <option value="Nigerian" <?php echo ($user['nationality'] === 'Nigerian') ? 'selected' : ''; ?>>Nigerian</option>
                                    <option value="American" <?php echo ($user['nationality'] === 'American') ? 'selected' : ''; ?>>American</option>
                                    <option value="British" <?php echo ($user['nationality'] === 'British') ? 'selected' : ''; ?>>British</option>
                                    <option value="Canadian" <?php echo ($user['nationality'] === 'Canadian') ? 'selected' : ''; ?>>Canadian</option>
                                    <option value="French" <?php echo ($user['nationality'] === 'French') ? 'selected' : ''; ?>>French</option>
                                    <option value="German" <?php echo ($user['nationality'] === 'German') ? 'selected' : ''; ?>>German</option>
                                    <option value="Other" <?php echo ($user['nationality'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" id="editAddress" rows="2"><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact</label>
                                <input type="text" class="form-control" name="emergency_contact" id="editEmergency" value="<?php echo htmlspecialchars($user['emergency_contact']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Passport Number</label>
                                <input type="text" class="form-control" name="passport_number" id="editPassport" value="<?php echo htmlspecialchars($user['passport_number']); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i data-feather="x"></i> Cancel
                        </button>
                        <button type="submit" class="save-btn">
                            <i data-feather="save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-body py-5">
                    <svg class="success-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="success-checkmark__circle" cx="26" cy="26" r="25" fill="none" />
                        <path class="success-checkmark__check" fill="none" d="m14.1 27.2l7.1 7.2 16.7-16.8" />
                    </svg>

                    <h4 class="mb-3 fw-bold text-success">Profile Updated Successfully!</h4>
                    <p class="text-muted">Your profile information has been saved and updated.</p>

                    <button type="button" class="btn btn-success mt-3" onclick="window.location.reload()">
                        <i data-feather="check"></i> Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();
        document.getElementById('year').textContent = new Date().getFullYear();

        // Sidebar toggler for mobile
        document.querySelectorAll('.navbar-toggler').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var sidebar = document.getElementById('sidebarMenu');
                if (sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                } else {
                    sidebar.classList.add('show');
                }
            });
        });

        // Close sidebar on nav-link click (mobile)
        document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                var sidebar = document.getElementById('sidebarMenu');
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('show');
                }
            });
        });

        // Cart icon click animation
        document.querySelector('.cart-icon-container').addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });

        // Avatar upload functionality
        document.getElementById('avatarUpload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatarDisplay = document.getElementById('profileAvatarDisplay');
                    if (avatarDisplay.tagName === 'IMG') {
                        avatarDisplay.src = e.target.result;
                    } else {
                        // Replace div with img
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.alt = 'Profile Avatar';
                        newImg.className = 'profile-avatar';
                        newImg.id = 'profileAvatarDisplay';
                        avatarDisplay.parentNode.replaceChild(newImg, avatarDisplay);
                    }

                    const modalPreview = document.getElementById('modalAvatarPreview');
                    if (modalPreview.tagName === 'IMG') {
                        modalPreview.src = e.target.result;
                    } else {
                        // Replace div with img
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.alt = 'Profile Avatar';
                        newImg.className = 'modal-avatar';
                        newImg.id = 'modalAvatarPreview';
                        modalPreview.parentNode.replaceChild(newImg, modalPreview);
                    }
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('modalAvatarUpload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    return;
                }

                // Validate file type
                if (!file.type.match('image.*')) {
                    alert('Please select a valid image file');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const modalPreview = document.getElementById('modalAvatarPreview');
                    if (modalPreview.tagName === 'IMG') {
                        modalPreview.src = e.target.result;
                    } else {
                        // Replace div with img
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.alt = 'Profile Avatar';
                        newImg.className = 'modal-avatar';
                        newImg.id = 'modalAvatarPreview';
                        modalPreview.parentNode.replaceChild(newImg, modalPreview);
                    }

                    const avatarDisplay = document.getElementById('profileAvatarDisplay');
                    if (avatarDisplay.tagName === 'IMG') {
                        avatarDisplay.src = e.target.result;
                    } else {
                        // Replace div with img
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.alt = 'Profile Avatar';
                        newImg.className = 'profile-avatar';
                        newImg.id = 'profileAvatarDisplay';
                        avatarDisplay.parentNode.replaceChild(newImg, avatarDisplay);
                    }
                };
                reader.readAsDataURL(file);
            }
        });

        // Enhanced form submission with loading and success animation
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.save-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="loading-spinner"></span> Saving...';
            submitBtn.disabled = true;
        });

        // Show success modal if profile was updated
        <?php if (isset($success_message)): ?>
            setTimeout(() => {
                var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
            }, 1000);
        <?php endif; ?>

        // Enhanced form interactions
        document.querySelectorAll('.form-control, .form-select').forEach(function(input) {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.3s ease';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Auto-update birth date max to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('editDob').setAttribute('max', today);

        // Phone number formatting
        document.getElementById('editPhone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('234')) {
                value = '+' + value;
            } else if (value.startsWith('0')) {
                value = '+234 ' + value.substring(1);
            }
            e.target.value = value;
        });

        // Passport number formatting
        document.getElementById('editPassport').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            });
        }, 5000);

        console.log('✅ Profile page loaded successfully');
        console.log('👤 User:', '<?php echo htmlspecialchars($user['full_name']); ?>');
        console.log('📊 Total bookings:', <?php echo $stats['total_bookings']; ?>);
    </script>
</body>

</html>
</qodoArtifact>