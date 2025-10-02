<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if booking reference exists
if (!isset($_GET['booking_ref'])) {
    header("Location: book-flight.php?error=no_booking_reference");
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
$booking_ref = trim($_GET['booking_ref']);

// Get user information
$stmt = $pdo->prepare("SELECT user_id, full_name, email, phone, avatar FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get cart count for navbar
$stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM cart WHERE user_id = ? AND status = 'active' AND expires_at > NOW()");
$stmt->execute([$user_id]);
$cart_data = $stmt->fetch(PDO::FETCH_ASSOC);
$cart_count = $cart_data['cart_count'];

// Get booking details
$stmt = $pdo->prepare("
    SELECT b.*, f.airline, f.flight_no, f.origin, f.destination, f.flight_date,
           f.departure_time, f.arrival_time, f.aircraft
    FROM bookings b
    JOIN flights f ON b.flight_id = f.flight_id
    WHERE b.booking_ref = ? AND b.user_id = ?
");
$stmt->execute([$booking_ref, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: book-flight.php?error=booking_not_found");
    exit();
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
    <title>Booking Confirmed | Speed of Light Airlines</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Booking Confirmation - Speed of Light Airlines" />
    <meta name="keywords" content="airline, booking, confirmation, success">
    <meta name="author" content="Speed of Light Airlines" />
    <link rel="icon" href="assets/images/airline-favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s ease-in-out infinite;
        }
        
        .particle:nth-child(1) { width: 80px; height: 80px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 60px; height: 60px; left: 30%; animation-delay: 3s; }
        .particle:nth-child(3) { width: 100px; height: 100px; left: 50%; animation-delay: 6s; }
        .particle:nth-child(4) { width: 40px; height: 40px; left: 70%; animation-delay: 9s; }
        .particle:nth-child(5) { width: 70px; height: 70px; left: 90%; animation-delay: 12s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.3; }
            50% { transform: translateY(-200px) rotate(180deg); opacity: 0.1; }
        }
        
        .navbar {
            background: rgba(0, 83, 156, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 83, 156, 0.3);
        }
        
        .navbar-brand {
            color: #fff !important;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 1.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
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
        }
        
        .cart-icon {
            color: #fff;
            font-size: 1.3rem;
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
        
        .success-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 50px 20px;
            position: relative;
            z-index: 10;
        }
        
        .success-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
            max-width: 800px;
            width: 100%;
            text-align: center;
            animation: successBounce 1s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        @keyframes successBounce {
            0% { opacity: 0; transform: scale(0.3) translateY(-100px); }
            50% { opacity: 1; transform: scale(1.05) translateY(0); }
            70% { transform: scale(0.95); }
            100% { transform: scale(1); }
        }
        
        .success-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #48bb78, #38a169);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px auto;
            animation: checkmarkAnimation 2s ease-in-out;
            box-shadow: 0 10px 30px rgba(72, 187, 120, 0.4);
        }
        
        .success-icon i {
            color: #fff;
            font-size: 3rem;
            animation: checkmarkPop 0.5s ease-in-out 1.5s both;
        }
        
        @keyframes checkmarkAnimation {
            0% { transform: scale(0) rotate(-180deg); }
            50% { transform: scale(1.2) rotate(0deg); }
            100% { transform: scale(1) rotate(0deg); }
        }
        
        @keyframes checkmarkPop {
            0% { opacity: 0; transform: scale(0); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        .success-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
            animation: fadeInUp 1s ease-out 0.5s both;
        }
        
        .success-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 40px;
            animation: fadeInUp 1s ease-out 0.7s both;
        }
        
        .booking-details {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.05), rgba(0, 51, 102, 0.05));
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            text-align: left;
            animation: fadeInUp 1s ease-out 0.9s both;
            border: 1px solid rgba(0, 83, 156, 0.1);
        }
        
        .booking-ref {
            background: linear-gradient(135deg, #00539C, #003366);
            color: #fff;
            padding: 15px 25px;
            border-radius: 15px;
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 83, 156, 0.3);
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 83, 156, 0.1);
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #2d3748;
        }
        
        .detail-value {
            color: #00539C;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
            animation: fadeInUp 1s ease-out 1.1s both;
        }
        
        .btn-primary, .btn-secondary, .btn-success {
            border: none;
            border-radius: 15px;
            font-weight: 600;
            padding: 15px 30px;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-width: 180px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00539C, #003366);
            color: #fff;
            box-shadow: 0 5px 20px rgba(0, 83, 156, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: #fff;
            box-shadow: 0 5px 20px rgba(108, 117, 125, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: #fff;
            box-shadow: 0 5px 20px rgba(72, 187, 120, 0.3);
        }
        
        .btn-primary::before, .btn-secondary::before, .btn-success::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-primary:hover::before, .btn-secondary:hover::before, .btn-success:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary:hover, .btn-secondary:hover, .btn-success:hover {
            transform: translateY(-3px);
            color: #fff;
        }
        
        .btn-primary:hover {
            box-shadow: 0 8px 30px rgba(0, 83, 156, 0.4);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 8px 30px rgba(108, 117, 125, 0.4);
        }
        
        .btn-success:hover {
            box-shadow: 0 8px 30px rgba(72, 187, 120, 0.4);
        }
        
        .confetti {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1000;
        }
        
        .confetti-piece {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #ff6b6b;
            animation: confettiFall 3s linear infinite;
        }
        
        .confetti-piece:nth-child(1) { left: 10%; animation-delay: 0s; background: #ff6b6b; }
        .confetti-piece:nth-child(2) { left: 20%; animation-delay: 0.5s; background: #4ecdc4; }
        .confetti-piece:nth-child(3) { left: 30%; animation-delay: 1s; background: #45b7d1; }
        .confetti-piece:nth-child(4) { left: 40%; animation-delay: 1.5s; background: #96ceb4; }
        .confetti-piece:nth-child(5) { left: 50%; animation-delay: 2s; background: #feca57; }
        .confetti-piece:nth-child(6) { left: 60%; animation-delay: 2.5s; background: #ff9ff3; }
        .confetti-piece:nth-child(7) { left: 70%; animation-delay: 3s; background: #54a0ff; }
        .confetti-piece:nth-child(8) { left: 80%; animation-delay: 3.5s; background: #5f27cd; }
        .confetti-piece:nth-child(9) { left: 90%; animation-delay: 4s; background: #00d2d3; }
        
        @keyframes confettiFall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        
        /* Responsive */
        @media (max-width: 767px) {
            .success-card {
                padding: 30px 20px;
                margin: 20px;
            }
            
            .success-title {
                font-size: 2rem;
            }
            
            .success-icon {
                width: 100px;
                height: 100px;
            }
            
            .success-icon i {
                font-size: 2.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-primary, .btn-secondary, .btn-success {
                width: 100%;
                max-width: 300px;
            }
            
            .particles { display: none; }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
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

    <!-- Confetti Animation -->
    <div class="confetti">
        <div class="confetti-piece"></div>
        <div class="confetti-piece"></div>
        <div class="confetti-piece"></div>
        <div class="confetti-piece"></div>
        <div class="confetti-piece"></div>
        <div class="confetti-piece"></div>
        <div class="confetti-piece"></div>
        <div class="confetti-piece"></div>
        <div class="confetti-piece"></div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="../pexels-sevenstormphotography-728824 (1).jpg" alt="Logo" style="width:38px; margin-right:10px; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                SOLA
            </a>
            
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
        </div>
    </nav>

    <!-- Success Content -->
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i data-feather="check"></i>
            </div>
            
            <h1 class="success-title">Booking Confirmed!</h1>
            <p class="success-subtitle">
                Congratulations! Your flight has been successfully booked. 
                We've sent a confirmation email to <?php echo htmlspecialchars($user['email']); ?>
            </p>
            
            <div class="booking-details">
                <div class="booking-ref">
                    Booking Reference: <?php echo htmlspecialchars($booking['booking_ref']); ?>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Flight:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['airline'] . ' ' . $booking['flight_no']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Route:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['origin'] . ' → ' . $booking['destination']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value"><?php echo date('D, d M Y', strtotime($booking['flight_date'])); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Departure:</span>
                    <span class="detail-value"><?php echo date('H:i', strtotime($booking['departure_time'])); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Arrival:</span>
                    <span class="detail-value"><?php echo date('H:i', strtotime($booking['arrival_time'])); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Passengers:</span>
                    <span class="detail-value"><?php echo $booking['passengers']; ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Class:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['class']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value">₦<?php echo number_format($booking['total_amount'], 2); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value" style="color: #48bb78;"><?php echo htmlspecialchars($booking['status']); ?></span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="my-bookings.php" class="btn-primary">
                    <i data-feather="calendar"></i> View My Bookings
                </a>
                <a href="book-flight.php" class="btn-success">
                    <i data-feather="send"></i> Book Another Flight
                </a>
                <a href="dashboard.php" class="btn-secondary">
                    <i data-feather="home"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();

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

        // Success celebration sound (optional)
        function playSuccessSound() {
            // You can add a success sound here if you have an audio file
            // const audio = new Audio('path/to/success-sound.mp3');
            // audio.play().catch(e => console.log('Audio play failed:', e));
        }

        // Auto-hide confetti after 5 seconds
        setTimeout(() => {
            const confetti = document.querySelector('.confetti');
            if (confetti) {
                confetti.style.opacity = '0';
                confetti.style.transition = 'opacity 1s ease';
                setTimeout(() => {
                    confetti.remove();
                }, 1000);
            }
        }, 5000);

        // Button click animations
        document.querySelectorAll('.btn-primary, .btn-secondary, .btn-success').forEach(btn => {
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

        // Add ripple CSS
        const style = document.createElement('style');
        style.textContent = `
            .ripple {
                position: absolute;
                border-radius: 50%;
                background: rgba(255,255,255,0.6);
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
        document.head.appendChild(style);

        // Play success sound on page load
        window.addEventListener('load', () => {
            playSuccessSound();
        });

        console.log('Booking confirmed successfully!', {
            booking_ref: '<?php echo $booking['booking_ref']; ?>',
            flight: '<?php echo $booking['airline'] . ' ' . $booking['flight_no']; ?>',
            total_amount: <?php echo $booking['total_amount']; ?>
        });
    </script>
</body>
</html>