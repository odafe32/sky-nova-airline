<?php
session_start();

// Authentication Check - Redirect if not logged in or not a user
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

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
    die("Database connection failed: " . $e->getMessage());
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get user's first name for display
$first_name = explode(' ', $user_name)[0];
$user_initial = strtoupper(substr($first_name, 0, 1));

// Get total booked flights count for this user
$total_flights = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $total_flights = $result['total'] ?? 0;
} catch (PDOException $e) {
    $total_flights = 0;
}

// Get total available flights added by admin
// Get total available flights (current/future flights only)
$available_flights = 0;
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM flights 
        WHERE flight_date >= CURDATE()
    ");
    $result = $stmt->fetch();
    $available_flights = $result['total'] ?? 0;
} catch (PDOException $e) {
    $available_flights = 0;
}

// Get cart items count
$cart_count = 0;
try {
    // Check if cart table exists, if not create it
    $stmt = $pdo->query("SHOW TABLES LIKE 'cart'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE cart (
                cart_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                flight_id INT NOT NULL,
                passengers INT DEFAULT 1,
                class ENUM('economy', 'business', 'first_class') DEFAULT 'economy',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                FOREIGN KEY (flight_id) REFERENCES flights(flight_id) ON DELETE CASCADE
            )
        ");
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) as cart_items FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $cart_count = $result['cart_items'] ?? 0;
} catch (PDOException $e) {
    $cart_count = 0;
}

// Get user's recent bookings
$recent_bookings = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.booking_ref,
            b.status,
            b.total_amount,
            b.class,
            b.passengers,
            b.created_at,
            f.flight_no,
            f.airline,
            f.origin,
            f.destination,
            f.flight_date,
            f.departure_time
        FROM bookings b
        JOIN flights f ON b.flight_id = f.flight_id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $recent_bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_bookings = [];
}

