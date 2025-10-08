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

// Paystack Configuration (TEST MODE)
$paystack_secret_key = 'sk_test_2f7724ac9e631c232ad0aacb344e6c8897019f70'; // Replace with your test secret key
$paystack_public_key = 'pk_test_e6bef1f3afea98869309108d617345c0d64c6e6e'; // Replace with your test public key

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];

// Function to get cart count
function getCartCount($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM cart WHERE user_id = ? AND status = 'active' AND expires_at > NOW()");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['cart_count'];
}

// Function to initialize Paystack payment
function initializePaystackPayment($email, $amount, $reference, $callback_url, $secret_key)
{
    $url = "https://api.paystack.co/transaction/initialize";

    $fields = [
        'email' => $email,
        'amount' => $amount * 100, // Paystack expects amount in kobo (multiply by 100)
        'reference' => $reference,
        'callback_url' => $callback_url,
        'currency' => 'NGN'
    ];

    $fields_string = http_build_query($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer " . $secret_key,
        "Cache-Control: no-cache",
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

// Get user information
$stmt = $pdo->prepare("SELECT user_id, full_name, email, phone, avatar FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get cart count for navbar
$cart_count = getCartCount($pdo, $user_id);

// Initialize variables
$booking_details = null;
$flight = null;
$error_message = '';
$success_message = '';

// Handle Add to Cart functionality FIRST (before other POST handling)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    try {
        $pdo->beginTransaction();

        // Get booking details from session
        if (!isset($_SESSION['booking_details']) || !isset($_SESSION['flight_info'])) {
            throw new Exception("No booking details found. Please search for a flight first.");
        }

        $booking_details = $_SESSION['booking_details'];
        $flight = $_SESSION['flight_info'];

        // Check if this flight is already in user's cart
        $stmt = $pdo->prepare("
            SELECT cart_id FROM cart 
            WHERE user_id = ? AND flight_id = ? AND status = 'active' AND expires_at > NOW()
        ");
        $stmt->execute([$user_id, $booking_details['flight_id']]);
        $existing_cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_cart) {
            // Update existing cart item
            $stmt = $pdo->prepare("
                UPDATE cart SET 
                    passengers = ?, 
                    class = ?, 
                    trip_type = ?, 
                    total_amount = ?, 
                    updated_at = NOW(),
                    expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)
                WHERE cart_id = ?
            ");
            $stmt->execute([
                $booking_details['passengers'],
                $booking_details['class'],
                $booking_details['trip_type'],
                $booking_details['total_price'],
                $existing_cart['cart_id']
            ]);
            $success_message = "Flight updated in your cart successfully!";
        } else {
            // Generate booking reference for cart
$booking_reference = 'CART' . strtoupper(uniqid());

// Add new item to cart
$stmt = $pdo->prepare("
    INSERT INTO cart (user_id, flight_id, passengers, class, trip_type, 
                     total_amount, booking_reference, status, created_at, expires_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR))
");
$stmt->execute([
    $user_id,
    $booking_details['flight_id'],
    $booking_details['passengers'],
    $booking_details['class'],
    $booking_details['trip_type'],
    $booking_details['total_price'],
    $booking_reference
]);
            $success_message = "Flight added to your cart successfully!";
        }

        $pdo->commit();

        // Update cart count
        $cart_count = getCartCount($pdo, $user_id);

        // Redirect to prevent form resubmission
        header("Location: checkout.php?cart_added=1");
        exit();
    } catch (Exception $e) {
        $pdo->rollback();
        $error_message = "Failed to add to cart: " . $e->getMessage();
    }
}

// Handle success message from redirect
if (isset($_GET['cart_added'])) {
    $success_message = "Flight added to your cart successfully!";
}

// Handle POST data from flight selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['from_search'])) {
    // Get flight details from database to ensure data integrity
    $flight_id = intval($_POST['flight_id']);
    $stmt = $pdo->prepare("
        SELECT flight_id, airline, flight_no, origin, destination, flight_date,
               departure_time, arrival_time, aircraft, status,
               economy_price, business_price, first_class_price,
               economy_seats, business_seats, first_class_seats
        FROM flights 
        WHERE flight_id = ? AND status = 'Scheduled'
    ");
    $stmt->execute([$flight_id]);
    $flight_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$flight_data) {
        header("Location: book-flight.php?error=flight_not_found");
        exit();
    }

    // Calculate pricing based on class
    $passengers = intval($_POST['passengers']);
    $class = $_POST['class'];
    $trip_type = $_POST['trip_type'];

    $price_per_person = 0;
    switch (strtolower($class)) {
        case 'economy':
            $price_per_person = $flight_data['economy_price'];
            break;
        case 'business':
            $price_per_person = $flight_data['business_price'];
            break;
        case 'first':
            $price_per_person = $flight_data['first_class_price'];
            break;
    }

    $total_price = $price_per_person * $passengers;

    $booking_details = [
        'flight_id' => $flight_data['flight_id'],
        'passengers' => $passengers,
        'class' => $class,
        'total_price' => $total_price,
        'trip_type' => $trip_type,
        'price_per_person' => $price_per_person
    ];

    $flight = $flight_data;

    // Store in session for form processing
    $_SESSION['booking_details'] = $booking_details;
    $_SESSION['flight_info'] = $flight;
}
// Handle existing session data
elseif (isset($_SESSION['booking_details'])) {
    $booking_details = $_SESSION['booking_details'];

    // Re-fetch flight data from database to ensure freshness
    if (isset($booking_details['flight_id'])) {
        $stmt = $pdo->prepare("
            SELECT flight_id, airline, flight_no, origin, destination, flight_date,
                   departure_time, arrival_time, aircraft, status,
                   economy_price, business_price, first_class_price,
                   economy_seats, business_seats, first_class_seats
            FROM flights 
            WHERE flight_id = ? AND status = 'Scheduled'
        ");
        $stmt->execute([$booking_details['flight_id']]);
        $flight = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$flight) {
            unset($_SESSION['booking_details']);
            unset($_SESSION['flight_info']);
            header("Location: book-flight.php?error=flight_expired");
            exit();
        }

        $_SESSION['flight_info'] = $flight;
    } else {
        $flight = $_SESSION['flight_info'] ?? null;
    }
}
// Handle cart checkout
elseif (isset($_GET['cart_id'])) {
    $cart_id = intval($_GET['cart_id']);
    $stmt = $pdo->prepare("
        SELECT c.*, f.airline, f.flight_no, f.origin, f.destination, f.flight_date,
               f.departure_time, f.arrival_time, f.aircraft, f.status,
               f.economy_price, f.business_price, f.first_class_price
        FROM cart c
        JOIN flights f ON c.flight_id = f.flight_id
        WHERE c.cart_id = ? AND c.user_id = ? AND c.status IN ('active', 'pending') AND c.expires_at > NOW()
    ");
    $stmt->execute([$cart_id, $user_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        $booking_details = [
            'cart_id' => $cart_item['cart_id'],
            'flight_id' => $cart_item['flight_id'],
            'passengers' => $cart_item['passengers'],
            'class' => $cart_item['class'],
            'total_price' => $cart_item['total_amount'],
            'trip_type' => $cart_item['trip_type'],
            'price_per_person' => $cart_item['total_amount'] / $cart_item['passengers']
        ];
        $flight = $cart_item;

        // Store in session
        $_SESSION['booking_details'] = $booking_details;
        $_SESSION['flight_info'] = $flight;
    }
} else {
    // No booking data found, redirect to search
    header("Location: book-flight.php?error=no_booking_details");
    exit();
}

// Validate that we have the required data
if (!$booking_details || !$flight) {
    header("Location: book-flight.php?error=booking_expired");
    exit();
}

// Handle form submission for booking confirmation - MODIFIED FOR PAYSTACK
// Handle form submission for booking confirmation - MODIFIED FOR PAYSTACK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    try {
        $pdo->beginTransaction();

        // Get passenger details from form
        $passenger_name = trim($_POST['fullName'] ?? '');
        $passenger_email = trim($_POST['inputEmail'] ?? '');
        $passenger_phone = trim($_POST['inputMobile'] ?? '');

        // Validation
        if (empty($passenger_name) || empty($passenger_email) || empty($passenger_phone)) {
            throw new Exception("All passenger details are required.");
        }

        // Check seat availability before booking
        $stmt = $pdo->prepare("
            SELECT economy_seats, business_seats, first_class_seats 
            FROM flights WHERE flight_id = ?
        ");
        $stmt->execute([$booking_details['flight_id']]);
        $seat_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $available_seats = 0;
        switch (strtolower($booking_details['class'])) {
            case 'economy':
                $available_seats = $seat_data['economy_seats'];
                break;
            case 'business':
                $available_seats = $seat_data['business_seats'];
                break;
            case 'first':
                $available_seats = $seat_data['first_class_seats'];
                break;
        }

        if ($available_seats < $booking_details['passengers']) {
            throw new Exception("Insufficient seats available. Only {$available_seats} seats left in {$booking_details['class']} class.");
        }

        // Generate booking reference
        $booking_ref = 'SOL' . strtoupper(uniqid());

        // Create PENDING booking first (before payment)
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, flight_id, passengers, class, total_amount, 
                                booking_ref, status, trip_type, passenger_name, passenger_email, 
                                passenger_phone, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id,
            $booking_details['flight_id'],
            $booking_details['passengers'],
            $booking_details['class'],
            $booking_details['total_price'],
            $booking_ref,
            $booking_details['trip_type'],
            $passenger_name,
            $passenger_email,
            $passenger_phone
        ]);

        $booking_id = $pdo->lastInsertId();

        // Store booking info in session for payment processing
        $_SESSION['pending_booking'] = [
            'booking_id' => $booking_id,
            'booking_ref' => $booking_ref,
            'amount' => $booking_details['total_price'],
            'email' => $passenger_email,
            'cart_id' => $booking_details['cart_id'] ?? null
        ];

        $pdo->commit();

        // Redirect to Paystack payment page
        header("Location: paystack-payment.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollback();
        $error_message = "Booking failed: " . $e->getMessage();
    }
}
// Get user's first name and avatar initial
$full_name = $user['full_name'];
$first_name = explode(' ', $full_name)[0];
$avatar_initial = strtoupper(substr($full_name, 0, 1));
$user_avatar = $user['avatar'] ? $user['avatar'] : null;

