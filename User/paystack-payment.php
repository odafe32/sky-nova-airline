<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if we have pending booking data
if (!isset($_SESSION['pending_booking'])) {
    header("Location: checkout.php?error=no_pending_booking");
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'airlines';
$username = 'root';
$password = '';

// Paystack Configuration (TEST MODE)
$paystack_secret_key = 'sk_test_2f7724ac9e631c232ad0aacb344e6c8897019f70';
$paystack_public_key = 'pk_test_e6bef1f3afea98869309108d617345c0d64c6e6e';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];
$pending_booking = $_SESSION['pending_booking'];

// Get user information
$stmt = $pdo->prepare("SELECT user_id, full_name, email, phone FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get booking information
$stmt = $pdo->prepare("
    SELECT b.*, f.airline, f.flight_no, f.origin, f.destination, f.flight_date,
           f.departure_time, f.arrival_time
    FROM bookings b
    JOIN flights f ON b.flight_id = f.flight_id
    WHERE b.booking_id = ? AND b.user_id = ?
");
$stmt->execute([$pending_booking['booking_id'], $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: checkout.php?error=booking_not_found");
    exit();
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

// Initialize payment with Paystack
$callback_url = "http://localhost/airlines/User/payment-callback.php?reference=" . urlencode($pending_booking['booking_ref']);
$paystack_response = initializePaystackPayment(
    $pending_booking['email'],
    $pending_booking['amount'],
    $pending_booking['booking_ref'],
    $callback_url,
    $paystack_secret_key
);

$error_message = '';
$payment_url = '';
if ($paystack_response && $paystack_response['status'] === true) {
    $payment_url = $paystack_response['data']['authorization_url'];

    // Store payment reference in database
    $stmt = $pdo->prepare("
        UPDATE bookings SET 
            payment_reference = ?,
            updated_at = NOW()
        WHERE booking_id = ?
    ");
    $stmt->execute([$paystack_response['data']['reference'], $pending_booking['booking_id']]);
} else {
    $error_message = "Payment initialization failed: " . ($paystack_response['message'] ?? 'Unknown error');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Payment Processing | Speed of Light Airlines</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="icon" href="assets/images/airline-favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Feather Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #38a169 0%, #38a169 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .payment-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 90%;
            text-align: center;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .payment-header {
            margin-bottom: 30px;
        }

        .payment-header h1 {
            color: #38a169;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .payment-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .booking-summary {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 25px;
            margin: 25px 0;
            text-align: left;
        }

        .booking-summary h5 {
            color: #38a169;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 1.1rem;
            color: #38a169;
        }

        .payment-btn {
            background: linear-gradient(135deg, #38a169 0%, #38a169 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 15px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 20px 10px;
        }

        .payment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 156, 104, 0.4);
            color: #fff;
        }

        .back-btn {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }

        .back-btn:hover {
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        }

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

        .paystack-logo {
            width: 120px;
            margin: 20px 0;
        }

        .security-info {
            background: rgba(33, 150, 243, 0.1);
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #1976d2;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee, #fdd);
            color: #c53030;
            border-left: 4px solid #e53e3e;
        }
    </style>
</head>

<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1><i data-feather="credit-card"></i> Payment Processing</h1>
            <p>Complete your flight booking payment securely</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i data-feather="alert-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="booking-summary">
            <h5><i data-feather="send"></i> Booking Summary</h5>
            <div class="summary-row">
                <span>Flight:</span>
                <span><?php echo htmlspecialchars($booking['airline'] . ' ' . $booking['flight_no']); ?></span>
            </div>
            <div class="summary-row">
                <span>Route:</span>
                <span><?php echo htmlspecialchars($booking['origin'] . ' → ' . $booking['destination']); ?></span>
            </div>
            <div class="summary-row">
                <span>Date:</span>
                <span><?php echo date('D, d M Y', strtotime($booking['flight_date'])); ?></span>
            </div>
            <div class="summary-row">
                <span>Time:</span>
                <span><?php echo date('H:i', strtotime($booking['departure_time'])); ?> - <?php echo date('H:i', strtotime($booking['arrival_time'])); ?></span>
            </div>
            <div class="summary-row">
                <span>Passengers:</span>
                <span><?php echo $booking['passengers']; ?> (<?php echo htmlspecialchars($booking['class']); ?>)</span>
            </div>
            <div class="summary-row">
                <span>Booking Reference:</span>
                <span><?php echo htmlspecialchars($booking['booking_ref']); ?></span>
            </div>
            <div class="summary-row">
                <span><strong>Total Amount:</strong></span>
                <span><strong>₦<?php echo number_format($booking['total_amount'], 2); ?></strong></span>
            </div>
        </div>

        <img src="https://paystack.com/assets/img/logo/paystack-logo-blue.png" alt="Paystack" class="paystack-logo">

        <div class="security-info">
            <i data-feather="shield"></i>
            <span>Your payment is secured with 256-bit SSL encryption. Test mode enabled - no real money will be charged.</span>
        </div>

        <div class="d-flex justify-content-center flex-wrap">
            <a href="checkout.php" class="payment-btn back-btn">
                <i data-feather="arrow-left"></i> Back to Checkout
            </a>

            <?php if ($payment_url): ?>
                <a href="<?php echo htmlspecialchars($payment_url); ?>" class="payment-btn" id="payBtn">
                    <i data-feather="credit-card"></i> Pay with Paystack
                </a>
            <?php else: ?>
                <button class="payment-btn" disabled>
                    <i data-feather="x-circle"></i> Payment Unavailable
                </button>
            <?php endif; ?>
        </div>

        <p class="mt-3 text-muted">
            <small>By proceeding, you agree to our terms and conditions. This is a test transaction.</small>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();

        // Add loading animation to pay button
        document.getElementById('payBtn')?.addEventListener('click', function() {
            this.innerHTML = '<span class="loading-spinner"></span> Redirecting to Paystack...';
            this.style.pointerEvents = 'none';
        });

        // Auto-redirect after 3 seconds if payment URL exists
        <?php if ($payment_url): ?>
            setTimeout(() => {
                const payBtn = document.getElementById('payBtn');
                if (payBtn) {
                    payBtn.click();
                }
            }, 3000);
        <?php endif; ?>
    </script>
</body>

</html>