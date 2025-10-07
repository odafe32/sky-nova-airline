<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'airlines';
$username = 'root';
$password = '';

// Paystack Configuration (TEST MODE)
$paystack_secret_key = 'sk_test_2f7724ac9e631c232ad0aacb344e6c8897019f70';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to verify Paystack payment
function verifyPaystackPayment($reference, $secret_key) {
    $url = "https://api.paystack.co/transaction/verify/" . $reference;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer " . $secret_key,
        "Cache-Control: no-cache",
    ));
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

// Get payment reference from URL
$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    header("Location: payment-failed.php?error=no_reference");
    exit();
}

// Verify payment with Paystack
$verification = verifyPaystackPayment($reference, $paystack_secret_key);

if (!$verification || !$verification['status']) {
    header("Location: payment-failed.php?error=verification_failed");
    exit();
}

$payment_data = $verification['data'];

// Check if payment was successful
if ($payment_data['status'] !== 'success') {
    header("Location: payment-failed.php?error=payment_failed&reference=" . urlencode($reference));
    exit();
}

try {
    $pdo->beginTransaction();

    // Find the booking by reference
    $stmt = $pdo->prepare("
        SELECT booking_id, user_id, flight_id, passengers, class, total_amount 
        FROM bookings 
        WHERE booking_ref = ? AND status = 'Pending'
    ");
    $stmt->execute([$reference]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception("Booking not found or already processed");
    }

    // Verify payment amount matches booking amount
    $paid_amount = $payment_data['amount'] / 100; // Convert from kobo to naira
    if ($paid_amount != $booking['total_amount']) {
        throw new Exception("Payment amount mismatch");
    }

    // Update booking status to Confirmed
    $stmt = $pdo->prepare("
        UPDATE bookings SET
            status = 'Confirmed',
            payment_status = 'Paid',
            paystack_reference = ?,
            payment_method = 'Paystack',
            paid_amount = ?,
            payment_date = NOW(),
            updated_at = NOW()
        WHERE booking_id = ?
    ");
    $stmt->execute([
        $payment_data['reference'],
        $paid_amount,
        $booking['booking_id']
    ]);

    // Update seat availability
    $stmt = $pdo->prepare("
        SELECT economy_seats, business_seats, first_class_seats
        FROM flights WHERE flight_id = ?
    ");
    $stmt->execute([$booking['flight_id']]);
    $seat_data = $stmt->fetch(PDO::FETCH_ASSOC);

    $seat_column = strtolower($booking['class']) . '_seats';
    $current_seats = $seat_data[$seat_column];
    $new_seats = $current_seats - $booking['passengers'];

    $stmt = $pdo->prepare("UPDATE flights SET {$seat_column} = ? WHERE flight_id = ?");
    $stmt->execute([$new_seats, $booking['flight_id']]);

    // Update cart status if booking came from cart
    if (isset($_SESSION['pending_booking']['cart_id'])) {
        $stmt = $pdo->prepare("UPDATE cart SET status = 'booked' WHERE cart_id = ?");
        $stmt->execute([$_SESSION['pending_booking']['cart_id']]);
    }

    // Insert payment record
    $stmt = $pdo->prepare("
        INSERT INTO payments (booking_id, user_id, amount, payment_method, payment_reference,
                            payment_status, transaction_id, created_at)
        VALUES (?, ?, ?, 'Paystack', ?, 'Completed', ?, NOW())
    ");
    $stmt->execute([
        $booking['booking_id'],
        $booking['user_id'],
        $paid_amount,
        $payment_data['reference'],
        $payment_data['id']
    ]);

    $pdo->commit();

    // Clear pending booking session
    unset($_SESSION['pending_booking']);
    unset($_SESSION['booking_details']);
    unset($_SESSION['flight_info']);

    // Redirect to success page
    header("Location: payment-success.php?reference=" . urlencode($reference));
    exit();

} catch (Exception $e) {
    $pdo->rollback();
    error_log("Payment processing error: " . $e->getMessage());
    header("Location: payment-failed.php?error=processing_failed&reference=" . urlencode($reference));
    exit();
}
?>