// Pre-fill form with user data
$prefill_name = $user['full_name'];
$prefill_email = $user['email'];
$prefill_phone = $user['phone'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Checkout | Speed of Light Airlines</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Checkout - Speed of Light Airlines" />
    <meta name="keywords" content="airline, checkout, booking, payment">
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
            left: 8%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            width: 40px;
            height: 40px;
            left: 28%;
            animation-delay: 4s;
        }

        .particle:nth-child(3) {
            width: 80px;
            height: 80px;
            left: 48%;
            animation-delay: 8s;
        }

        .particle:nth-child(4) {
            width: 30px;
            height: 30px;
            left: 68%;
            animation-delay: 12s;
        }

        .particle:nth-child(5) {
            width: 50px;
            height: 50px;
            left: 88%;
            animation-delay: 16s;
        }

        .particle:nth-child(6) {
            width: 35px;
            height: 35px;
            left: 18%;
            animation-delay: 2s;
        }

        .particle:nth-child(7) {
            width: 70px;
            height: 70px;
            left: 78%;
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
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
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

        .checkout-header {
            color: #38a169;
            text-align: center;
            margin-bottom: 50px;
            animation: fadeInDown 1s ease-out;
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.05) 0%, rgba(0, 51, 102, 0.05) 100%);
            padding: 40px 20px;
            border-radius: 24px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 83, 156, 0.1);
        }

        .checkout-header h1 {
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

        .checkout-header p {
            font-size: 1.2rem;
            opacity: 0.8;
            font-weight: 400;
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #38a169;
            margin-bottom: 25px;
            position: relative;
            padding-left: 20px;
            animation: fadeInLeft 1s ease-out;
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

        .smart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 83, 156, 0.1);
            padding: 30px 25px;
            margin-bottom: 30px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: fadeInUp 1s ease-out;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 83, 156, 0.1);
        }

        .smart-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #38a169, #38a169);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .smart-card:hover::before {
            transform: scaleX(1);
        }

        .smart-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 15px 40px rgba(0, 83, 156, 0.15);
            background: rgba(255, 255, 255, 1);
        }

        .flight-summary-card {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.02) 0%, rgba(0, 51, 102, 0.02) 100%);
        }

        .flight-summary-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #38a169;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .flight-summary-detail {
            font-size: 1.1rem;
            color: #333;
        }

        .flight-summary-airline-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-right: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.1);
            transition: transform 0.3s ease;
        }

        .smart-card:hover .flight-summary-airline-logo {
            transform: scale(1.1) rotate(5deg);
        }

        .flight-summary-airline {
            font-weight: 600;
            font-size: 1.1rem;
            color: #38a169;
        }

        .flight-summary-badge {
            background: linear-gradient(135deg, #e3fcec, #c8f7c5);
            color: #1b5e20;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 20px;
            padding: 6px 16px;
            margin-left: 10px;
            box-shadow: 0 2px 10px rgba(27, 94, 32, 0.2);
            animation: pulse 2s infinite;
        }

        .route-display {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
            position: relative;
        }

        .route-point {
            text-align: center;
            flex: 1;
        }

        .route-code {
            font-size: 1.5rem;
            font-weight: 700;
            color: #38a169;
            margin-bottom: 5px;
        }

        .route-city {
            color: #666;
            font-size: 0.9rem;
        }

        .route-arrow {
            margin: 0 20px;
            color: #38a169;
            font-size: 1.5rem;
            position: relative;
        }

        .route-arrow::before {
            content: '✈️';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.2rem;
            animation: fly 3s ease-in-out infinite;
        }

        @keyframes fly {

            0%,
            100% {
                transform: translateX(-50%) translateY(0);
            }

            50% {
                transform: translateX(-50%) translateY(-8px);
            }
        }

        .warning-card {
            background: linear-gradient(135deg, #fffde7, #fff9c4);
            border: 2px solid rgba(255, 193, 7, 0.3);
            border-radius: 20px;
            padding: 25px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            animation: fadeInUp 1.2s ease-out;
            position: relative;
            overflow: hidden;
        }

        .warning-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffc107, #ff9800);
        }

        .warning-card img {
            width: 70px;
            height: 70px;
            animation: bounce 2s infinite;
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

        .action-buttons {
            display: flex;
            gap: 15px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .checkout-btn,
        .add-cart-btn,
        .back-btn {
            border: none;
            border-radius: 12px;
            font-weight: 600;
            padding: 14px 30px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-width: 150px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }

        .checkout-btn {
            background: linear-gradient(135deg, #38a169 0%, #38a169 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
        }

        .add-cart-btn {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
        }

        .back-btn {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .checkout-btn::before,
        .add-cart-btn::before,
        .back-btn::before {
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

        .checkout-btn:hover::before,
        .add-cart-btn:hover::before,
        .back-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 83, 156, 0.4);
            color: #fff;
        }

        .add-cart-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.4);
            color: #fff;
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
            color: #fff;
        }

        .voucher-section {
            background: linear-gradient(135deg, #e3fcec, #c8f7c5);
            border-radius: 20px;
            padding: 25px 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            animation: fadeInUp 1.4s ease-out;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(72, 187, 120, 0.2);
        }

        .voucher-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #48bb78, #38a169);
        }

        .voucher-section img {
            width: 90px;
            height: 90px;
            animation: bounce 2s infinite;
        }

        .newsletter-check {
            margin: 25px 0;
            padding: 20px;
            background: rgba(0, 83, 156, 0.02);
            border-radius: 12px;
            border: 1px solid rgba(0, 83, 156, 0.1);
        }

        .newsletter-check .form-check-input:checked {
            background-color: #38a169;
            border-color: #38a169;
        }

        .footer {
            background: #38a169;
            color: #fff;
            text-align: center;
            padding: 18px 0 10px 0;
            margin-top: 40px;
            letter-spacing: 1px;
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

        /* Payment Processing Styles */
        .payment-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 2px solid rgba(0, 132, 7, 0.3);
            border-radius: 20px;
            padding: 25px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            animation: fadeInUp 1.2s ease-out;
            position: relative;
            overflow: hidden;
        }

        .payment-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #38a169, #38a169);
        }

        .payment-info img {
            width: 70px;
            height: 70px;
            animation: bounce 2s infinite;
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
        }

        @media (max-width: 767px) {
            .checkout-header h1 {
                font-size: 2.2rem;
            }

            .smart-card {
                padding: 20px 15px;
            }

            .voucher-section,
            .warning-card,
            .payment-info {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .particles {
                display: none;
            }
        }

        /* Alert Styles */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee, #fdd);
            color: #c53030;
            border-left: 4px solid #e53e3e;
        }

        .alert-success {
            background: linear-gradient(135deg, #f0fff4, #e6fffa);
            color: #2f855a;
            border-left: 4px solid #38a169;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes bounce {

            0%,
            20%,
            53%,
            80%,
            100% {
                transform: translate3d(0, 0, 0);
            }

            40%,
            43% {
                transform: translate3d(0, -30px, 0);
            }

            70% {
                transform: translate3d(0, -15px, 0);
            }

            90% {
                transform: translate3d(0, -4px, 0);
            }
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

    <!-- Navbar (Top) -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="../pexels-sevenstormphotography-728824 (1).jpg" alt="Logo" style="width:38px; margin-right:10px; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                SKYNOVA
            </a>

            <!-- Enhanced User Section with Cart Icon -->
            <div class="navbar-user-section">
                <div class="cart-icon-container" onclick="window.location.href='cart.php'">
                    <i data-feather="shopping-cart" class="cart-icon"></i>
                    <?php if ($cart_count > 0): ?>
                        <div class="cart-badge"><?php echo $cart_count; ?></div>
                    <?php endif; ?>
                </div>

                <div class="user-info" onclick="window.location.href='profile.php'" style="cursor: pointer;">
                    <div class="user-avatar">
                        <?php if ($user_avatar): ?>
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="User Avatar">
                        <?php else: ?>
                            <?php echo $avatar_initial; ?>
                        <?php endif; ?>
                    </div>
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
                <div class="checkout-header animate__animated animate__fadeInDown">
                    <h1>Flight Checkout</h1>
                    <p>Review your booking details and complete your purchase or save to cart.</p>
                </div>

                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Flight</small>
                        <small>Passenger</small>
                        <small>Payment</small>
                        <small>Confirmation</small>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-primary" style="width: 75%"></div>
                    </div>
                </div>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger animate__animated animate__fadeInDown">
                        <i data-feather="alert-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success animate__animated animate__fadeInDown">
                        <i data-feather="check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Payment Information -->
                <div class="payment-info">
                    <img src="https://public-files-paystack-prod.s3.eu-west-1.amazonaws.com/integration-logos/paystack.jpg" alt="">
                    <div>
                        <div class="fw-bold mb-2">
                            <i data-feather="credit-card"></i> Secure Payment Processing
                            <span style="color:#2196f3;">Powered by Paystack</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i data-feather="shield"></i>
                            <span class="text-muted">Your payment is secured with 256-bit SSL encryption. Test mode enabled.</span>
                        </div>
                    </div>
                </div>

                <!-- Voucher Section -->
                <div class="voucher-section">
                    <img src="https://cdn.airpaz.com/cdn-cgi/image/w=2212,h=4448,f=webp/forerunner-next/img/illustration/v2/hero/ge-cross-booking-voucher-desktop.png" alt="Voucher">
                    <div>
                        <div class="fw-bold mb-2">
                            <i data-feather="gift"></i> Congratulations!
                            <span style="color:#43a047;">A FREE Hotel Discount Voucher</span> awaits you upon completing this booking
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <img src="https://cdn.airpaz.com/cdn-cgi/image/w=400,h=400,f=webp/forerunner-next/img/illustration/v2/spot/cross-booking-voucher.png" alt="Hotel Voucher" style="width:50px;">
                            <span class="text-muted">Save up to 50% on your next hotel booking!</span>
                        </div>
                    </div>
                </div>

                <!-- Passenger Details -->
                <div class="section-title">
                    <i data-feather="users"></i> Passenger Details
                </div>
                <div class="warning-card">
                    <img src="https://cdn.airpaz.com/cdn-cgi/image/w=120,h=120,f=webp/forerunner-next/img/illustration/v2/spot/warning.png" alt="Warning">
                    <div>
                        <strong>Important:</strong> Enter the passenger's name as written on the passport/ID Card. Spelling or punctuation errors may cause rejection of boarding or change fees.
                        <a href="#" style="color:#38a169;font-weight:600;"> Check Name Guidelines</a>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="smart-card">
                            <!-- Passenger Form for Booking Confirmation -->
                            <form id="passengerForm" method="POST">
                                <input type="hidden" name="confirm_booking" value="1">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="fullName" id="fullName"
                                            placeholder="Enter full name" value="<?php echo htmlspecialchars($prefill_name); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="inputEmail" id="inputEmail"
                                            placeholder="Enter email" value="<?php echo htmlspecialchars($prefill_email); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" name="inputMobile" id="inputMobile"
                                            placeholder="+234 812 345 6789" value="<?php echo htmlspecialchars($prefill_phone); ?>" required>
                                    </div>
                                </div>
                                <div class="form-text">We'll send your e-ticket and updates to your email and phone number.</div>

                                <div class="newsletter-check mt-3">
                                    <div class="form-check">
                                        <input type="checkbox" id="newsletter" class="form-check-input">
                                        <label for="newsletter" class="form-check-label">
                                            <i data-feather="mail"></i> I want to receive Speed of Light Airlines' exclusive promotions via newsletter
                                        </label>
                                    </div>
                                </div>

                                <!-- Enhanced Action Buttons -->
                                <div class="action-buttons">
                                    <a href="book-flight.php" class="back-btn">
                                        <i data-feather="arrow-left"></i> Back to Search
                                    </a>

                                    <button type="submit" class="checkout-btn" id="confirmBookingBtn">
                                        <i data-feather="credit-card"></i> Proceed to Payment
                                    </button>
                                </div>
                            </form>

                            <!-- Separate Add to Cart Form -->
                            <form method="POST" id="addToCartForm" style="margin-top: 15px;">
                                <input type="hidden" name="add_to_cart" value="1">
                                <button type="submit" class="add-cart-btn" id="addToCartBtn">
                                    <i data-feather="shopping-cart"></i> Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="smart-card summary-card">
                            <h5 class="mb-3">Flight Summary</h5>

                            <!-- Flight Details -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center flight-summary-title">
                                    <i data-feather="send"></i>
                                    <span><?php echo $booking_details['trip_type'] === 'round' ? 'Departure Flight' : 'Flight Details'; ?></span>
                                </div>
                                <div class="ps-4">
                                    <div class="fw-semibold">
                                        <?php echo htmlspecialchars($flight['origin'] . ' → ' . $flight['destination']); ?> | Direct Flight
                                    </div>
                                    <div class="text-muted">
                                        <?php echo date('D, d M Y', strtotime($flight['flight_date'])); ?> |
                                        <?php echo date('H:i', strtotime($flight['departure_time'])); ?> -
                                        <?php echo date('H:i', strtotime($flight['arrival_time'])); ?>
                                    </div>
                                    <div class="d-flex align-items-center mt-2">
                                        <div class="flight-summary-airline-logo" style="width:40px;height:40px;background:linear-gradient(135deg,#38a169,#38a169);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.8rem;margin-right:10px;">
                                            <?php echo strtoupper(substr($flight['airline'], 0, 2)); ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($flight['airline']); ?> •
                                            <?php echo htmlspecialchars($booking_details['class']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- Booking Details -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Passengers:</span>
                                    <span><?php echo $booking_details['passengers']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Class:</span>
                                    <span><?php echo htmlspecialchars($booking_details['class']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Trip Type:</span>
                                    <span><?php echo $booking_details['trip_type'] === 'round' ? 'Round Trip' : 'One Way'; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Price per person:</span>
                                    <span>₦<?php echo number_format($booking_details['price_per_person'], 2); ?></span>
                                </div>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between summary-price mt-2">
                                <span><strong>Total Amount:</strong></span>
                                <span id="summaryTotal"><strong>₦<?php echo number_format($booking_details['total_price'], 2); ?></strong></span>
                            </div>

                            <!-- Quick Cart Info -->
                            <?php if ($cart_count > 0): ?>
                                <div class="mt-3 p-3" style="background: rgba(72, 187, 120, 0.1); border-radius: 12px; border-left: 4px solid #48bb78;">
                                    <small class="text-muted">
                                        <i data-feather="info"></i> You have <?php echo $cart_count; ?> item(s) in your cart.
                                        <a href="cart.php" style="color: #48bb78; font-weight: 600;">View Cart</a>
                                    </small>
                                </div>
                            <?php endif; ?>
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
        const cartIconEl = document.querySelector('.cart-icon-container');
        if (cartIconEl) {
            cartIconEl.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        }

        // Form submission with loading animation
        document.getElementById('passengerForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('confirmBookingBtn');
            if (btn) {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="loading-spinner"></span> Processing Booking...';
                btn.disabled = true;
            }
            // Allow form to submit normally
        });

        // Add to Cart button loading animation
        document.getElementById('addToCartForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('addToCartBtn');
            if (btn) {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="loading-spinner"></span> Adding to Cart...';
                btn.disabled = true;
            }
            // Allow form to submit normally
        });

        // Enhanced form interactions
        document.querySelectorAll('.form-control, .form-select').forEach(function(input) {
            input.addEventListener('focus', function() {
                if (this.parentElement) {
                    this.parentElement.style.transform = 'scale(1.02)';
                    this.parentElement.style.transition = 'transform 0.3s ease';
                }
            });

            input.addEventListener('blur', function() {
                if (this.parentElement) {
                    this.parentElement.style.transform = 'scale(1)';
                }
            });
        });

        // Form validation with visual feedback
        document.getElementById('passengerForm').addEventListener('input', function(e) {
            const field = e.target;
            if (field.hasAttribute('required')) {
                if (!field.value.trim()) {
                    field.style.borderColor = '#f56565';
                    field.style.boxShadow = '0 0 0 0.2rem rgba(245, 101, 101, 0.25)';
                } else {
                    field.style.borderColor = '#48bb78';
                    field.style.boxShadow = '0 0 0 0.2rem rgba(72, 187, 120, 0.25)';
                }
            }
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

        // Observe all smart cards
        document.querySelectorAll('.smart-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });

        // Auto-hide success/error messages after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            });
        }, 5000);

        console.log('Enhanced checkout page loaded successfully');
        console.log('Booking details received:', <?php echo json_encode($booking_details); ?>);
        console.log('Flight details received:', <?php echo json_encode($flight); ?>);
        console.log('Cart functionality enabled');
        console.log('Current cart count:', <?php echo $cart_count; ?>);
    </script>
</body>

</html>