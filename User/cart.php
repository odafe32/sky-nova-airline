<?php
session_start();
$user_id = $_SESSION['user_id'];

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

// Handle success message from redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'removed') {
        $success_message = "Flight removed from cart successfully!";
    } elseif ($_GET['success'] == 'cleared') {
        $success_message = "Cart cleared successfully!";
    }
}

// Handle remove cart item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_cart_item'])) {
    try {
        $cart_id = intval($_POST['cart_id']);
        $stmt = $pdo->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);

        // Redirect immediately after successful deletion
        header("Location: cart.php?success=removed");
        exit();
    } catch (Exception $e) {
        $error_message = "Failed to remove flight from cart.";
    }
}

// Handle clear entire cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Redirect immediately after successful deletion
        header("Location: cart.php?success=cleared");
        exit();
    } catch (Exception $e) {
        $error_message = "Failed to clear cart.";
    }
}

// Handle edit cart item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_cart_item'])) {
    try {
        $cart_id = intval($_POST['cart_id']);
        $passengers = intval($_POST['passengers']);
        $class = $_POST['class'];
        $special_requests = $_POST['special_requests'] ?? '';

        // Recalculate total amount based on new class and passengers
        $stmt = $pdo->prepare("
            SELECT f.economy_price, f.business_price, f.first_class_price 
            FROM cart c 
            JOIN flights f ON c.flight_id = f.flight_id 
            WHERE c.cart_id = ? AND c.user_id = ?
        ");
        $stmt->execute([$cart_id, $user_id]);
        $flight = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($flight) {
            $price_per_person = 0;
            switch ($class) {
                case 'economy':
                    $price_per_person = $flight['economy_price'];
                    break;
                case 'business':
                    $price_per_person = $flight['business_price'];
                    break;
                case 'first':
                    $price_per_person = $flight['first_class_price'];
                    break;
            }

            $new_total = $price_per_person * $passengers;

            $stmt = $pdo->prepare("
                UPDATE cart 
                SET passengers = ?, class = ?, total_amount = ?, updated_at = NOW() 
                WHERE cart_id = ? AND user_id = ?
            ");
            $stmt->execute([$passengers, $class, $new_total, $cart_id, $user_id]);
            $success_message = "Flight details updated successfully!";
        }
    } catch (Exception $e) {
        $error_message = "Failed to update flight details.";
    }
}

// Get user information
$stmt = $pdo->prepare("SELECT user_id, full_name, email, phone, avatar FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get cart count for navbar
$stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart_count_result = $stmt->fetch(PDO::FETCH_ASSOC);
$cart_count = $cart_count_result['cart_count'];

// Get cart items with flight details
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        f.airline,
        f.flight_no,
        f.origin,
        f.destination,
        f.flight_date,
        f.departure_time,
        f.arrival_time,
        f.aircraft,
        f.economy_price,
        f.business_price,
        f.first_class_price,
        u.full_name as passenger_name,
        u.email as passenger_email,
        u.phone as passenger_phone,
        u.nationality,
        u.date_of_birth
    FROM cart c
    JOIN flights f ON c.flight_id = f.flight_id
    JOIN users u ON c.user_id = u.user_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total amount
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['total_amount'];
}

// Function to get airline logo
function getAirlineLogo($airline)
{
    $logos = [
        'Air France' => 'https://cdn.airpaz.com/cdn-cgi/image/w=60,h=60,f=webp/rel-0275/airlines/201x201/AF.png',
        'British Airways' => 'https://cdn.airpaz.com/cdn-cgi/image/w=60,h=60,f=webp/rel-0275/airlines/201x201/BA.png',
        'Emirates' => 'https://cdn.airpaz.com/cdn-cgi/image/w=60,h=60,f=webp/rel-0275/airlines/201x201/EK.png',
        'Lufthansa' => 'https://cdn.airpaz.com/cdn-cgi/image/w=60,h=60,f=webp/rel-0275/airlines/201x201/LH.png',
        'Turkish Airlines' => 'https://cdn.airpaz.com/cdn-cgi/image/w=60,h=60,f=webp/rel-0275/airlines/201x201/TK.png',
        'Qatar Airways' => 'https://cdn.airpaz.com/cdn-cgi/image/w=60,h=60,f=webp/rel-0275/airlines/201x201/QR.png'
    ];

    return $logos[$airline] ?? 'https://via.placeholder.com/60x60/00539C/FFFFFF?text=' . strtoupper(substr($airline, 0, 2));
}

// Get user's first name and avatar initial
$full_name = $user['full_name'];
$first_name = explode(' ', $full_name)[0];
$avatar_initial = strtoupper(substr($full_name, 0, 1));
$user_avatar = $user['avatar'] ? $user['avatar'] : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Cart | Speed of Light Airlines</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Shopping Cart - Speed of Light Airlines" />
    <meta name="keywords" content="airline, cart, booking, flights">
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
            animation: float 20s ease-in-out infinite;
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
            animation-delay: 5s;
        }

        .particle:nth-child(3) {
            width: 80px;
            height: 80px;
            left: 45%;
            animation-delay: 10s;
        }

        .particle:nth-child(4) {
            width: 30px;
            height: 30px;
            left: 65%;
            animation-delay: 15s;
        }

        .particle:nth-child(5) {
            width: 50px;
            height: 50px;
            left: 85%;
            animation-delay: 20s;
        }

        .particle:nth-child(6) {
            width: 35px;
            height: 35px;
            left: 15%;
            animation-delay: 3s;
        }

        .particle:nth-child(7) {
            width: 70px;
            height: 70px;
            left: 75%;
            animation-delay: 12s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.6;
            }

            50% {
                transform: translateY(-300px) rotate(180deg);
                opacity: 0.2;
            }
        }

        .navbar {
            background: #00539C;
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
            background: #003366;
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
            background: #00539C;
            color: #fff;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .main-content {
            padding: 40px 20px 80px 20px;
            position: relative;
            z-index: 10;
        }

        .cart-header {
            color: #00539C;
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

        .cart-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00539C, #003366);
        }

        .cart-header h1 {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #00539C, #003366);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .cart-header p {
            font-size: 1.2rem;
            opacity: 0.8;
            font-weight: 400;
        }

        .cart-summary {
            background: linear-gradient(135deg, #e3fcec, #c8f7c5);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: fadeInUp 1s ease-out;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(72, 187, 120, 0.2);
            box-shadow: 0 8px 32px rgba(72, 187, 120, 0.15);
        }

        .cart-summary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #48bb78, #38a169);
        }

        .cart-summary-info {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .cart-summary-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #48bb78, #38a169);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.8rem;
            animation: pulse 2s infinite;
            box-shadow: 0 4px 20px rgba(72, 187, 120, 0.3);
        }

        .cart-summary-text h4 {
            color: #1b5e20;
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 1.4rem;
        }

        .cart-summary-text p {
            color: #2e7d32;
            margin: 0;
            font-weight: 500;
            font-size: 1.1rem;
        }

        .cart-summary-total {
            text-align: right;
        }

        .cart-summary-total .total-label {
            color: #2e7d32;
            font-size: 1rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .cart-summary-total .total-amount {
            color: #1b5e20;
            font-size: 2.2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(27, 94, 32, 0.2);
        }

        .cart-item {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 28px;
            box-shadow: 0 12px 40px rgba(0, 83, 156, 0.12);
            margin-bottom: 35px;
            padding: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: fadeInUp 1s ease-out;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(0, 83, 156, 0.08);
        }

        .cart-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #00539C, #003366, #48bb78);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .cart-item:hover::before {
            transform: scaleX(1);
        }

        .cart-item:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 25px 70px rgba(0, 83, 156, 0.25);
            background: rgba(255, 255, 255, 1);
        }

        .cart-item-header {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.06) 0%, rgba(0, 51, 102, 0.06) 100%);
            padding: 30px;
            border-bottom: 2px solid rgba(0, 83, 156, 0.1);
            position: relative;
        }

        .booking-id {
            position: absolute;
            top: 30px;
            right: 30px;
            background: linear-gradient(135deg, #00539C, #003366);
            color: #fff;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.8px;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
            animation: glow 3s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
            }

            to {
                box-shadow: 0 4px 25px rgba(0, 83, 156, 0.6), 0 0 30px rgba(0, 83, 156, 0.2);
            }
        }

        .flight-info {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .airline-logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin-right: 20px;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 83, 156, 0.15);
            transition: transform 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px;
        }

        .cart-item:hover .airline-logo {
            transform: scale(1.15) rotate(8deg);
        }

        .airline-details h4 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #00539C;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 83, 156, 0.1);
        }

        .flight-number {
            color: #666;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .route-display {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 30px 0;
            position: relative;
            background: rgba(0, 83, 156, 0.02);
            border-radius: 20px;
            padding: 25px;
        }

        .route-point {
            text-align: center;
            flex: 1;
        }

        .route-code {
            font-size: 2rem;
            font-weight: 700;
            color: #00539C;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 83, 156, 0.1);
        }

        .route-city {
            color: #666;
            font-size: 1rem;
            font-weight: 500;
        }

        .route-time {
            color: #00539C;
            font-weight: 700;
            font-size: 1.2rem;
            margin-top: 8px;
        }

        .route-arrow {
            margin: 0 25px;
            color: #00539C;
            font-size: 1.8rem;
            position: relative;
        }

        .route-arrow::before {
            content: '✈️';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.5rem;
            animation: fly 3s ease-in-out infinite;
        }

        @keyframes fly {

            0%,
            100% {
                transform: translateX(-50%) translateY(0);
            }

            50% {
                transform: translateX(-50%) translateY(-10px);
            }
        }

        .passenger-details {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.03) 0%, rgba(72, 187, 120, 0.03) 100%);
            border-radius: 20px;
            padding: 30px;
            margin: 25px 0;
            border: 1px solid rgba(0, 83, 156, 0.1);
        }

        .passenger-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #00539C;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .passenger-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .passenger-item {
            background: rgba(255, 255, 255, 0.9);
            padding: 18px;
            border-radius: 16px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 83, 156, 0.1);
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.05);
        }

        .passenger-item:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 83, 156, 0.15);
        }

        .passenger-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 600;
        }

        .passenger-value {
            font-weight: 700;
            color: #00539C;
            font-size: 1.1rem;
        }

        .price-breakdown {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.06) 0%, rgba(0, 51, 102, 0.06) 100%);
            border-radius: 20px;
            padding: 30px;
            margin: 25px 0;
            border: 1px solid rgba(0, 83, 156, 0.1);
        }

        .price-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #00539C;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 83, 156, 0.1);
            transition: all 0.3s ease;
            font-size: 1.05rem;
        }

        .price-row:hover {
            background: rgba(0, 83, 156, 0.03);
            border-radius: 12px;
            padding: 12px 16px;
        }

        .price-total {
            font-size: 1.4rem;
            font-weight: 700;
            color: #00539C;
            border-bottom: 3px solid #00539C;
            background: rgba(0, 83, 156, 0.08);
            border-radius: 16px;
            padding: 20px 16px;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.1);
        }

        .cart-item-actions {
            padding: 30px;
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.03) 0%, rgba(72, 187, 120, 0.03) 100%);
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            border-top: 2px solid rgba(0, 83, 156, 0.1);
        }

        .action-btn {
            border: none;
            border-radius: 16px;
            padding: 14px 28px;
            font-weight: 700;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-width: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1rem;
            text-decoration: none;
        }

        .action-btn::before {
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

        .action-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .action-btn:hover {
            transform: translateY(-4px);
        }

        .btn-edit {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: #fff;
            box-shadow: 0 6px 20px rgba(66, 153, 225, 0.3);
        }

        .btn-edit:hover {
            box-shadow: 0 10px 30px rgba(66, 153, 225, 0.4);
            color: #fff;
        }

        .btn-remove {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: #fff;
            box-shadow: 0 6px 20px rgba(245, 101, 101, 0.3);
        }

        .btn-remove:hover {
            box-shadow: 0 10px 30px rgba(245, 101, 101, 0.4);
            color: #fff;
        }

        .btn-checkout {
            background: linear-gradient(135deg, #00539C 0%, #003366 100%);
            color: #fff;
            box-shadow: 0 6px 20px rgba(0, 83, 156, 0.3);
            animation: pulse-glow 2s ease-in-out infinite alternate;
            font-size: 1.1rem;
            min-width: 180px;
        }

        .btn-checkout:hover {
            box-shadow: 0 10px 30px rgba(0, 83, 156, 0.5);
            color: #fff;
        }

        .cart-footer {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 28px;
            box-shadow: 0 12px 40px rgba(0, 83, 156, 0.12);
            padding: 35px;
            margin-top: 50px;
            animation: fadeInUp 1.5s ease-out;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(0, 83, 156, 0.08);
        }

        .cart-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #00539C, #003366, #48bb78);
        }

        .cart-footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 25px;
        }

        .total-summary h3 {
            color: #00539C;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 83, 156, 0.1);
        }

        .total-summary p {
            color: #666;
            margin: 0;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .cart-actions {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .continue-btn,
        .clear-cart-btn {
            border: none;
            border-radius: 16px;
            font-weight: 700;
            padding: 16px 32px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-width: 160px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .continue-btn {
            background: linear-gradient(135deg, #00539C 0%, #003366 100%);
            color: #fff;
            box-shadow: 0 6px 20px rgba(0, 83, 156, 0.3);
            animation: pulse-glow 2s ease-in-out infinite alternate;
        }

        @keyframes pulse-glow {
            from {
                box-shadow: 0 6px 20px rgba(0, 83, 156, 0.3);
            }

            to {
                box-shadow: 0 6px 30px rgba(0, 83, 156, 0.6), 0 0 40px rgba(0, 83, 156, 0.2);
            }
        }

        .clear-cart-btn {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: #fff;
            box-shadow: 0 6px 20px rgba(245, 101, 101, 0.3);
        }

        .continue-btn::before,
        .clear-cart-btn::before {
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

        .continue-btn:hover::before,
        .clear-cart-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .continue-btn:hover,
        .clear-cart-btn:hover {
            transform: translateY(-4px);
            color: #fff;
        }

        .continue-btn:hover {
            box-shadow: 0 10px 30px rgba(0, 83, 156, 0.5);
        }

        .clear-cart-btn:hover {
            box-shadow: 0 10px 30px rgba(245, 101, 101, 0.5);
        }

        .empty-cart {
            text-align: center;
            padding: 100px 20px;
            color: #666;
        }

        .empty-cart img {
            width: 250px;
            margin-bottom: 40px;
            opacity: 0.7;
        }

        .empty-cart h3 {
            color: #00539C;
            font-size: 2rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .empty-cart p {
            font-size: 1.2rem;
            margin-bottom: 40px;
        }

        .shop-now-btn {
            background: linear-gradient(135deg, #00539C 0%, #003366 100%);
            color: #fff;
            border: none;
            border-radius: 16px;
            padding: 16px 32px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .shop-now-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 83, 156, 0.4);
            color: #fff;
        }

        .footer {
            background: #00539C;
            color: #fff;
            text-align: center;
            padding: 20px 0 12px 0;
            margin-top: 50px;
            letter-spacing: 1px;
        }

        /* Enhanced Modals */
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

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 22px;
            height: 22px;
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

        /* Staggered Animation */
        .cart-item:nth-child(1) {
            animation-delay: 0.1s;
        }

        .cart-item:nth-child(2) {
            animation-delay: 0.2s;
        }

        .cart-item:nth-child(3) {
            animation-delay: 0.3s;
        }

        .cart-item:nth-child(4) {
            animation-delay: 0.4s;
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
            .cart-header h1 {
                font-size: 2.5rem;
            }

            .cart-item {
                margin-bottom: 25px;
            }

            .route-code {
                font-size: 1.6rem;
            }

            .passenger-grid {
                grid-template-columns: 1fr;
            }

            .cart-footer-content {
                flex-direction: column;
                text-align: center;
            }

            .cart-actions {
                justify-content: center;
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
            background: rgba(0, 83, 156, 0.5);
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
                SOLA
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
                        <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar" class="user-avatar">
                    <?php else: ?>
                        <div class="user-avatar"><?php echo $avatar_initial; ?></div>
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
                            <a class="nav-link" href="dashboard.php"><i data-feather="home"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="book-flight.php"><i data-feather="send"></i> Book a Flight</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my-bookings.php"><i data-feather="calendar"></i> My Bookings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php"><i data-feather="user"></i> Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i data-feather="log-out"></i> Logout</a>
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

                <div class="cart-header animate__animated animate__fadeInDown">
                    <h1>My Shopping Cart</h1>
                    <p>Review your selected flights and passenger details before checkout.</p>
                </div>

                <?php if (empty($cart_items)): ?>
                    <!-- Empty Cart State -->
                    <div class="empty-cart">
                        <i data-feather="shopping-cart" style="width:120px;height:120px;color:#00539C;opacity:0.3;"></i>
                        <h3>Your cart is empty</h3>
                        <p>Start by searching and adding flights to your cart.</p>
                        <a href="book-flight.php" class="shop-now-btn">
                            <i data-feather="search"></i> Search Flights
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Enhanced Cart Summary -->
                    <div class="cart-summary">
                        <div class="cart-summary-info">
                            <div class="cart-summary-icon">
                                <i data-feather="shopping-cart"></i>
                            </div>
                            <div class="cart-summary-text">
                                <h4><?php echo count($cart_items); ?> Flight Booking<?php echo count($cart_items) > 1 ? 's' : ''; ?></h4>
                                <p>Ready for checkout • All details verified</p>
                            </div>
                        </div>
                        <div class="cart-summary-total">
                            <div class="total-label">Total Amount</div>
                            <div class="total-amount">₦<?php echo number_format($total_amount, 2); ?></div>
                        </div>
                    </div>

                    <!-- Cart Items -->
                    <div id="cartItemsContainer">
                        <?php foreach ($cart_items as $index => $item): ?>
                            <div class="cart-item animate__animated animate__fadeInUp" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                                <div class="booking-id">
                                    Cart #<?php echo str_pad($item['cart_id'], 3, '0', STR_PAD_LEFT); ?>
                                </div>

                                <div class="cart-item-header">
                                    <div class="flight-info">
                                        <img src="<?php echo getAirlineLogo($item['airline']); ?>" alt="<?php echo htmlspecialchars($item['airline']); ?>" class="airline-logo">
                                        <div class="airline-details">
                                            <h4><?php echo htmlspecialchars($item['airline']); ?></h4>
                                            <div class="flight-number"><?php echo htmlspecialchars($item['flight_no']); ?> • <?php echo ucfirst($item['trip_type']); ?> • <?php echo ucfirst($item['class']); ?></div>
                                        </div>
                                    </div>

                                    <div class="route-display">
                                        <div class="route-point">
                                            <div class="route-code"><?php echo htmlspecialchars($item['origin']); ?></div>
                                            <div class="route-city"><?php echo htmlspecialchars($item['origin']); ?></div>
                                            <div class="route-time"><?php echo date('H:i', strtotime($item['departure_time'])); ?></div>
                                        </div>
                                        <div class="route-arrow">
                                            <i data-feather="arrow-right"></i>
                                        </div>
                                        <div class="route-point">
                                            <div class="route-code"><?php echo htmlspecialchars($item['destination']); ?></div>
                                            <div class="route-city"><?php echo htmlspecialchars($item['destination']); ?></div>
                                            <div class="route-time"><?php echo date('H:i', strtotime($item['arrival_time'])); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="passenger-details">
                                    <div class="passenger-title">
                                        <i data-feather="user"></i> Passenger Information
                                    </div>
                                    <div class="passenger-grid">
                                        <div class="passenger-item">
                                            <div class="passenger-label">Full Name</div>
                                            <div class="passenger-value"><?php echo strtoupper(htmlspecialchars($item['passenger_name'])); ?></div>
                                        </div>
                                        <div class="passenger-item">
                                            <div class="passenger-label">Email</div>
                                            <div class="passenger-value"><?php echo htmlspecialchars($item['passenger_email']); ?></div>
                                        </div>
                                        <div class="passenger-item">
                                            <div class="passenger-label">Mobile</div>
                                            <div class="passenger-value"><?php echo htmlspecialchars($item['passenger_phone'] ?: 'Not provided'); ?></div>
                                        </div>
                                        <div class="passenger-item">
                                            <div class="passenger-label">Nationality</div>
                                            <div class="passenger-value"><?php echo htmlspecialchars($item['nationality'] ?: 'Not specified'); ?></div>
                                        </div>
                                        <div class="passenger-item">
                                            <div class="passenger-label">Date of Birth</div>
                                            <div class="passenger-value"><?php echo $item['date_of_birth'] ? date('d M Y', strtotime($item['date_of_birth'])) : 'Not provided'; ?></div>
                                        </div>
                                        <div class="passenger-item">
                                            <div class="passenger-label">Passengers</div>
                                            <div class="passenger-value"><?php echo $item['passengers']; ?> Passenger<?php echo $item['passengers'] > 1 ? 's' : ''; ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="price-breakdown">
                                    <div class="price-title">
                                        <i data-feather="dollar-sign"></i> Price Breakdown
                                    </div>
                                    <div class="price-row">
                                        <span><?php echo htmlspecialchars($item['origin'] . ' → ' . $item['destination']); ?> (<?php echo ucfirst($item['trip_type']); ?>)</span>
                                        <span>₦<?php echo number_format($item['total_amount'] / $item['passengers'], 2); ?> per person</span>
                                    </div>
                                    <div class="price-row">
                                        <span>Passengers</span>
                                        <span><?php echo $item['passengers']; ?> × ₦<?php echo number_format($item['total_amount'] / $item['passengers'], 2); ?></span>
                                    </div>
                                    <div class="price-row">
                                        <span>Class</span>
                                        <span><?php echo ucfirst($item['class']); ?></span>
                                    </div>
                                    <div class="price-row">
                                        <span>Taxes & Fees</span>
                                        <span>Included</span>
                                    </div>
                                    <div class="price-row price-total">
                                        <span>Total Price</span>
                                        <span>₦<?php echo number_format($item['total_amount'], 2); ?></span>
                                    </div>
                                </div>

                                <div class="cart-item-actions">
                                    <button class="action-btn btn-remove" data-bs-toggle="modal" data-bs-target="#removeFlightModal"
                                        data-cart-id="<?php echo $item['cart_id']; ?>"
                                        data-flight="<?php echo $item['flight_no'] . ' - ' . $item['origin'] . ' to ' . $item['destination']; ?>">
                                        <i data-feather="trash-2"></i> Remove
                                    </button>
                                    <a href="checkout.php?cart_id=<?php echo $item['cart_id']; ?>" class="action-btn btn-checkout">
                                        <i data-feather="credit-card"></i> Checkout This Flight
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Enhanced Cart Footer -->
                    <div class="cart-footer">
                        <div class="cart-footer-content">
                            <div class="total-summary">
                                <h3>Grand Total: ₦<?php echo number_format($total_amount, 2); ?></h3>
                                <p>For <?php echo array_sum(array_column($cart_items, 'passengers')); ?> passenger<?php echo array_sum(array_column($cart_items, 'passengers')) > 1 ? 's' : ''; ?> • All taxes included • Ready for checkout</p>
                            </div>
                            <div class="cart-actions">
                                <button class="clear-cart-btn" data-bs-toggle="modal" data-bs-target="#clearCartModal">
                                    <i data-feather="trash"></i> Clear All
                                </button>
                                <a href="checkout.php" class="continue-btn">
                                    <i data-feather="credit-card"></i> Checkout All Flights
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Remove Flight Modal -->
    <div class="modal fade" id="removeFlightModal" tabindex="-1" aria-labelledby="removeFlightModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="removeFlightModalLabel">
                        <i data-feather="trash-2"></i> Remove Flight
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="removeFlightForm">
                    <div class="modal-body text-center">
                        <i data-feather="alert-triangle" class="text-danger" style="width:64px;height:64px;"></i>
                        <h5 class="mt-3">Are you sure you want to remove this flight?</h5>
                        <p class="text-muted">This action cannot be undone. The flight will be permanently removed from your cart.</p>

                        <div class="alert alert-warning mt-3">
                            <strong>Flight:</strong> <span id="removeFlightDetails"></span>
                        </div>

                        <input type="hidden" name="remove_cart_item" value="1">
                        <input type="hidden" name="cart_id" id="removeCartId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i data-feather="x"></i> Keep Flight
                        </button>
                        <button type="submit" class="btn btn-danger" id="confirmRemoveBtn">
                            <i data-feather="trash-2"></i> Yes, Remove
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Clear Cart Modal -->
    <div class="modal fade" id="clearCartModal" tabindex="-1" aria-labelledby="clearCartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="clearCartModalLabel">
                        <i data-feather="trash"></i> Clear Entire Cart
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="clearCartForm">
                    <div class="modal-body text-center">
                        <i data-feather="shopping-cart" class="text-warning" style="width:64px;height:64px;"></i>
                        <h5 class="mt-3">Clear all flights from your cart?</h5>
                        <p class="text-muted">This will remove all <?php echo count($cart_items); ?> flight booking<?php echo count($cart_items) > 1 ? 's' : ''; ?> from your cart. This action cannot be undone.</p>

                        <div class="alert alert-info mt-3">
                            <strong>Total Value:</strong> ₦<?php echo number_format($total_amount, 2); ?> will be removed
                        </div>

                        <input type="hidden" name="clear_cart" value="1">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i data-feather="x"></i> Keep Cart
                        </button>
                        <button type="submit" class="btn btn-warning" id="confirmClearCartBtn">
                            <i data-feather="trash"></i> Yes, Clear All
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        &copy; <span id="year"></span> Speed of Light Airlines. All rights reserved.
    </footer>

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
        const cartIcon = document.querySelector('.cart-icon-container');
        if (cartIcon) {
            cartIcon.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        }

        // Edit Flight Modal functionality
        document.querySelectorAll('.btn-edit').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const cartId = this.getAttribute('data-cart-id');
                const passengers = this.getAttribute('data-passengers');
                const flightClass = this.getAttribute('data-class');
                const flight = this.getAttribute('data-flight');

                document.getElementById('editCartId').value = cartId;
                document.getElementById('editPassengers').value = passengers;
                document.getElementById('editClass').value = flightClass;
                document.getElementById('editFlightModalLabel').innerHTML =
                    '<i data-feather="edit"></i> Edit Flight - ' + flight;
                feather.replace();
            });
        });

        // Remove Flight Modal functionality
        document.querySelectorAll('.btn-remove').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const cartId = this.getAttribute('data-cart-id');
                const flightDetails = this.getAttribute('data-flight');

                document.getElementById('removeCartId').value = cartId;
                document.getElementById('removeFlightDetails').textContent = flightDetails;
            });
        });

        // Form submission loading states
        // Fix the remove button functionality
        // Fix the remove button functionality - SINGLE EVENT LISTENER ONLY
        // Fix the clear cart button functionality
        const confirmClearCartBtn = document.getElementById('confirmClearCartBtn');
        if (confirmClearCartBtn) {
            confirmClearCartBtn.addEventListener('click', function() {
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="loading-spinner"></span> Clearing...';
                this.disabled = true;

                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 10000);
            });
        }

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

        // Smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';

        // Entrance animations on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cart items for entrance animations
        document.querySelectorAll('.cart-item').forEach(item => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(30px)';
            item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(item);
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

        // Enhanced button click effects
        document.querySelectorAll('.action-btn, .continue-btn, .clear-cart-btn, .shop-now-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Create ripple effect
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');

                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add ripple CSS dynamically
        const rippleCSS = `
            .ripple {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple-animation 0.6s linear;
                pointer-events: none;
            }
            
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;

        const style = document.createElement('style');
        style.textContent = rippleCSS;
        document.head.appendChild(style);

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Enhanced cart interactions
        document.querySelectorAll('.cart-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.zIndex = '10';
            });

            item.addEventListener('mouseleave', function() {
                this.style.zIndex = '1';
            });
        });

        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Close any open modals
                document.querySelectorAll('.modal.show').forEach(modal => {
                    bootstrap.Modal.getInstance(modal)?.hide();
                });
            }
        });

        // Performance optimization: Lazy load images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        // Add loading states to all forms - EXCLUDE MODAL FORMS
        document.querySelectorAll('form:not(#removeFlightForm):not(#clearCartForm)').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="loading-spinner"></span> Processing...';
                    submitBtn.disabled = true;

                    // Re-enable after 10 seconds as fallback
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 10000);
                }
            });
        });

        // Console log for debugging
        console.log('🛒 Cart page loaded successfully');
        console.log('📊 Cart items:', <?php echo count($cart_items); ?>);
        console.log('💰 Total amount:', '₦<?php echo number_format($total_amount, 2); ?>');
    </script>
</body>

</html>