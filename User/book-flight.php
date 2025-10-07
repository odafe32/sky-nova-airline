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


// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT user_id, full_name, email, avatar, membership, phone, status FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// Check if user account is active
if ($user['status'] !== 'active') {
    session_destroy();
    header("Location: ../login.php?error=account_inactive");
    exit();
}

// Get cart count for the user
$stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM cart WHERE user_id = ? AND status = 'active' AND expires_at > NOW()");
$stmt->execute([$user_id]);
$cart_data = $stmt->fetch(PDO::FETCH_ASSOC);
$cart_count = $cart_data['cart_count'];

// Get available cities from flights table
$stmt = $pdo->prepare("
    SELECT DISTINCT city_name
    FROM (
        SELECT origin as city_name FROM flights 
        UNION 
        SELECT destination as city_name FROM flights
    ) as cities 
    WHERE city_name IS NOT NULL AND city_name != ''
    ORDER BY city_name ASC
");
$stmt->execute();
$available_cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert to the format expected by JavaScript
$formatted_cities = [];
foreach ($available_cities as $city) {
    $formatted_cities[] = [
        'city_name' => $city['city_name'],
        'city_code' => $city['city_name']
    ];
}

// Get upcoming flights for the modal (next 30 days)
$stmt = $pdo->prepare("
    SELECT 
        flight_id,
        airline,
        flight_no,
        origin,
        destination,
        flight_date,
        departure_time,
        arrival_time,
        aircraft,
        economy_price,
        business_price,
        first_class_price,
        economy_seats,
        business_seats,
        first_class_seats,
        status
    FROM flights 
    WHERE flight_date >= CURDATE() 
    AND flight_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND status = 'Scheduled'
    ORDER BY flight_date ASC, departure_time ASC
");
$stmt->execute();
$upcoming_flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's first name and avatar initial
$full_name = $user['full_name'];
$first_name = explode(' ', $full_name)[0];
$avatar_initial = strtoupper(substr($full_name, 0, 1));
$user_avatar = $user['avatar'] ? $user['avatar'] : null;

// Initialize variables for search results
$show_results = false;
$error_message = '';
$available_flights = [];
$search_data = [];

// Check if this is a search request (has search parameters)
$is_search_request = isset($_GET['fromCode']) && isset($_GET['toCode']) && !empty($_GET['fromCode']) && !empty($_GET['toCode']);

if ($is_search_request) {
    $show_results = true;

    // Get and sanitize form data
    $from_code = trim($_GET['fromCode']);
    $to_code = trim($_GET['toCode']);
    $departure_date = $_GET['departure'] ?? '';
    $return_date = $_GET['return'] ?? '';
    $passengers = intval($_GET['passengers'] ?? 1);
    $class = $_GET['class'] ?? 'Economy';
    $trip_type = $_GET['tripType'] ?? 'round';

    // Store search data for display
    $search_data = [
        'from' => $_GET['from'] ?? $from_code,
        'to' => $_GET['to'] ?? $to_code,
        'from_code' => $from_code,
        'to_code' => $to_code,
        'departure' => $departure_date,
        'return' => $return_date,
        'passengers' => $passengers,
        'class' => $class,
        'trip_type' => $trip_type
    ];

    // Validation checks
    if (empty($from_code) || empty($to_code)) {
        $error_message = "Please select both departure and destination cities.";
    } elseif ($from_code === $to_code) {
        $error_message = "Departure and destination cities cannot be the same. Please select different cities.";
    } elseif (empty($departure_date)) {
        $error_message = "Please select a departure date.";
    } elseif ($passengers < 1 || $passengers > 9) {
        $error_message = "Number of passengers must be between 1 and 9.";
    } elseif ($trip_type === 'round' && empty($return_date)) {
        $error_message = "Please select a return date for round trip.";
    } elseif (!empty($return_date) && $return_date <= $departure_date) {
        $error_message = "Return date must be after departure date.";
    } else {
        // Search for available flights
        $stmt = $pdo->prepare("
            SELECT 
                flight_id,
                airline,
                flight_no,
                origin,
                destination,
                flight_date,
                departure_time,
                arrival_time,
                aircraft,
                price,
                seats_available,
                status,
                economy_price,
                business_price,
                first_class_price,
                economy_seats,
                business_seats,
                first_class_seats
            FROM flights 
            WHERE origin = ? 
            AND destination = ? 
            AND flight_date = ?
            AND status = 'Scheduled'
            ORDER BY departure_time ASC
        ");

        $stmt->execute([$from_code, $to_code, $departure_date]);
        $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($flights)) {
            $error_message = "No flights available for the selected route and date. Please try different cities or dates.";
        } else {
            // Check seat availability based on class
            $available_flights = [];

            foreach ($flights as $flight) {
                $available_seats = 0;
                $price_per_person = 0;

                // Determine available seats and price based on class
                switch (strtolower($class)) {
                    case 'economy':
                        $available_seats = $flight['economy_seats'];
                        $price_per_person = $flight['economy_price'] > 0 ? $flight['economy_price'] : $flight['price'];
                        break;
                    case 'business':
                        $available_seats = $flight['business_seats'];
                        $price_per_person = $flight['business_price'] > 0 ? $flight['business_price'] : $flight['price'] * 2;
                        break;
                    case 'first':
                        $available_seats = $flight['first_class_seats'];
                        $price_per_person = $flight['first_class_price'] > 0 ? $flight['first_class_price'] : $flight['price'] * 3;
                        break;
                    default:
                        $available_seats = $flight['economy_seats'];
                        $price_per_person = $flight['economy_price'] > 0 ? $flight['economy_price'] : $flight['price'];
                }

                // Check if enough seats available
                if ($available_seats >= $passengers) {
                    $flight['available_seats_class'] = $available_seats;
                    $flight['price_per_person'] = $price_per_person;
                    $flight['total_price'] = $price_per_person * $passengers;
                    $flight['selected_class'] = $class;
                    $available_flights[] = $flight;
                }
            }

            if (empty($available_flights)) {
                $error_message = "No flights available with sufficient seats in " . ucfirst($class) . " class for " . $passengers . " passenger(s). Please try a different class or reduce the number of passengers.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo $show_results ? 'Flight Search Results' : 'Book a Flight'; ?> | Speed of Light Airlines</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="<?php echo $show_results ? 'Flight Search Results' : 'Book a Flight'; ?> - Speed of Light Airlines" />
    <meta name="keywords" content="airline, booking, flights, search results">
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
            background: rgba(0, 83, 156, 0.05);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }

        .particle:nth-child(1) {
            width: 60px;
            height: 60px;
            left: 10%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            width: 40px;
            height: 40px;
            left: 25%;
            animation-delay: 2s;
        }

        .particle:nth-child(3) {
            width: 80px;
            height: 80px;
            left: 45%;
            animation-delay: 4s;
        }

        .particle:nth-child(4) {
            width: 30px;
            height: 30px;
            left: 70%;
            animation-delay: 6s;
        }

        .particle:nth-child(5) {
            width: 50px;
            height: 50px;
            left: 85%;
            animation-delay: 1s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.3;
            }

            50% {
                transform: translateY(-80px) rotate(180deg);
                opacity: 0.1;
            }
        }

        .navbar {
            background: #10b981;
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

        .book-flight-header {
            color: #10b981;
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

        .book-flight-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #38a169);
        }

        .book-flight-header h1 {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #10b981, #38a169);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .book-flight-header p {
            font-size: 1.2rem;
            opacity: 0.8;
            font-weight: 400;
        }

        /* View Available Flights Button */
        .view-flights-btn {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
            margin-top: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .view-flights-btn:hover {
            background: linear-gradient(135deg, #38a169, #2f855a);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
        }

        /* Available Flights Modal */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #10b981, #38a169);
            color: #fff;
            border-radius: 20px 20px 0 0;
            padding: 20px 30px;
            border-bottom: none;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .btn-close {
            filter: invert(1);
            opacity: 0.8;
        }

        .btn-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 0;
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Flight Cards in Modal */
        .available-flight-card {
            background: #fff;
            border: none;
            border-bottom: 1px solid rgba(0, 83, 156, 0.1);
            padding: 20px 30px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .available-flight-card:hover {
            background: rgba(0, 83, 156, 0.02);
            transform: translateX(5px);
        }

        .available-flight-card:last-child {
            border-bottom: none;
            border-radius: 0 0 20px 20px;
        }

        .flight-route-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .route-cities {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .city-info {
            text-align: center;
        }

        .city-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #10b981;
            margin-bottom: 2px;
        }

        .city-time {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .route-separator {
            color: #10b981;
            font-size: 1.2rem;
            margin: 0 10px;
        }

        .flight-date-badge {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: #fff;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .flight-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .airline-badge {
            background: rgba(0, 83, 156, 0.1);
            color: #10b981;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .flight-number {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .price-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .price-item {
            text-align: center;
        }

        .price-label {
            font-size: 0.75rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .price-value {
            font-size: 0.9rem;
            font-weight: 700;
            color: #10b981;
        }

        .select-flight-indicator {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #10b981;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .available-flight-card:hover .select-flight-indicator {
            opacity: 1;
            transform: translateY(-50%) translateX(-5px);
        }

        /* Filter Section */
        .filter-section {
            background: rgba(0, 83, 156, 0.05);
            padding: 20px 30px;
            border-bottom: 1px solid rgba(0, 83, 156, 0.1);
        }

        .filter-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-select {
            border: 1px solid rgba(0, 83, 156, 0.2);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.9rem;
            background: #fff;
            min-width: 120px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 2px rgba(0, 83, 156, 0.1);
        }

        .no-flights-message {
            text-align: center;
            padding: 60px 30px;
            color: #666;
        }

        .no-flights-message i {
            font-size: 3rem;
            color: #10b981;
            margin-bottom: 20px;
        }

        /* Rest of your existing styles... */
        .checkout-header {
            color: #10b981;
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInDown 1s ease-out;
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.05) 0%, rgba(0, 51, 102, 0.05) 100%);
            padding: 30px 20px;
            border-radius: 24px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 83, 156, 0.1);
            position: relative;
            overflow: hidden;
        }

        .checkout-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #38a169);
        }

        .checkout-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #10b981, #38a169);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .booking-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 83, 156, 0.1);
            padding: 40px 30px;
            margin: 0 auto;
            max-width: 900px;
            animation: fadeInUp 1s ease-out;
            border: 1px solid rgba(0, 83, 156, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .booking-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #10b981, #38a169);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .booking-form:hover::before {
            transform: scaleX(1);
        }

        .booking-form:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 15px 40px rgba(0, 83, 156, 0.15);
            background: rgba(255, 255, 255, 1);
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
            border-color: #10b981;
            box-shadow: 0 0 0 0.2rem rgba(0, 83, 156, 0.15);
            background: #fff;
            transform: translateY(-2px);
        }

        /* Enhanced City Search Dropdown */
        .city-search-container {
            position: relative;
        }

        .city-search-input {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 12px 16px 12px 45px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            width: 100%;
            cursor: pointer;
        }

        .city-search-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 0.2rem rgba(0, 83, 156, 0.15);
            background: #fff;
            transform: translateY(-2px);
            cursor: text;
        }

        .city-search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            pointer-events: none;
            z-index: 10;
        }

        .city-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 2px solid #10b981;
            border-top: none;
            border-radius: 0 0 12px 12px;
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 8px 25px rgba(0, 83, 156, 0.15);
        }

        .city-dropdown.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .city-option {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .city-option:hover {
            background: rgba(0, 83, 156, 0.05);
            transform: translateX(5px);
        }

        .city-option:last-child {
            border-bottom: none;
        }

        .city-name {
            font-weight: 600;
            color: #2d3748;
        }

        .city-code {
            font-size: 0.85rem;
            color: #10b981;
            font-weight: 700;
            background: rgba(0, 83, 156, 0.1);
            padding: 2px 8px;
            border-radius: 6px;
        }

        .no-results {
            padding: 20px;
            text-align: center;
            color: #666;
            font-style: italic;
        }

        .no-cities-message {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }

        .search-btn {
            background: linear-gradient(135deg, #10b981 0%, #38a169 100%);
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
            animation: pulse-glow 2s ease-in-out infinite alternate;
        }

        @keyframes pulse-glow {
            from {
                box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
            }

            to {
                box-shadow: 0 4px 25px rgba(0, 83, 156, 0.6), 0 0 30px rgba(0, 83, 156, 0.2);
            }
        }

        .search-btn::before {
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

        .search-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .search-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 83, 156, 0.4);
        }

        .search-btn:active {
            transform: translateY(-1px);
        }

        .search-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            animation: none;
        }

        /* Swap Button */
        .swap-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #10b981;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
            z-index: 10;
        }

        .swap-btn:hover {
            transform: translate(-50%, -50%) scale(1.1) rotate(180deg);
            box-shadow: 0 6px 20px rgba(0, 83, 156, 0.4);
        }

        .swap-container {
            position: relative;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        /* Search Results Styles */
        .search-summary {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(0, 83, 156, 0.1);
            box-shadow: 0 4px 20px rgba(0, 83, 156, 0.1);
        }

        .search-summary h5 {
            color: #10b981;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .search-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 83, 156, 0.1);
        }

        .search-detail:last-child {
            border-bottom: none;
        }

        .search-detail strong {
            color: #2d3748;
        }

        .search-detail span {
            color: #10b981;
            font-weight: 600;
        }

        .error-message {
            background: linear-gradient(135deg, #fee, #fdd);
            color: #c53030;
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            margin: 30px 0;
            border-left: 4px solid #e53e3e;
            animation: fadeInUp 1s ease-out;
        }

        .error-message i {
            font-size: 2rem;
            margin-bottom: 15px;
            display: block;
        }

        .error-message h4 {
            margin-bottom: 10px;
            font-weight: 700;
        }

        .back-btn {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .back-btn:hover {
            background: linear-gradient(135deg, #495057, #343a40);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .flight-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 83, 156, 0.1);
            box-shadow: 0 8px 32px rgba(0, 83, 156, 0.1);
            transition: all 0.3s ease;
            animation: fadeInUp 1s ease-out;
        }

        .flight-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 83, 156, 0.15);
        }

        .flight-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(0, 83, 156, 0.1);
        }

        .airline-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .airline-logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #10b981, #38a169);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .airline-details h4 {
            color: #10b981;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .flight-number {
            color: #666;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .flight-status {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: #fff;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .flight-route {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .route-point {
            text-align: center;
            flex: 1;
        }

        .route-point h3 {
            color: #10b981;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .route-point p {
            color: #666;
            margin: 0;
            font-weight: 500;
        }

        .route-arrow {
            flex: 0 0 100px;
            text-align: center;
            position: relative;
        }

        .route-arrow::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 20%;
            right: 20%;
            height: 2px;
            background: linear-gradient(90deg, #10b981, #38a169);
            transform: translateY(-50%);
        }

        .route-arrow i {
            background: #fff;
            color: #10b981;
            padding: 8px;
            border-radius: 50%;
            border: 2px solid #10b981;
            position: relative;
            z-index: 2;
        }

        .flight-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .detail-item {
            background: rgba(0, 83, 156, 0.05);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }

        .detail-item i {
            color: #10b981;
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
        }

        .detail-item strong {
            color: #2d3748;
            display: block;
            margin-bottom: 5px;
        }

        .detail-item span {
            color: #10b981;
            font-weight: 600;
        }

        .pricing-section {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.05), rgba(0, 51, 102, 0.05));
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 25px;
        }

        .pricing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .pricing-header h5 {
            color: #10b981;
            font-weight: 700;
            margin: 0;
        }

        .class-badge {
            background: linear-gradient(135deg, #10b981, #38a169);
            color: #fff;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .price-breakdown {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 83, 156, 0.1);
        }

        .price-breakdown:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.1rem;
            color: #10b981;
        }

        .select-flight-btn {
            background: linear-gradient(135deg, #10b981 0%, #38a169 100%);
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
        }

        .select-flight-btn::before {
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

        .select-flight-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .select-flight-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 83, 156, 0.4);
        }

        .footer {
            background: #10b981;
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

            .swap-container {
                flex-direction: column;
                gap: 10px;
            }

            .swap-btn {
                position: static;
                transform: rotate(90deg);
                margin: 10px 0;
            }

            .flight-route {
                flex-direction: column;
                gap: 15px;
            }

            .route-arrow {
                transform: rotate(90deg);
            }

            .flight-details {
                grid-template-columns: 1fr;
            }

            .flight-route-info {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .flight-meta {
                justify-content: center;
            }

            .filter-row {
                justify-content: center;
            }
        }

        @media (max-width: 767px) {
            .book-flight-header h1 {
                font-size: 2.2rem;
            }

            .checkout-header h1 {
                font-size: 2rem;
            }

            .booking-form {
                padding: 25px 20px;
            }

            .flight-card {
                padding: 20px 15px;
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

            .flight-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .search-detail {
                flex-direction: column;
                gap: 5px;
                text-align: center;
            }

            .modal-body {
                max-height: 60vh;
            }

            .available-flight-card {
                padding: 15px 20px;
            }

            .filter-section {
                padding: 15px 20px;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 83, 156, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(0, 83, 156, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #38a169;
        }

        .city-dropdown::-webkit-scrollbar {
            width: 6px;
        }

        .city-dropdown::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .city-dropdown::-webkit-scrollbar-thumb {
            background: rgba(0, 83, 156, 0.3);
            border-radius: 3px;
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
    </div>

    <!-- Enhanced Navbar with Cart Icon and User Info -->
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
                            <a class="nav-link" href="dashboard.php">
                                <i data-feather="home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo !$show_results ? 'active' : ''; ?>" href="book-flight.php">
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
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger animate__animated animate__fadeInDown">
                        <?php
                        switch ($_GET['error']) {
                            case 'account_inactive':
                                echo '<i data-feather="alert-circle"></i> Your account is inactive. Please contact support.';
                                break;
                            case 'session_expired':
                                echo '<i data-feather="clock"></i> Your session has expired. Please log in again.';
                                break;
                            default:
                                echo '<i data-feather="alert-triangle"></i> An error occurred. Please try again.';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success animate__animated animate__fadeInDown">
                        <?php
                        switch ($_GET['success']) {
                            case 'profile_updated':
                                echo '<i data-feather="check-circle"></i> Profile updated successfully!';
                                break;
                            default:
                                echo '<i data-feather="check"></i> Operation completed successfully!';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (!$show_results): ?>
                    <!-- SEARCH FORM -->
                    <div class="book-flight-header animate__animated animate__fadeInDown">
                        <h1>Book a Flight</h1>
                        <p>Find the best flights and book your next adventure with ease.</p>

                        <!-- View Available Flights Button -->
                        <button type="button" class="view-flights-btn" data-bs-toggle="modal" data-bs-target="#availableFlightsModal">
                            <i data-feather="eye"></i> View Available Flights
                        </button>
                    </div>

                    <?php if (empty($formatted_cities)): ?>
                        <div class="no-cities-message animate__animated animate__fadeInUp">
                            <i data-feather="info"></i>
                            <strong>No flights available at the moment.</strong><br>
                            Please check back later or contact our support team for assistance.
                        </div>
                    <?php else: ?>
                        <form class="booking-form shadow-lg animate__animated animate__fadeInUp" autocomplete="off" action="book-flight.php" method="get">
                            <div class="row g-4 align-items-end">
                                <!-- Trip Type -->
                                <div class="col-md-3">
                                    <label class="form-label">Trip Type</label>
                                    <select class="form-select" id="tripType" name="tripType">
                                        <option value="round">Round Trip</option>
                                        <option value="oneway">One Way</option>
                                    </select>
                                </div>

                                <!-- From and To with Swap Button -->
                                <div class="col-md-6">
                                    <div class="swap-container">
                                        <!-- From -->
                                        <div style="flex: 1;">
                                            <label class="form-label">From</label>
                                            <div class="city-search-container">
                                                <i data-feather="map-pin" class="city-search-icon"></i>
                                                <input type="text" class="city-search-input" id="fromCity" name="from" placeholder="Select departure city..." required readonly>
                                                <input type="hidden" id="fromCityCode" name="fromCode">
                                                <div class="city-dropdown" id="fromDropdown"></div>
                                            </div>
                                        </div>

                                        <!-- Swap Button -->
                                    

                                        <!-- To -->
                                        <div style="flex: 1;">
                                            <label class="form-label">To</label>
                                            <div class="city-search-container">
                                                <i data-feather="navigation" class="city-search-icon"></i>
                                                <input type="text" class="city-search-input" id="toCity" name="to" placeholder="Select destination city..." required readonly>
                                                <input type="hidden" id="toCityCode" name="toCode">
                                                <div class="city-dropdown" id="toDropdown"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Departure Date -->
                                <div class="col-md-3">
                                    <label class="form-label">Departure</label>
                                    <input type="date" class="form-control" name="departure" id="departureDate" required>
                                </div>
                            </div>

                            <div class="row g-4 mt-2">
                                <!-- Return Date -->
                                <div class="col-md-3" id="returnDateCol">
                                    <label class="form-label">Return</label>
                                    <input type="date" class="form-control" name="return" id="returnDate">
                                </div>

                                <!-- Passengers -->
                                <div class="col-md-3">
                                    <label class="form-label">Passengers</label>
                                    <input type="number" class="form-control" name="passengers" min="1" max="9" value="1" id="passengersInput" required>
                                </div>

                                <!-- Class -->
                                <div class="col-md-3">
                                    <label class="form-label">Class</label>
                                    <select class="form-select" name="class" id="classSelect">
                                        <option>Economy</option>
                                        <option>Business</option>
                                        <option>First</option>
                                    </select>
                                </div>

                                <!-- Search Button -->
                                <div class="col-md-3 d-grid">
                                    <button type="submit" class="btn search-btn btn-lg" id="searchBtn" disabled style="color: white;">
                                        <i data-feather="search"></i>
                                        Search Flights
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- SEARCH RESULTS -->
                    <div class="checkout-header animate__animated animate__fadeInDown">
                        <h1>Flight Search Results</h1>
                        <p>Review available flights and proceed to booking</p>
                    </div>

                    <!-- Search Summary -->
                    <?php if (!empty($search_data)): ?>
                        <div class="search-summary animate__animated animate__fadeInUp">
                            <h5><i data-feather="search"></i> Search Details</h5>
                            <div class="search-detail">
                                <strong>Route:</strong>
                                <span><?php echo htmlspecialchars($search_data['from']); ?> → <?php echo htmlspecialchars($search_data['to']); ?></span>
                            </div>
                            <div class="search-detail">
                                <strong>Departure:</strong>
                                <span><?php echo date('M d, Y', strtotime($search_data['departure'])); ?></span>
                            </div>
                            <?php if (!empty($search_data['return']) && $search_data['trip_type'] === 'round'): ?>
                                <div class="search-detail">
                                    <strong>Return:</strong>
                                    <span><?php echo date('M d, Y', strtotime($search_data['return'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="search-detail">
                                <strong>Passengers:</strong>
                                <span><?php echo $search_data['passengers']; ?> passenger(s)</span>
                            </div>
                            <div class="search-detail">
                                <strong>Class:</strong>
                                <span><?php echo ucfirst($search_data['class']); ?></span>
                            </div>
                            <div class="search-detail">
                                <strong>Trip Type:</strong>
                                <span><?php echo $search_data['trip_type'] === 'round' ? 'Round Trip' : 'One Way'; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Error Message -->
                    <?php if (!empty($error_message)): ?>
                        <div class="error-message animate__animated animate__fadeInUp">
                            <i data-feather="alert-triangle"></i>
                            <h4>Flight Not Available</h4>
                            <p><?php echo htmlspecialchars($error_message); ?></p>
                            <a href="book-flight.php" class="back-btn">
                                <i data-feather="arrow-left"></i> Search Again
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Available Flights -->
                    <?php if (!empty($available_flights)): ?>
                        <?php foreach ($available_flights as $flight): ?>
                            <div class="flight-card">
                                <!-- Flight Header -->
                                <div class="flight-header">
                                    <div class="airline-info">
                                        <div class="airline-logo">
                                            <?php echo strtoupper(substr($flight['airline'], 0, 2)); ?>
                                        </div>
                                        <div class="airline-details">
                                            <h4><?php echo htmlspecialchars($flight['airline']); ?></h4>
                                            <div class="flight-number"><?php echo htmlspecialchars($flight['flight_no']); ?></div>
                                        </div>
                                    </div>
                                    <div class="flight-status"><?php echo htmlspecialchars($flight['status']); ?></div>
                                </div>

                                <!-- Flight Route -->
                                <div class="flight-route">
                                    <div class="route-point">
                                        <h3><?php echo htmlspecialchars($flight['origin']); ?></h3>
                                        <p><?php echo date('H:i', strtotime($flight['departure_time'])); ?></p>
                                        <p>Departure</p>
                                    </div>
                                    <div class="route-arrow">
                                        <i data-feather="arrow-right"></i>
                                    </div>
                                    <div class="route-point">
                                        <h3><?php echo htmlspecialchars($flight['destination']); ?></h3>
                                        <p><?php echo date('H:i', strtotime($flight['arrival_time'])); ?></p>
                                        <p>Arrival</p>
                                    </div>
                                </div>

                                <!-- Flight Details -->
                                <div class="flight-details">
                                    <div class="detail-item">
                                        <i data-feather="calendar"></i>
                                        <strong>Date</strong>
                                        <span><?php echo date('M d, Y', strtotime($flight['flight_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i data-feather="clock"></i>
                                        <strong>Duration</strong>
                                        <span>
                                            <?php
                                            $departure = new DateTime($flight['departure_time']);
                                            $arrival = new DateTime($flight['arrival_time']);
                                            $duration = $departure->diff($arrival);
                                            echo $duration->format('%h:%I');
                                            ?>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <i data-feather="users"></i>
                                        <strong>Available Seats</strong>
                                        <span><?php echo $flight['available_seats_class']; ?> seats</span>
                                    </div>
                                    <div class="detail-item">
                                        <i data-feather="send"></i>
                                        <strong>Aircraft</strong>
                                        <span><?php echo htmlspecialchars($flight['aircraft']); ?></span>
                                    </div>
                                </div>

                                <!-- Pricing Section -->
                                <div class="pricing-section">
                                    <div class="pricing-header">
                                        <h5>Pricing Details</h5>
                                        <div class="class-badge"><?php echo ucfirst($flight['selected_class']); ?> Class</div>
                                    </div>
                                    <div class="price-breakdown">
                                        <span>Price per person:</span>
                                        <span>₦<?php echo number_format($flight['price_per_person'], 2); ?></span>
                                    </div>
                                    <div class="price-breakdown">
                                        <span>Number of passengers:</span>
                                        <span><?php echo $search_data['passengers']; ?></span>
                                    </div>
                                    <div class="price-breakdown">
                                        <span><strong>Total Amount:</strong></span>
                                        <span><strong>₦<?php echo number_format($flight['total_price'], 2); ?></strong></span>
                                    </div>
                                </div>

                                <!-- Select Flight Button - FIXED: Direct form submission to checkout.php -->
                                <form method="post" action="checkout.php" style="display: inline;">
                                    <input type="hidden" name="flight_id" value="<?php echo $flight['flight_id']; ?>">
                                    <input type="hidden" name="airline" value="<?php echo htmlspecialchars($flight['airline']); ?>">
                                    <input type="hidden" name="flight_no" value="<?php echo htmlspecialchars($flight['flight_no']); ?>">
                                    <input type="hidden" name="origin" value="<?php echo htmlspecialchars($flight['origin']); ?>">
                                    <input type="hidden" name="destination" value="<?php echo htmlspecialchars($flight['destination']); ?>">
                                    <input type="hidden" name="flight_date" value="<?php echo $flight['flight_date']; ?>">
                                    <input type="hidden" name="departure_time" value="<?php echo $flight['departure_time']; ?>">
                                    <input type="hidden" name="arrival_time" value="<?php echo $flight['arrival_time']; ?>">
                                    <input type="hidden" name="aircraft" value="<?php echo htmlspecialchars($flight['aircraft']); ?>">
                                    <input type="hidden" name="passengers" value="<?php echo $search_data['passengers']; ?>">
                                    <input type="hidden" name="class" value="<?php echo $flight['selected_class']; ?>">
                                    <input type="hidden" name="price_per_person" value="<?php echo $flight['price_per_person']; ?>">
                                    <input type="hidden" name="total_price" value="<?php echo $flight['total_price']; ?>">
                                    <input type="hidden" name="trip_type" value="<?php echo $search_data['trip_type']; ?>">
                                    <input type="hidden" name="return_date" value="<?php echo $search_data['return'] ?? ''; ?>">
                                    <input type="hidden" name="from_search" value="1">
                                    <button type="submit" class="select-flight-btn">
                                        <i data-feather="check-circle"></i> Select This Flight - ₦<?php echo number_format($flight['total_price'], 2); ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Back to Search Button -->
                    <div class="text-center mt-4">
                        <a href="book-flight.php" class="back-btn">
                            <i data-feather="arrow-left"></i> Search Different Flight
                        </a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Available Flights Modal -->
    <div class="modal fade" id="availableFlightsModal" tabindex="-1" aria-labelledby="availableFlightsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="availableFlightsModalLabel">
                        <i data-feather="send"></i> Available Flights (Next 30 Days)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-row">
                        <div class="filter-item">
                            <label class="filter-label">Route</label>
                            <select class="filter-select" id="routeFilter">
                                <option value="">All Routes</option>
                            </select>
                        </div>
                        <div class="filter-item">
                            <label class="filter-label">Airline</label>
                            <select class="filter-select" id="airlineFilter">
                                <option value="">All Airlines</option>
                            </select>
                        </div>
                        <div class="filter-item">
                            <label class="filter-label">Class</label>
                            <select class="filter-select" id="modalClassFilter">
                                <option value="Economy">Economy</option>
                                <option value="Business">Business</option>
                                <option value="First">First Class</option>
                            </select>
                        </div>
                        <div class="filter-item">
                            <label class="filter-label">Passengers</label>
                            <select class="filter-select" id="modalPassengersFilter">
                                <option value="1">1 Passenger</option>
                                <option value="2">2 Passengers</option>
                                <option value="3">3 Passengers</option>
                                <option value="4">4 Passengers</option>
                                <option value="5">5 Passengers</option>
                                <option value="6">6 Passengers</option>
                                <option value="7">7 Passengers</option>
                                <option value="8">8 Passengers</option>
                                <option value="9">9 Passengers</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-body" id="modalFlightsList">
                    <?php if (!empty($upcoming_flights)): ?>
                        <?php foreach ($upcoming_flights as $flight): ?>
                            <div class="available-flight-card"
                                data-route="<?php echo htmlspecialchars($flight['origin'] . '-' . $flight['destination']); ?>"
                                data-airline="<?php echo htmlspecialchars($flight['airline']); ?>"
                                data-date="<?php echo $flight['flight_date']; ?>"
                                onclick="selectFlightFromModal(<?php echo htmlspecialchars(json_encode($flight)); ?>)">

                                <div class="flight-route-info">
                                    <div class="route-cities">
                                        <div class="city-info">
                                            <div class="city-name"><?php echo htmlspecialchars($flight['origin']); ?></div>
                                            <div class="city-time"><?php echo date('H:i', strtotime($flight['departure_time'])); ?></div>
                                        </div>
                                        <div class="route-separator">
                                            <i data-feather="arrow-right"></i>
                                        </div>
                                        <div class="city-info">
                                            <div class="city-name"><?php echo htmlspecialchars($flight['destination']); ?></div>
                                            <div class="city-time"><?php echo date('H:i', strtotime($flight['arrival_time'])); ?></div>
                                        </div>
                                    </div>
                                    <div class="flight-date-badge">
                                        <?php echo date('M d, Y', strtotime($flight['flight_date'])); ?>
                                    </div>
                                </div>

                                <div class="flight-meta">
                                    <div>
                                        <div class="airline-badge"><?php echo htmlspecialchars($flight['airline']); ?></div>
                                        <div class="flight-number"><?php echo htmlspecialchars($flight['flight_no']); ?></div>
                                    </div>

                                    <div class="price-info">
                                        <div class="price-item">
                                            <div class="price-label">Economy</div>
                                            <div class="price-value">₦<?php echo number_format($flight['economy_price'], 0); ?></div>
                                        </div>
                                        <div class="price-item">
                                            <div class="price-label">Business</div>
                                            <div class="price-value">₦<?php echo number_format($flight['business_price'], 0); ?></div>
                                        </div>
                                        <div class="price-item">
                                            <div class="price-label">First</div>
                                            <div class="price-value">₦<?php echo number_format($flight['first_class_price'], 0); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="select-flight-indicator">
                                    <i data-feather="chevron-right"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-flights-message">
                            <i data-feather="calendar-x"></i>
                            <h4>No Flights Available</h4>
                            <p>There are currently no scheduled flights for the next 30 days.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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

        // Get cities and flights from database (passed from PHP)
        const cities = <?php echo json_encode($formatted_cities); ?>;
        const upcomingFlights = <?php echo json_encode($upcoming_flights); ?>;

        let currentActiveInput = null;

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

        // City search functionality
        function setupCitySearch(inputId, dropdownId, codeInputId) {
            const input = document.getElementById(inputId);
            const dropdown = document.getElementById(dropdownId);
            const codeInput = document.getElementById(codeInputId);

            if (!input || !dropdown || !codeInput) return;

            input.addEventListener('click', function() {
                currentActiveInput = inputId;
                input.readOnly = false;
                input.focus();
                showAllCities(dropdownId);
            });

            input.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                filterCities(query, dropdownId);
            });

            input.addEventListener('blur', function() {
                // Delay hiding dropdown to allow for clicks
                setTimeout(() => {
                    dropdown.classList.remove('show');
                    if (!this.value) {
                        this.readOnly = true;
                    }
                }, 200);
            });

            // Handle city selection
            dropdown.addEventListener('click', function(e) {
                if (e.target.closest('.city-option')) {
                    const option = e.target.closest('.city-option');
                    const cityName = option.querySelector('.city-name').textContent;
                    const cityCode = option.querySelector('.city-code').textContent;

                    input.value = `${cityName} (${cityCode})`;
                    codeInput.value = cityCode;
                    input.readOnly = true;
                    dropdown.classList.remove('show');

                    // Check if both cities are selected to enable search button
                    checkFormValidity();
                }
            });
        }

        function showAllCities(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            if (!dropdown) return;

            dropdown.innerHTML = '';

            if (cities.length === 0) {
                dropdown.innerHTML = '<div class="no-results">No cities available</div>';
            } else {
                cities.forEach(city => {
                    const option = createCityOption(city);
                    dropdown.appendChild(option);
                });
            }

            dropdown.classList.add('show');
        }

        function filterCities(query, dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            if (!dropdown) return;

            dropdown.innerHTML = '';

            const filteredCities = cities.filter(city =>
                city.city_name.toLowerCase().includes(query) ||
                city.city_code.toLowerCase().includes(query)
            );

            if (filteredCities.length === 0) {
                dropdown.innerHTML = '<div class="no-results">No cities found</div>';
            } else {
                filteredCities.forEach(city => {
                    const option = createCityOption(city);
                    dropdown.appendChild(option);
                });
            }

            dropdown.classList.add('show');
        }

        function createCityOption(city) {
            const option = document.createElement('div');
            option.className = 'city-option';
            option.innerHTML = `
                <div>
                    <div class="city-name">${city.city_name}</div>
                </div>
                <div class="city-code">${city.city_code}</div>
            `;
            return option;
        }

        function checkFormValidity() {
            const fromCity = document.getElementById('fromCityCode');
            const toCity = document.getElementById('toCityCode');
            const searchBtn = document.getElementById('searchBtn');

            if (!fromCity || !toCity || !searchBtn) return;

            if (fromCity.value && toCity.value && fromCity.value !== toCity.value) {
                searchBtn.disabled = false;
            } else {
                searchBtn.disabled = true;
            }
        }

        // Setup city search for both inputs only if cities are available
        if (cities.length > 0) {
            setupCitySearch('fromCity', 'fromDropdown', 'fromCityCode');
            setupCitySearch('toCity', 'toDropdown', 'toCityCode');
        }

        // Swap cities functionality
        const swapBtn = document.getElementById('swapBtn');
        if (swapBtn) {
            swapBtn.addEventListener('click', function() {
                const fromInput = document.getElementById('fromCity');
                const toInput = document.getElementById('toCity');
                const fromCode = document.getElementById('fromCityCode');
                const toCode = document.getElementById('toCityCode');

                if (!fromInput || !toInput || !fromCode || !toCode) return;

                // Swap values
                const tempValue = fromInput.value;
                const tempCode = fromCode.value;

                fromInput.value = toInput.value;
                fromCode.value = toCode.value;

                toInput.value = tempValue;
                toCode.value = tempCode;

                // Add animation effect
                this.style.transform = 'translate(-50%, -50%) scale(1.2) rotate(180deg)';
                setTimeout(() => {
                    this.style.transform = 'translate(-50%, -50%) scale(1) rotate(0deg)';
                }, 300);

                checkFormValidity();
            });
        }

        // Animate return date field based on trip type
        const tripType = document.getElementById('tripType');
        if (tripType) {
            tripType.addEventListener('change', function() {
                var returnCol = document.getElementById('returnDateCol');
                if (!returnCol) return;

                if (this.value === 'oneway') {
                    returnCol.style.opacity = '0.5';
                    returnCol.style.transform = 'scale(0.95)';
                    returnCol.querySelector('input').disabled = true;
                    returnCol.querySelector('input').required = false;
                } else {
                    returnCol.style.opacity = '1';
                    returnCol.style.transform = 'scale(1)';
                    returnCol.querySelector('input').disabled = false;
                    returnCol.querySelector('input').required = true;
                }
            });
        }

        // Modal functionality
        function selectFlightFromModal(flight) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('availableFlightsModal'));
            modal.hide();

            // Fill form with selected flight data
            const fromInput = document.getElementById('fromCity');
            const toInput = document.getElementById('toCity');
            const fromCode = document.getElementById('fromCityCode');
            const toCode = document.getElementById('toCityCode');
            const departureDate = document.getElementById('departureDate');

            if (fromInput && toInput && fromCode && toCode && departureDate) {
                fromInput.value = `${flight.origin} (${flight.origin})`;
                fromCode.value = flight.origin;
                toInput.value = `${flight.destination} (${flight.destination})`;
                toCode.value = flight.destination;
                departureDate.value = flight.flight_date;

                fromInput.readOnly = true;
                toInput.readOnly = true;

                checkFormValidity();

                // Scroll to form
                document.querySelector('.booking-form').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }

        // Modal filters
        function initializeModalFilters() {
            if (!upcomingFlights || upcomingFlights.length === 0) return;

            // Populate route filter
            const routeFilter = document.getElementById('routeFilter');
            const airlineFilter = document.getElementById('airlineFilter');

            if (routeFilter && airlineFilter) {
                const routes = [...new Set(upcomingFlights.map(f => `${f.origin} → ${f.destination}`))];
                const airlines = [...new Set(upcomingFlights.map(f => f.airline))];

                routes.forEach(route => {
                    const option = document.createElement('option');
                    option.value = route.replace(' → ', '-');
                    option.textContent = route;
                    routeFilter.appendChild(option);
                });

                airlines.forEach(airline => {
                    const option = document.createElement('option');
                    option.value = airline;
                    option.textContent = airline;
                    airlineFilter.appendChild(option);
                });

                // Add filter event listeners
                [routeFilter, airlineFilter, document.getElementById('modalClassFilter'), document.getElementById('modalPassengersFilter')].forEach(filter => {
                    if (filter) {
                        filter.addEventListener('change', filterModalFlights);
                    }
                });
            }
        }

        function filterModalFlights() {
            const routeFilter = document.getElementById('routeFilter').value;
            const airlineFilter = document.getElementById('airlineFilter').value;
            const classFilter = document.getElementById('modalClassFilter').value;
            const passengersFilter = parseInt(document.getElementById('modalPassengersFilter').value);

            const flightCards = document.querySelectorAll('.available-flight-card');

            flightCards.forEach(card => {
                let show = true;

                // Route filter
                if (routeFilter && card.dataset.route !== routeFilter) {
                    show = false;
                }

                // Airline filter
                if (airlineFilter && card.dataset.airline !== airlineFilter) {
                    show = false;
                }

                // Show/hide card
                card.style.display = show ? 'block' : 'none';
            });
        }

        // Form submission with loading animation
        const bookingForm = document.querySelector('.booking-form');
        if (bookingForm) {
            bookingForm.addEventListener('submit', function(e) {
                const btn = document.getElementById('searchBtn');
                if (btn) {
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<span class="loading-spinner"></span> Searching...';
                    btn.disabled = true;
                }
                // Allow form to submit normally
            });
        }

        // BETTER: Handle loading state on form submission, not button click
        document.querySelectorAll('form[action="checkout.php"]').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const btn = this.querySelector('.select-flight-btn');
                if (btn) {
                    btn.innerHTML = '<span class="loading-spinner"></span> Proceeding to Checkout...';
                    btn.disabled = true;
                    feather.replace();
                }
                // Form will submit normally after this
            });
        });

        // Remove the old button click handler completely
        // document.querySelectorAll('.select-flight-btn').forEach... <- DELETE THIS SECTION

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        const departureInput = document.querySelector('input[name="departure"]');
        const returnInput = document.querySelector('input[name="return"]');

        if (departureInput) departureInput.setAttribute('min', today);
        if (returnInput) returnInput.setAttribute('min', today);

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

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.city-search-container')) {
                document.querySelectorAll('.city-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });

                // Make inputs readonly if they don't have values
                document.querySelectorAll('.city-search-input').forEach(input => {
                    if (!input.value) {
                        input.readOnly = true;
                    }
                });
            }
        });

        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';

        // Add entrance animations on scroll
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

        // Observe form elements
        document.querySelectorAll('.form-control, .form-select, .city-search-input').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(element);
        });

        // Initialize modal filters when modal is shown
        const availableFlightsModal = document.getElementById('availableFlightsModal');
        if (availableFlightsModal) {
            availableFlightsModal.addEventListener('shown.bs.modal', function() {
                initializeModalFilters();
                feather.replace();
            });
        }

        // Re-initialize feather icons after dynamic content
        setTimeout(() => {
            feather.replace();
        }, 100);

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

        console.log('Flight booking page loaded successfully');
        console.log('Available cities:', cities.length);
        console.log('Upcoming flights:', upcomingFlights.length);
        console.log('Form submission fixed - will POST directly to checkout.php');
    </script>

</body>

</html>
</qodoArtifact>