// Get upcoming flights for this user
$upcoming_user_flights = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.booking_ref,
            b.class,
            b.passengers,
            f.flight_no,
            f.airline,
            f.origin,
            f.destination,
            f.flight_date,
            f.departure_time
        FROM bookings b
        JOIN flights f ON b.flight_id = f.flight_id
        WHERE b.user_id = ? AND f.flight_date >= CURDATE() AND b.status = 'confirmed'
        ORDER BY f.flight_date ASC
        LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $upcoming_user_flights = $stmt->fetchAll();
} catch (PDOException $e) {
    $upcoming_user_flights = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>SkyNova Airlines | Dashboard</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="SkyNova Airlines Premium Dashboard" />
    <meta name="keywords" content="airline, dashboard, flights, SKYNOVA, premium">
    <meta name="author" content="SkyNova Airlines" />
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

        /* Enhanced Animated Background Particles */
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
            background: rgba(16, 185, 129, 0.08);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }

        .particle:nth-child(1) {
            width: 80px;
            height: 80px;
            left: 8%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            width: 60px;
            height: 60px;
            left: 18%;
            animation-delay: 1.5s;
        }

        .particle:nth-child(3) {
            width: 100px;
            height: 100px;
            left: 32%;
            animation-delay: 3s;
        }

        .particle:nth-child(4) {
            width: 40px;
            height: 40px;
            left: 48%;
            animation-delay: 4.5s;
        }

        .particle:nth-child(5) {
            width: 70px;
            height: 70px;
            left: 68%;
            animation-delay: 6s;
        }

        .particle:nth-child(6) {
            width: 90px;
            height: 90px;
            left: 82%;
            animation-delay: 7.5s;
        }

        .particle:nth-child(7) {
            width: 50px;
            height: 50px;
            left: 25%;
            animation-delay: 2s;
        }

        .particle:nth-child(8) {
            width: 65px;
            height: 65px;
            left: 75%;
            animation-delay: 5s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.6;
            }

            50% {
                transform: translateY(-120px) rotate(180deg);
                opacity: 0.2;
            }
        }

        .navbar {
            background: #10b981;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(16, 185, 129, 0.15);
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
            background: linear-gradient(135deg, #10b981, #059669);
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
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
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

        .user-avatar {
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
        }

        .user-name {
            font-size: 1rem;
            font-weight: 600;
        }

        .sidebar {
            background: #059669;
            min-height: 100vh;
            padding-top: 30px;
            z-index: 100;
            box-shadow: 2px 0 20px rgba(5, 150, 105, 0.1);
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
            background: linear-gradient(90deg, transparent, rgba(16, 185, 129, 0.3), transparent);
            transition: left 0.5s;
        }

        .sidebar .nav-link:hover::before {
            left: 100%;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: #10b981;
            color: #fff;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .main-content {
            padding: 40px 20px 80px 20px;
            position: relative;
            z-index: 10;
        }


        /* Enhanced Welcome Section Styles - ADD THESE TO YOUR EXISTING CSS */

        /* Update the existing welcome-section with these enhanced styles */
        .welcome-section {
            position: relative;
            background: linear-gradient(135deg,
                    rgba(16, 185, 129, 0.08) 0%,
                    rgba(5, 150, 105, 0.12) 25%,
                    rgba(52, 211, 153, 0.08) 50%,
                    rgba(16, 185, 129, 0.10) 75%,
                    rgba(5, 150, 105, 0.08) 100%);
            padding: 60px 40px;
            border-radius: 32px;
            backdrop-filter: blur(20px);
            border: 2px solid rgba(16, 185, 129, 0.15);
            overflow: hidden;
            box-shadow:
                0 20px 60px rgba(16, 185, 129, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            color: #10b981;
            text-align: center;
            margin-bottom: 50px;
            animation: fadeInDown 1s ease-out;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg,
                    #10b981 0%,
                    #34d399 25%,
                    #6ee7b7 50%,
                    #22c55e 75%,
                    #10b981 100%);
            background-size: 200% 100%;
            animation: gradientFlow 4s ease-in-out infinite;
        }

        @keyframes gradientFlow {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        /* Floating Background Elements */
        .welcome-bg-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }

        .floating-plane,
        .floating-cloud,
        .floating-star,
        .floating-globe {
            position: absolute;
            font-size: 2rem;
            opacity: 0.1;
            animation: floatAround 15s ease-in-out infinite;
        }

        .floating-plane {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
            animation-duration: 12s;
        }

        .floating-cloud {
            top: 15%;
            right: 15%;
            animation-delay: 3s;
            animation-duration: 18s;
        }

        .floating-star {
            bottom: 25%;
            left: 20%;
            animation-delay: 6s;
            animation-duration: 14s;
        }

        .floating-globe {
            bottom: 20%;
            right: 10%;
            animation-delay: 9s;
            animation-duration: 16s;
        }

        @keyframes floatAround {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg) scale(1);
            }

            25% {
                transform: translate(30px, -20px) rotate(90deg) scale(1.1);
            }

            50% {
                transform: translate(-20px, -40px) rotate(180deg) scale(0.9);
            }

            75% {
                transform: translate(-30px, 20px) rotate(270deg) scale(1.05);
            }
        }

        /* Enhanced Sliding Header */
        .sliding-header-container {
            position: relative;
            height: 140px;
            display: flex;
            align-items: center;
            margin-bottom: 35px;
            background: linear-gradient(135deg,
                    rgba(16, 185, 129, 0.05) 0%,
                    rgba(52, 211, 153, 0.05) 100%);
            border-radius: 25px;
            border: 2px solid rgba(16, 185, 129, 0.12);
            overflow: hidden;
            box-shadow:
                0 10px 30px rgba(16, 185, 129, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .sliding-text-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .sliding-text {
            font-size: 2.8rem;
            font-weight: 700;
            letter-spacing: 3px;
            white-space: nowrap;
            background: linear-gradient(45deg,
                    #10b981 0%,
                    #34d399 25%,
                    #6ee7b7 50%,
                    #22c55e 75%,
                    #10b981 100%);
            background-size: 400% 400%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation:
                slideText 25s linear infinite,
                gradientShift 3s ease-in-out infinite,
                textGlow 2s ease-in-out infinite alternate;
            text-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
            position: relative;
            z-index: 2;
        }

        .sliding-text-glow {
            position: absolute;
            top: 50%;
            left: 0;
            width: 200px;
            height: 60px;
            background: linear-gradient(90deg,
                    transparent 0%,
                    rgba(16, 185, 129, 0.3) 50%,
                    transparent 100%);
            transform: translateY(-50%);
            animation: glowMove 25s linear infinite;
            border-radius: 50px;
            filter: blur(20px);
        }

        @keyframes slideText {
            0% {
                transform: translateX(100%);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        @keyframes glowMove {
            0% {
                left: -200px;
            }

            100% {
                left: calc(100% + 200px);
            }
        }

        @keyframes textGlow {
            0% {
                filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.3));
            }

            100% {
                filter: drop-shadow(0 0 20px rgba(16, 185, 129, 0.6));
            }
        }

        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        /* Animated Border Lines */
        .animated-border {
            position: absolute;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg,
                    transparent 0%,
                    #38a169 50%,
                    transparent 100%);
            animation: borderPulse 3s ease-in-out infinite;
        }

        .top-border {
            top: 0;
            animation-delay: 0s;
        }

        .bottom-border {
            bottom: 0;
            animation-delay: 1.5s;
        }

        @keyframes borderPulse {

            0%,
            100% {
                opacity: 0.3;
                transform: scaleX(0.5);
            }

            50% {
                opacity: 1;
                transform: scaleX(1);
            }
        }

        /* Enhanced Description */
        .description-container {
            margin-bottom: 40px;
            position: relative;
        }

        .main-description {
            font-size: 1.5rem;
            line-height: 1.8;
            color: #38a169;
            font-weight: 500;
            text-align: center;
            margin: 0;
        }

        .typewriter-text {
            display: inline-block;
            border-right: 3px solid #38a169;
            animation: typewriter 4s steps(60) 1s forwards, blink 1s infinite;
            white-space: nowrap;
            overflow: hidden;
            width: 0;
        }

        @keyframes typewriter {
            to {
                width: 100%;
            }
        }

        @keyframes blink {

            0%,
            50% {
                border-color: #38a169;
            }

            51%,
            100% {
                border-color: transparent;
            }
        }

        .sub-description {
            color: rgba(0, 83, 156, 0.8);
            font-size: 1.2rem;
            font-weight: 400;
            margin-top: 10px;
            display: inline-block;
        }

        /* Enhanced Interactive Badge */
        .badge-container {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        .enhanced-badge {
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px 35px;
            background: linear-gradient(135deg, #38a169 0%, #38a169 100%);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            box-shadow:
                0 10px 30px rgba(0, 83, 156, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .enhanced-badge:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow:
                0 20px 50px rgba(0, 83, 156, 0.4),
                0 0 50px rgba(0, 83, 156, 0.2);
        }

        .badge-icon {
            font-size: 2rem;
            animation: iconFloat 3s ease-in-out infinite;
        }

        @keyframes iconFloat {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-5px) rotate(10deg);
            }
        }

        .badge-text {
            color: white;
            text-align: left;
        }

        .badge-main {
            display: block;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .badge-sub {
            display: block;
            font-size: 0.9rem;
            opacity: 0.8;
            font-weight: 400;
        }

        .badge-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .enhanced-badge:hover .badge-glow {
            opacity: 1;
            animation: glowRotate 2s linear infinite;
        }

        @keyframes glowRotate {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .badge-ripple {
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

        .enhanced-badge:active .badge-ripple {
            width: 300px;
            height: 300px;
        }

        /* Welcome Stats Counter */
        .welcome-stats {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            margin-top: 30px;
            padding: 25px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .welcome-stats .stat-item {
            text-align: center;
            color: #38a169;
        }

        .welcome-stats .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 5px;
            background: linear-gradient(45deg, #38a169, #48bb78);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .welcome-stats .stat-label {
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-divider {
            font-size: 2rem;
            color: rgba(0, 83, 156, 0.3);
            font-weight: 300;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .welcome-section {
                padding: 40px 20px;
            }

            .sliding-header-container {
                height: 100px;
            }

            .sliding-text {
                font-size: 2rem;
                letter-spacing: 2px;
            }

            .main-description {
                font-size: 1.2rem;
            }

            .enhanced-badge {
                padding: 15px 25px;
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .welcome-stats {
                flex-direction: column;
                gap: 20px;
            }

            .stat-divider {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .sliding-text {
                font-size: 1.5rem;
                letter-spacing: 1px;
            }

            .sliding-header-container {
                height: 80px;
            }

            .main-description {
                font-size: 1rem;
            }

            .floating-plane,
            .floating-cloud,
            .floating-star,
            .floating-globe {
                font-size: 1.5rem;
            }
        }



        .pulse-badge {
            display: inline-block;
            animation: pulse-glow 2s ease-in-out infinite alternate;
            background: linear-gradient(135deg, #38a169, #38a169) !important;
            border: none !important;
            font-size: 1.1rem;
            padding: 12px 24px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
        }

        @keyframes pulse-glow {
            from {
                box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
            }

            to {
                box-shadow: 0 4px 30px rgba(0, 83, 156, 0.6), 0 0 40px rgba(0, 83, 156, 0.2);
            }
        }

        .dashboard-cards {
            margin-top: 50px;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 28px;
            box-shadow: 0 10px 40px rgba(0, 83, 156, 0.12);
            padding: 45px 30px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            animation: fadeInUp 1s ease-out;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(0, 83, 156, 0.08);
            height: 100%;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #38a169, #38a169);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .dashboard-card:hover::before {
            transform: scaleX(1);
        }

        .dashboard-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.02) 0%, rgba(0, 51, 102, 0.02) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .dashboard-card:hover::after {
            opacity: 1;
        }

        .dashboard-card:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 25px 70px rgba(0, 83, 156, 0.25);
            background: rgba(255, 255, 255, 1);
        }

        .dashboard-card .card-icon {
            font-size: 4rem;
            margin-bottom: 25px;
            animation: bounceIn 1.2s ease-out;
            transition: all 0.3s ease;
            position: relative;
            z-index: 10;
        }

        .dashboard-card:hover .card-icon {
            transform: scale(1.15) rotate(8deg);
            filter: drop-shadow(0 8px 16px rgba(0, 83, 156, 0.2));
        }

        .card-icon.primary {
            color: #38a169;
        }

        .card-icon.success {
            color: #48bb78;
        }

        .card-icon.info {
            color: #4299e1;
        }

        .card-icon.warning {
            color: #ed8936;
        }

        .card-icon.danger {
            color: #f56565;
        }

        .card-icon.purple {
            color: #9f7aea;
        }

        .dashboard-card .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #2d3748;
            position: relative;
            z-index: 10;
        }

        .dashboard-card .card-desc {
            color: #718096;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.7;
            position: relative;
            z-index: 10;
        }

        .dashboard-card .btn {
            border-radius: 16px;
            font-weight: 600;
            padding: 14px 32px;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
            z-index: 10;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .dashboard-card .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .dashboard-card .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #38a169 0%, #38a169 100%);
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }

        .btn-info {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
        }

        .btn-danger {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        }

        .btn-purple {
            background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%);
        }

        /* Quick Stats Section */
        .quick-stats {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 8px 32px rgba(0, 83, 156, 0.1);
            border: 1px solid rgba(0, 83, 156, 0.1);
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.05) 0%, rgba(0, 51, 102, 0.05) 100%);
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 83, 156, 0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #38a169;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
            font-weight: 500;
        }

        /* Activity Cards */
        .activity-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 8px 25px rgba(0, 83, 156, 0.1);
            border: 1px solid rgba(0, 83, 156, 0.08);
            transition: all 0.3s ease;
        }

        .activity-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 83, 156, 0.15);
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .flight-info {
            font-weight: 600;
            color: #38a169;
            font-size: 1.1rem;
        }

        .flight-route {
            color: #666;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-confirmed {
            background: rgba(72, 187, 120, 0.2);
            color: #48bb78;
        }

        .status-pending {
            background: rgba(237, 137, 54, 0.2);
            color: #ed8936;
        }

        .footer {
            background: #48bb78;
            color: #fff;
            text-align: center;
            padding: 20px 0 12px 0;
            margin-top: 50px;
            letter-spacing: 1px;
        }

        /* Staggered Animation */
        .dashboard-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .dashboard-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .dashboard-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .dashboard-card:nth-child(4) {
            animation-delay: 0.4s;
        }

        .dashboard-card:nth-child(5) {
            animation-delay: 0.5s;
        }

        .dashboard-card:nth-child(6) {
            animation-delay: 0.6s;
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
            .sliding-text {
                font-size: 2.5rem;
            }

            .sliding-header-container {
                height: 80px;
            }

            .welcome-section p {
                font-size: 1.2rem;
            }

            .dashboard-card {
                padding: 35px 25px;
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

        @media (max-width: 576px) {
            .sliding-text {
                font-size: 1.8rem;
            }

            .sliding-header-container {
                height: 60px;
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
    <!-- Enhanced Animated Background Particles -->
    <div class="particles">
        <div class="particle"></div>
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
                    <i data-feather="shopping-cart" class="cart-icon"></i>
                    <div class="cart-badge"><?php echo $cart_count; ?></div>
                </div>

                <div class="user-info">
                    <div class="user-avatar"><?php echo $user_initial; ?></div>
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
                            <a class="nav-link active" href="dashboard.php">
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
                            <a class="nav-link" href="profile.php">
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
                <!-- Enhanced Welcome Section with Sliding Header -->
                <!-- Replace the existing welcome section with this enhanced version -->

                <!-- Enhanced Beautiful Welcome Section -->
     
                <!-- Quick Stats Section -->
                <div class="quick-stats animate__animated animate__fadeInUp">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $total_flights; ?></div>
                                <div class="stat-label">My Booked Flights</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $available_flights; ?></div>
                                <div class="stat-label">Available Flights</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $cart_count; ?></div>
                                <div class="stat-label">Cart Items</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Section -->
                <?php if (!empty($recent_bookings)): ?>
                    <div class="quick-stats animate__animated animate__fadeInUp">
                        <h5 style="color: #38a169; margin-bottom: 20px; font-weight: 700;">
                            <i data-feather="activity" style="margin-right: 10px;"></i>
                            Recent Bookings
                        </h5>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <div class="activity-card">
                                <div class="activity-header">
                                    <div>
                                        <div class="flight-info">
                                            <?php echo htmlspecialchars($booking['airline']); ?> <?php echo htmlspecialchars($booking['flight_no']); ?>
                                        </div>
                                        <div class="flight-route">
                                            <?php echo htmlspecialchars($booking['origin']); ?> → <?php echo htmlspecialchars($booking['destination']); ?> •
                                            <?php echo htmlspecialchars($booking['class']); ?> •
                                            <?php echo $booking['passengers']; ?> passenger(s)
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </div>
                                        <div style="color: #38a169; font-weight: 600; margin-top: 5px;">
                                            $<?php echo number_format($booking['total_amount'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Upcoming Flights Section -->
                <?php if (!empty($upcoming_user_flights)): ?>
                    <div class="quick-stats animate__animated animate__fadeInUp">
                        <h5 style="color: #38a169; margin-bottom: 20px; font-weight: 700;">
                            <i data-feather="plane" style="margin-right: 10px;"></i>
                            My Upcoming Flights
                        </h5>
                        <?php foreach ($upcoming_user_flights as $flight): ?>
                            <div class="activity-card">
                                <div class="activity-header">
                                    <div>
                                        <div class="flight-info">
                                            <?php echo htmlspecialchars($flight['airline']); ?> <?php echo htmlspecialchars($flight['flight_no']); ?>
                                        </div>
                                        <div class="flight-route">
                                            <?php echo htmlspecialchars($flight['origin']); ?> → <?php echo htmlspecialchars($flight['destination']); ?> •
                                            <?php echo date('M j, Y', strtotime($flight['flight_date'])); ?> •
                                            <?php echo date('g:i A', strtotime($flight['departure_time'])); ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="color: #38a169; font-weight: 600;">
                                            <?php echo ucfirst($flight['class']); ?> Class
                                        </div>
                                        <div style="color: #666; font-size: 0.9rem;">
                                            <?php echo $flight['passengers']; ?> passenger(s)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Enhanced Dashboard Cards -->
                <div class="row dashboard-cards">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="dashboard-card animate__animated animate__fadeInUp" onclick="navigateWithLoading('book-flight.php')">
                            <div class="card-icon primary">
                                <i data-feather="send"></i>
                            </div>
                            <div class="card-title">Book a Flight</div>
                            <div class="card-desc">Find and book flights to your favorite destinations worldwide with our smart search engine.</div>
                            <a href="book-flight.php">
                            <button class="btn btn-primary" onclick="navigateWithLoading('book-flight.php')">
                                Book Now <i data-feather="arrow-right" style="width:16px;height:16px;margin-left:8px;"></i>
                            </button>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="dashboard-card animate__animated animate__fadeInUp" onclick="navigateWithLoading('my-bookings.php')">
                            <div class="card-icon success">
                                <i data-feather="calendar"></i>
                            </div>
                            <div class="card-title">My Bookings</div>
                            <div class="card-desc">View and manage your upcoming and past flight bookings with detailed information.</div>
                            <a href="my-bookings.php">
                            <button class="btn btn-success" onclick="navigateWithLoading('my-bookings.php')">
                                View Bookings <i data-feather="arrow-right" style="width:16px;height:16px;margin-left:8px;"></i>
                            </button>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="dashboard-card animate__animated animate__fadeInUp" onclick="navigateWithLoading('flight-status.php')">
                            <div class="card-icon info">
                                <i data-feather="map-pin"></i>
                            </div>
                            <div class="card-title">Flight Status</div>
                            <div class="card-desc">Check the real-time status of your flights and get live updates on delays or changes.</div>
                            <a href="flight-status.php">
                            <button class="btn btn-info "  onclick="navigateWithLoading('flight-status.php')">
                                Check Status <i data-feather="arrow-right" style="width:16px; height:16px;margin-left:8px; color:white;;"></i>
                            </button>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="dashboard-card animate__animated animate__fadeInUp" onclick="navigateWithLoading('profile.php')">
                            <div class="card-icon warning">
                                <i data-feather="user"></i>
                            </div>
                            <div class="card-title">My Profile</div>
                            <div class="card-desc">Update your personal information, travel preferences, and account settings.</div>
                            <a href="profile.php">
                            <button class="btn btn-warning" onclick="navigateWithLoading('profile.php')">
                                Edit Profile <i data-feather="arrow-right" style="width:16px;height:16px;margin-left:8px;"></i>
                            </button>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="dashboard-card animate__animated animate__fadeInUp" onclick="navigateWithLoading('cart.php')">
                            <div class="card-icon danger">
                                <i data-feather="shopping-cart"></i>
                            </div>
                            <div class="card-title">Shopping Cart</div>
                            <div class="card-desc">Review your selected flights and complete your booking with secure payment.</div>
                            <a href="cart.php">
                            <button class="btn btn-danger" onclick="navigateWithLoading('cart.php')">
                                View Cart <i data-feather="arrow-right" style="width:16px;height:16px;margin-left:8px;"></i>
                            </button>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="dashboard-card animate__animated animate__fadeInUp" onclick="showComingSoon()">
                            <div class="card-icon purple">
                                <i data-feather="headphones"></i>
                            </div>
                            <div class="card-title">Support Center</div>
                            <div class="card-desc">Get help with your bookings, travel queries, and customer support assistance.</div>
                            <button class="btn btn-purple" onclick="showComingSoon()">
                                Get Help <i data-feather="arrow-right" style="width:16px;height:16px;margin-left:8px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <footer class="footer">
        &copy; <span id="year"></span> SKYNOVA Airlines. All Rights Reserved.  
    </footer>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Typewriter effect
            function typeWriter(element, text, speed = 50) {
                let i = 0;
                element.innerHTML = '';
                element.style.width = '0';

                function type() {
                    if (i < text.length) {
                        element.innerHTML += text.charAt(i);
                        i++;
                        setTimeout(type, speed);
                    } else {
                        // Remove cursor after typing is complete
                        setTimeout(() => {
                            element.style.borderRight = 'none';
                        }, 1000);
                    }
                }
                type();
            }

            // Initialize typewriter effect
            const typewriterElement = document.querySelector('.typewriter-text');
            if (typewriterElement) {
                const text = typewriterElement.getAttribute('data-text');
                setTimeout(() => {
                    typeWriter(typewriterElement, text, 80);
                }, 1500);
            }

            // Animated counter for stats
            function animateCounter(element, target, duration = 2000) {
                let start = 0;
                const increment = target / (duration / 16);

                function updateCounter() {
                    start += increment;
                    if (start < target) {
                        element.textContent = Math.floor(start);
                        requestAnimationFrame(updateCounter);
                    } else {
                        element.textContent = target;
                    }
                }
                updateCounter();
            }

            // Initialize counters when visible
            const observerOptions = {
                threshold: 0.5,
                rootMargin: '0px 0px -100px 0px'
            };

            const counterObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const counters = entry.target.querySelectorAll('.stat-number');
                        counters.forEach(counter => {
                            const target = parseInt(counter.getAttribute('data-target'));
                            animateCounter(counter, target);
                        });
                        counterObserver.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            const statsSection = document.querySelector('.welcome-stats');
            if (statsSection) {
                counterObserver.observe(statsSection);
            }
        });

        // Enhanced welcome message function - ADD THIS TO YOUR EXISTING FUNCTIONS
        function showWelcomeMessage() {
            // Create enhanced modal
            const modal = document.createElement('div');
            modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        animation: modalFadeIn 0.3s ease-out;
    `;

            modal.innerHTML = `
        <div style="
            background: linear-gradient(135deg, #38a169, #38a169);
            color: white;
            padding: 40px;
            border-radius: 25px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 83, 156, 0.3);
            animation: modalSlideIn 0.4s ease-out;
            position: relative;
            overflow: hidden;
        ">
            <div style="font-size: 4rem; margin-bottom: 20px; animation: iconBounce 0.6s ease-out;">✈️</div>
            <h2 style="margin-bottom: 15px; font-weight: 700;">Welcome Aboard!</h2>
            <p style="margin-bottom: 25px; opacity: 0.9; line-height: 1.6;">
                Discover amazing features like smart flight search, real-time updates, 
                easy booking management, and 24/7 customer support.
            </p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button onclick="this.closest('div').remove(); navigateWithLoading('book-flight.php')" style="
                    background: rgba(255,255,255,0.2);
                    border: 2px solid rgba(255,255,255,0.3);
                    color: white;
                    padding: 12px 24px;
                    border-radius: 15px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.3s ease;
                " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    Book Flight ✈️
                </button>
                <button onclick="this.closest('div').remove()" style="
                    background: transparent;
                    border: 2px solid rgba(255,255,255,0.3);
                    color: white;
                    padding: 12px 24px;
                    border-radius: 15px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.3s ease;
                " onmouseover="this.style.borderColor='rgba(255,255,255,0.6)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.3)'">
                    Close
                </button>
            </div>
            <div style="
                position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
                animation: modalGlow 3s ease-in-out infinite;
            "></div>
        </div>
    `;

            // Add animations
            const style = document.createElement('style');
            style.textContent = `
        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes modalSlideIn {
            from { opacity: 0; transform: scale(0.8) translateY(50px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes iconBounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        @keyframes modalGlow {
            0%, 100% { transform: rotate(0deg); opacity: 0.1; }
            50% { transform: rotate(180deg); opacity: 0.2; }
        }
    `;
            document.head.appendChild(style);

            document.body.appendChild(modal);

            // Close on backdrop click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.remove();
            });

            // Auto close after 10 seconds
            setTimeout(() => {
                if (modal.parentElement) modal.remove();
            }, 10000);
        }
    </script>
</body>

</html>