<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit();
}

$host = 'localhost';
$dbname = 'airlines';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = intval($_GET['booking_id']);

try {
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            f.airline,
            f.flight_no,
            f.origin,
            f.destination,
            f.flight_date,
            f.departure_time,
            f.arrival_time,
            f.aircraft,
            u.full_name as passenger_name,
            u.email as passenger_email,
            u.phone as passenger_phone
        FROM bookings b
        JOIN flights f ON b.flight_id = f.flight_id
        JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        $booking['flight_date'] = date('l, F j, Y', strtotime($booking['flight_date']));
        $booking['departure_time'] = date('H:i', strtotime($booking['departure_time']));
        $booking['arrival_time'] = date('H:i', strtotime($booking['arrival_time']));
        $booking['created_at'] = date('F j, Y H:i', strtotime($booking['created_at']));
        
        echo json_encode([
            'success' => true,
            'booking' => $booking
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching booking details'
    ]);
}
?>