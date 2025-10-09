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
    die("Booking not found or not confirmed");
}

// Generate a professional ticket page that can be printed as PDF
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Flight Ticket - <?php echo htmlspecialchars($booking['booking_ref']); ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background: #fff;
            color: #333;
        }
        .ticket-container {
            max-width: 800px;
            margin: 0 auto;
            border: 3px solid #38a169;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .ticket-header {
            background: linear-gradient(135deg, #38a169, #2d5a3d);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 7px 7px 0 0;
        }
        .ticket-header h1 {
            margin: 0;
            font-size: 2.2em;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .ticket-header h2 {
            margin: 10px 0 0 0;
            font-size: 1.3em;
            opacity: 0.9;
        }
        .ticket-header .booking-ref {
            margin-top: 15px;
            font-size: 1.1em;
            font-weight: 600;
            letter-spacing: 3px;
        }
        .ticket-content {
            padding: 30px;
        }
        .passenger-info, .flight-info {
            margin-bottom: 30px;
        }
        .section-title {
            color: #38a169;
            font-size: 1.4em;
            margin-bottom: 20px;
            border-bottom: 2px solid #38a169;
            padding-bottom: 5px;
            font-weight: bold;
        }
        .info-row {
            display: flex;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            flex: 1;
            font-weight: 600;
            color: #555;
        }
        .info-value {
            flex: 2;
            color: #333;
        }
        .flight-route {
            background: linear-gradient(135deg, #f0f8ff, #e6f3ff);
            padding: 25px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #38a169;
        }
        .route-display {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 40px;
            margin-bottom: 15px;
        }
        .route-point {
            text-align: center;
        }
        .route-code {
            font-size: 2em;
            font-weight: bold;
            color: #38a169;
            margin-bottom: 5px;
        }
        .route-city {
            color: #666;
            font-size: 0.9em;
        }
        .route-arrow {
            font-size: 1.8em;
            color: #38a169;
        }
        .flight-datetime {
            font-size: 1.1em;
            color: #333;
            margin-top: 10px;
        }
        .barcode {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .barcode-text {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #333;
        }
        .ticket-footer {
            background: #f8f9fa;
            padding: 20px 30px;
            border-top: 1px solid #ddd;
            border-radius: 0 0 7px 7px;
        }
        .footer-text {
            text-align: center;
            color: #666;
            font-size: 0.9em;
            line-height: 1.4;
        }
        .important-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .notice-title {
            color: #856404;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #38a169;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(56, 161, 105, 0.3);
        }
        .print-btn:hover {
            background: #2d5a3d;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(56, 161, 105, 0.4);
        }
        @media print {
            .print-btn { display: none !important; }
            body { 
                margin: 0; 
                padding: 10px;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .ticket-container { 
                box-shadow: 0 0 20px rgba(0,0,0,0.1) !important;
                border: 3px solid #38a169 !important;
                page-break-inside: avoid;
            }
            .ticket-header {
                background: linear-gradient(135deg, #38a169, #2d5a3d) !important;
                color: white !important;
            }
            .flight-route {
                background: linear-gradient(135deg, #f0f8ff, #e6f3ff) !important;
                border: 2px solid #38a169 !important;
            }
            .important-notice {
                background: #fff3cd !important;
                border: 1px solid #ffeaa7 !important;
            }
            .ticket-footer {
                background: #f8f9fa !important;
            }
            .section-title {
                color: #38a169 !important;
                border-bottom: 2px solid #38a169 !important;
            }
            .route-code, .route-arrow {
                color: #38a169 !important;
            }
            .barcode {
                background: #f9f9f9 !important;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <h1>SPEED OF LIGHT AIRLINES</h1>
            <h2>Electronic Flight Ticket</h2>
            <div class="booking-ref">Booking Reference: <?php echo htmlspecialchars($booking['booking_ref']); ?></div>
        </div>

        <div class="ticket-content">
            <div class="passenger-info">
                <div class="section-title">Passenger Information</div>
                <div class="info-row">
                    <div class="info-label">Full Name:</div>
                    <div class="info-value"><?php echo htmlspecialchars($booking['passenger_name']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?php echo htmlspecialchars($booking['passenger_email']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone:</div>
                    <div class="info-value"><?php echo htmlspecialchars($booking['passenger_phone'] ?: 'Not provided'); ?></div>
                </div>
            </div>

            <div class="flight-info">
                <div class="section-title">Flight Information</div>

                <div class="flight-route">
                    <div class="route-display">
                        <div class="route-point">
                            <div class="route-code"><?php echo htmlspecialchars($booking['origin']); ?></div>
                            <div class="route-city">Departure</div>
                        </div>
                        <div class="route-arrow">→</div>
                        <div class="route-point">
                            <div class="route-code"><?php echo htmlspecialchars($booking['destination']); ?></div>
                            <div class="route-city">Arrival</div>
                        </div>
                    </div>
                    <div class="flight-datetime">
                        <?php echo date('l, F j, Y', strtotime($booking['flight_date'])); ?><br>
                        Departure: <?php echo date('H:i', strtotime($booking['departure_time'])); ?> |
                        Arrival: <?php echo date('H:i', strtotime($booking['arrival_time'])); ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Airline:</div>
                    <div class="info-value"><?php echo htmlspecialchars($booking['airline']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Flight Number:</div>
                    <div class="info-value"><?php echo htmlspecialchars($booking['flight_no']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Aircraft:</div>
                    <div class="info-value"><?php echo htmlspecialchars($booking['aircraft']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Class:</div>
                    <div class="info-value"><?php echo htmlspecialchars($booking['class']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Passengers:</div>
                    <div class="info-value"><?php echo $booking['passengers']; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Total Amount:</div>
                    <div class="info-value">₦<?php echo number_format($booking['total_amount'], 2); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Payment Status:</div>
                    <div class="info-value" style="color: #28a745; font-weight: bold;">✓ Confirmed</div>
                </div>
            </div>

            <div class="barcode">
                <div class="barcode-text"><?php echo htmlspecialchars($booking['booking_ref']); ?></div>
            </div>
        </div>

        <div class="ticket-footer">
            <div class="important-notice">
                <div class="notice-title">Important Notice:</div>
                <div class="notice-text">
                    Please arrive at the airport at least 2 hours before departure. Bring valid identification.
                    This is your official e-ticket. Please save or print for your records.
                </div>
            </div>
            <div class="footer-text">
                <strong>Speed of Light Airlines</strong><br>
                Customer Service: +234-XXX-XXXX | Website: www.speedoflightairlines.com<br>
                Ticket Generated: <?php echo date('F j, Y \\a\\t H:i'); ?><br>
                This ticket is non-transferable and valid only for the named passenger.
            </div>
        </div>
    </div>

    <button class="print-btn" onclick="window.print()">🖨️ Download/Save as PDF</button>

    <script>
        // Auto-print after page loads (optional)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 1000);
        // };
    </script>
</body>
</html>