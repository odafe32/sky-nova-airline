<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_POST['booking_id']) || empty($_POST['booking_id'])) {
    die("Booking ID is required");
}

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
$booking_id = intval($_POST['booking_id']);

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
    WHERE b.booking_id = ? AND b.user_id = ? AND b.status = 'Confirmed'
");

$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("Booking not found");
}

if (strtotime($booking['flight_date']) >= strtotime('today')) {
    die("Ticket download is only available for completed flights");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Flight Ticket - <?php echo htmlspecialchars($booking['booking_ref']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .ticket { max-width: 800px; margin: 0 auto; border: 2px solid #00539C; }
        .header { background: #00539C; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .section { margin-bottom: 20px; }
        .section h3 { color: #00539C; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .info { display: flex; justify-content: space-between; margin: 10px 0; }
        .print-btn { position: fixed; top: 20px; right: 20px; background: #00539C; color: white; border: none; padding: 10px 20px; cursor: pointer; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print Ticket</button>
    
    <div class="ticket">
        <div class="header">
            <h1>SPEED OF LIGHT AIRLINES</h1>
            <h3>ELECTRONIC TICKET</h3>
            <p>Booking Reference: <?php echo htmlspecialchars($booking['booking_ref']); ?></p>
        </div>
        
        <div class="content">
            <div class="section">
                <h3>Passenger Information</h3>
                <div class="info"><span>Name:</span> <span><?php echo htmlspecialchars($booking['passenger_name']); ?></span></div>
                <div class="info"><span>Email:</span> <span><?php echo htmlspecialchars($booking['passenger_email']); ?></span></div>
                <div class="info"><span>Phone:</span> <span><?php echo htmlspecialchars($booking['passenger_phone'] ?: 'Not provided'); ?></span></div>
            </div>
            
            <div class="section">
                <h3>Flight Information</h3>
                <div class="info"><span>Airline:</span> <span><?php echo htmlspecialchars($booking['airline']); ?></span></div>
                <div class="info"><span>Flight:</span> <span><?php echo htmlspecialchars($booking['flight_no']); ?></span></div>
                <div class="info"><span>Route:</span> <span><?php echo htmlspecialchars($booking['origin'] . ' → ' . $booking['destination']); ?></span></div>
                <div class="info"><span>Date:</span> <span><?php echo date('F j, Y', strtotime($booking['flight_date'])); ?></span></div>
                <div class="info"><span>Departure:</span> <span><?php echo date('H:i', strtotime($booking['departure_time'])); ?></span></div>
                <div class="info"><span>Arrival:</span> <span><?php echo date('H:i', strtotime($booking['arrival_time'])); ?></span></div>
                <div class="info"><span>Class:</span> <span><?php echo htmlspecialchars($booking['class']); ?></span></div>
                <div class="info"><span>Passengers:</span> <span><?php echo $booking['passengers']; ?></span></div>
                <div class="info"><span>Amount:</span> <span>₦<?php echo number_format($booking['total_amount'], 2); ?></span></div>
            </div>
            
            <div class="section">
                <h3>Barcode</h3>
                <p style="text-align: center; font-family: monospace; font-size: 18px;">||||| <?php echo htmlspecialchars($booking['booking_ref']); ?> |||||</p>
            </div>
        </div>
    </div>
    
    <script>
        window.onafterprint = function() { window.close(); }
    </script>
</body>
</html>