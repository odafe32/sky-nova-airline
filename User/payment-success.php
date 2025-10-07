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
$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    header("Location: dashboard.php");
    exit();
}

// Get booking details
$stmt = $pdo->prepare("
    SELECT b.*, f.airline, f.flight_no, f.origin, f.destination, f.flight_date,
           f.departure_time, f.arrival_time, f.aircraft
    FROM bookings b
    JOIN flights f ON b.flight_id = f.flight_id
    WHERE b.booking_ref = ? AND b.user_id = ? AND b.status = 'Confirmed'
");
$stmt->execute([$reference, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: dashboard.php?error=booking_not_found");
    exit();
}

// Get user information
$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Successful | Speed of Light Airlines</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
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
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            padding: 20px 0;
        }

        .success-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
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

        .success-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #48bb78, #38a169);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: bounce 1s ease-out;
        }

        .success-icon i {
            color: white;
            font-size: 2.5rem;
        }

        .success-header h1 {
            color: #48bb78;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .success-header p {
            color: #666;
            font-size: 1.2rem;
        }

        .booking-details {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 30px;
            margin: 30px 0;
        }

        .booking-details h3 {
            color: #38a169;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: #666;
        }

        .detail-value {
            font-weight: 600;
            color: #333;
        }

        .flight-route {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.1), rgba(0, 51, 102, 0.1));
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .route-display {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
            margin: 15px 0;
        }

        .route-point {
            text-align: center;
        }

        .route-code {
            font-size: 1.8rem;
            font-weight: 700;
            color: #38a169;
        }

        .route-city {
            color: #666;
            font-size: 0.9rem;
        }

        .route-arrow {
            color: #38a169;
            font-size: 1.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn-custom {
            border: none;
            border-radius: 12px;
            font-weight: 600;
            padding: 12px 30px;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #38a169 0%, #38a169 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
        }

        .btn-secondary-custom {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
        }

        .btn-outline-custom {
            background: transparent;
            color: #38a169;
            border: 2px solid #38a169;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            color: #fff;
        }

        .btn-primary-custom:hover {
            box-shadow: 0 8px 25px rgba(0, 83, 156, 0.4);
        }

        .btn-secondary-custom:hover {
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.4);
        }

        .btn-outline-custom:hover {
            background: #38a169;
            color: #fff;
        }

        .confirmation-number {
            background: linear-gradient(135deg, #e3fcec, #c8f7c5);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border-left: 4px solid #48bb78;
        }

        .confirmation-number h4 {
            color: #1b5e20;
            margin-bottom: 10px;
        }

        .confirmation-code {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1b5e20;
            letter-spacing: 2px;
        }

        .next-steps {
            background: rgba(33, 150, 243, 0.1);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }

        .next-steps h5 {
            color: #1976d2;
            margin-bottom: 15px;
        }

        .next-steps ul {
            color: #1976d2;
            margin: 0;
            padding-left: 20px;
        }

        .next-steps li {
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .success-container {
                margin: 0 15px;
                padding: 25px;
            }

            .route-display {
                flex-direction: column;
                gap: 15px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <div class="success-header">
                <div class="success-icon">
                    <i data-feather="check"></i>
                </div>
                <h1>Payment Successful!</h1>
                <p>Your flight has been booked successfully</p>
            </div>

            <div class="confirmation-number">
                <h4><i data-feather="bookmark"></i> Booking Confirmation</h4>
                <div class="confirmation-code"><?php echo htmlspecialchars($booking['booking_ref']); ?></div>
                <small class="text-muted">Please save this reference number for your records</small>
            </div>

            <div class="booking-details">
                <h3><i data-feather="send"></i> Flight Details</h3>
                
                <div class="flight-route">
                    <div class="route-display">
                        <div class="route-point">
                            <div class="route-code"><?php echo htmlspecialchars($booking['origin']); ?></div>
                            <div class="route-city">Departure</div>
                        </div>
                        <div class="route-arrow">
                            <i data-feather="arrow-right"></i>
                        </div>
                        <div class="route-point">
                            <div class="route-code"><?php echo htmlspecialchars($booking['destination']); ?></div>
                            <div class="route-city">Arrival</div>
                        </div>
                    </div>
                    <div class="text-muted">
                        <?php echo date('D, d M Y', strtotime($booking['flight_date'])); ?> | 
                        <?php echo date('H:i', strtotime($booking['departure_time'])); ?> - 
                        <?php echo date('H:i', strtotime($booking['arrival_time'])); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Airline:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['airline']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Flight Number:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['flight_no']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Aircraft:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['aircraft']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Passenger(s):</span>
                    <span class="detail-value"><?php echo $booking['passengers']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Class:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['class']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Passenger Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['passenger_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Paid:</span>
                    <span class="detail-value">₦<?php echo number_format($booking['total_amount'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Status:</span>
                    <span class="detail-value" style="color: #48bb78;">✓ Confirmed</span>
                </div>
            </div>

            <div class="next-steps">
                <h5><i data-feather="info"></i> What's Next?</h5>
                <ul>
                    <li>Your e-ticket has been sent to <?php echo htmlspecialchars($booking['passenger_email']); ?></li>
                    <li>Please arrive at the airport at least 2 hours before departure</li>
                    <li>Bring a valid ID that matches the passenger name on the booking</li>
                    <li>Check-in online 24 hours before your flight</li>
                </ul>
            </div>

            <div class="action-buttons">
                <a href="my-bookings.php" class="btn-custom btn-primary-custom">
                    <i data-feather="calendar"></i> View My Bookings
                </a>
                <a href="book-flight.php" class="btn-custom btn-secondary-custom">
                    <i data-feather="plus"></i> Book Another Flight
                </a>
                <a href="dashboard.php" class="btn-custom btn-outline-custom">
                    <i data-feather="home"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();

        // Confetti animation (optional)
        function createConfetti() {
            const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57'];
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-10px';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.pointerEvents = 'none';
                confetti.style.zIndex = '9999';
                confetti.style.borderRadius = '50%';
                document.body.appendChild(confetti);

                const animation = confetti.animate([
                    { transform: 'translateY(-10px) rotateZ(0deg)', opacity: 1 },
                    { transform: `translateY(100vh) rotateZ(360deg)`, opacity: 0 }
                ], {
                    duration: Math.random() * 2000 + 1000,
                    easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
                });

                animation.onfinish = () => confetti.remove();
            }
        }

        // Trigger confetti on page load
        window.addEventListener('load', () => {
            setTimeout(createConfetti, 500);
        });
    </script>
</body>
</html>