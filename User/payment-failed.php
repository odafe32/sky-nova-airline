<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$error = $_GET['error'] ?? 'unknown';
$reference = $_GET['reference'] ?? '';

// Error messages
$error_messages = [
    'no_reference' => 'Payment reference not found.',
    'verification_failed' => 'Payment verification failed. Please try again.',
    'payment_failed' => 'Payment was not successful. Please try again.',
    'processing_failed' => 'Payment processing failed. Please contact support.',
    'unknown' => 'An unknown error occurred during payment processing.'
];

$error_message = $error_messages[$error] ?? $error_messages['unknown'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Failed | Speed of Light Airlines</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .failed-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
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

        .failed-header {
            margin-bottom: 30px;
        }

        .failed-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: shake 0.8s ease-out;
        }

        .failed-icon i {
            color: white;
            font-size: 2.5rem;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .failed-header h1 {
            color: #e53e3e;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .failed-header p {
            color: #666;
            font-size: 1.2rem;
        }

        .error-details {
            background: rgba(255, 107, 107, 0.1);
            border: 2px solid rgba(255, 107, 107, 0.3);
            border-radius: 16px;
            padding: 25px;
            margin: 25px 0;
            border-left: 4px solid #ff6b6b;
        }

        .error-details h5 {
            color: #c53030;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .error-details p {
            color: #c53030;
            margin: 0;
        }

        .reference-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .reference-info h6 {
            color: #666;
            margin-bottom: 10px;
        }

        .reference-code {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
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
            background: linear-gradient(135deg, #00539C 0%, #003366 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
        }

        .btn-secondary-custom {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-outline-custom {
            background: transparent;
            color: #00539C;
            border: 2px solid #00539C;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            color: #fff;
        }

        .btn-primary-custom:hover {
            box-shadow: 0 8px 25px rgba(0, 83, 156, 0.4);
        }

        .btn-secondary-custom:hover {
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        }

        .btn-outline-custom:hover {
            background: #00539C;
            color: #fff;
        }

        .help-section {
            background: rgba(33, 150, 243, 0.1);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }

        .help-section h5 {
            color: #1976d2;
            margin-bottom: 15px;
        }

        .help-section ul {
            color: #1976d2;
            margin: 0;
            padding-left: 20px;
            text-align: left;
        }

        .help-section li {
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .failed-container {
                padding: 25px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="failed-container">
        <div class="failed-header">
            <div class="failed-icon">
                <i data-feather="x"></i>
            </div>
            <h1>Payment Failed</h1>
            <p>We couldn't process your payment</p>
        </div>

        <div class="error-details">
            <h5><i data-feather="alert-triangle"></i> Error Details</h5>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>

        <?php if (!empty($reference)): ?>
        <div class="reference-info">
            <h6>Transaction Reference:</h6>
            <div class="reference-code"><?php echo htmlspecialchars($reference); ?></div>
            <small class="text-muted">Please keep this reference for support inquiries</small>
        </div>
        <?php endif; ?>

        <div class="help-section">
            <h5><i data-feather="help-circle"></i> What can you do?</h5>
            <ul>
                <li>Check your internet connection and try again</li>
                <li>Verify your card details are correct</li>
                <li>Ensure you have sufficient funds</li>
                <li>Try using a different payment method</li>
                <li>Contact your bank if the issue persists</li>
            </ul>
        </div>

        <div class="action-buttons">
            <a href="checkout.php" class="btn-custom btn-primary-custom">
                <i data-feather="refresh-cw"></i> Try Again
            </a>
            <a href="book-flight.php" class="btn-custom btn-secondary-custom">
                <i data-feather="search"></i> New Search
            </a>
            <a href="dashboard.php" class="btn-custom btn-outline-custom">
                <i data-feather="home"></i> Dashboard
            </a>
        </div>

        <div class="mt-4">
            <p class="text-muted">
                <small>
                    Need help? Contact our support team at 
                    <a href="mailto:support@speedoflightairlines.com" style="color: #00539C;">
                        support@speedoflightairlines.com
                    </a>
                </small>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();
    </script>
</body>
</html>
</qodoArtifact>

