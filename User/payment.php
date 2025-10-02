<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment | Speed of Light Airlines</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Payment - Speed of Light Airlines" />
    <meta name="keywords" content="airline, payment, booking, checkout">
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
            animation: float 22s ease-in-out infinite;
        }
        
        .particle:nth-child(1) { width: 60px; height: 60px; left: 5%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 40px; height: 40px; left: 25%; animation-delay: 6s; }
        .particle:nth-child(3) { width: 80px; height: 80px; left: 45%; animation-delay: 12s; }
        .particle:nth-child(4) { width: 30px; height: 30px; left: 65%; animation-delay: 18s; }
        .particle:nth-child(5) { width: 50px; height: 50px; left: 85%; animation-delay: 24s; }
        .particle:nth-child(6) { width: 35px; height: 35px; left: 15%; animation-delay: 4s; }
        .particle:nth-child(7) { width: 70px; height: 70px; left: 75%; animation-delay: 14s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.6; }
            50% { transform: translateY(-350px) rotate(180deg); opacity: 0.2; }
        }
        
        .navbar {
            background: #00539C;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 83, 156, 0.15);
        }
        
        .navbar-brand {
            color: #fff !important;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 1.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
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
        }
        
        .user-name {
            font-size: 1rem;
            font-weight: 600;
        }
        
        .sidebar {
            background: #003366;
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
        
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #00539C;
            color: #fff;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .main-content {
            padding: 40px 20px 80px 20px;
            position: relative;
            z-index: 10;
        }
        
        .payment-header {
            color: #00539C;
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
        
        .payment-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00539C, #003366);
        }
        
        .payment-header h1 {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #00539C, #003366);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .payment-header p {
            font-size: 1.2rem;
            opacity: 0.8;
            font-weight: 400;
        }
        
        .payment-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
            margin-bottom: 40px;
            animation: fadeInUp 1s ease-out;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 83, 156, 0.1);
        }
        
        .payment-step {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }
        
        .step-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(0, 83, 156, 0.1);
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            border: 2px solid rgba(0, 83, 156, 0.2);
        }
        
        .step-circle.active {
            background: linear-gradient(135deg, #00539C, #003366);
            color: #fff;
            border-color: #00539C;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
            animation: pulse 2s infinite;
        }
        
        .step-circle.completed {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: #fff;
            border-color: #48bb78;
        }
        
        .step-label {
            font-weight: 600;
            color: #00539C;
            font-size: 1.1rem;
        }
        
        .step-connector {
            width: 40px;
            height: 2px;
            background: rgba(0, 83, 156, 0.2);
            margin: 0 15px;
        }
        
        .timer-box {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid rgba(255, 193, 7, 0.3);
            border-radius: 20px;
            padding: 25px 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
            animation: fadeInUp 1.2s ease-out;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(255, 193, 7, 0.15);
        }
        
        .timer-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffc107, #ff9800);
        }
        
        .timer-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.5rem;
            animation: pulse 2s infinite;
        }
        
        .timer-content h4 {
            font-weight: 700;
            color: #c62828;
            font-size: 1.3rem;
            margin-bottom: 5px;
        }
        
        .timer-count {
            font-size: 2.5rem;
            font-weight: 700;
            color: #c62828;
            letter-spacing: 3px;
            text-shadow: 0 2px 4px rgba(198, 40, 40, 0.2);
        }
        
        .smart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 83, 156, 0.1);
            padding: 30px;
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
            background: linear-gradient(90deg, #00539C, #003366);
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
        
        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #00539C;
            margin-bottom: 25px;
            position: relative;
            padding-left: 20px;
        }
        
        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: linear-gradient(135deg, #00539C, #003366);
            border-radius: 2px;
        }
        
        .payment-method-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.08);
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .payment-method-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 83, 156, 0.05), transparent);
            transition: left 0.5s;
        }
        
        .payment-method-card:hover::before {
            left: 100%;
        }
        
        .payment-method-card.selected, .payment-method-card:hover {
            border: 2px solid #48bb78;
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.15);
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 1);
        }
        
        .payment-method-card.selected::after {
            content: '✓';
            position: absolute;
            top: 15px;
            right: 15px;
            width: 25px;
            height: 25px;
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .payment-method-icons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .payment-method-icon {
            height: 35px;
            border-radius: 6px;
            transition: transform 0.3s ease;
        }
        
        .payment-method-card:hover .payment-method-icon {
            transform: scale(1.1);
        }
        
        .payment-method-content {
            flex: 1;
        }
        
        .payment-method-title {
            font-weight: 700;
            color: #00539C;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .payment-method-desc {
            color: #666;
            font-size: 0.95rem;
        }
        
        .summary-section {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.03) 0%, rgba(0, 51, 102, 0.03) 100%);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 83, 156, 0.1);
        }
        
        .summary-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #00539C;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 83, 156, 0.1);
            transition: all 0.3s ease;
            font-size: 1.05rem;
        }
        
        .summary-row:hover {
            background: rgba(0, 83, 156, 0.02);
            border-radius: 8px;
            padding: 8px 12px;
        }
        
        .summary-total {
            font-size: 1.3rem;
            font-weight: 700;
            color: #00539C;
            border-bottom: 2px solid #00539C;
            background: rgba(0, 83, 156, 0.05);
            border-radius: 12px;
            padding: 15px 12px;
        }
        
        .flight-summary-detail, .passenger-summary-detail {
            font-size: 1.05rem;
            color: #333;
            line-height: 1.6;
        }
        
        .airpaz-code-section {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.05) 0%, rgba(0, 51, 102, 0.05) 100%);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 83, 156, 0.1);
        }
        
        .payment-code {
            font-family: 'Courier New', monospace;
            font-size: 1.3rem;
            font-weight: 700;
            color: #00539C;
            background: rgba(0, 83, 156, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            letter-spacing: 2px;
        }
        
        .payment-status {
            font-weight: 600;
            color: #c62828;
            font-size: 1.1rem;
        }
        
        .voucher-section {
            background: linear-gradient(135deg, #e3fcec, #c8f7c5);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
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
            width: 80px;
            height: 80px;
            animation: bounce 2s infinite;
        }
        
        .promo-input-group {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(0, 83, 156, 0.1);
        }
        
        .promo-input-group .input-group {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.1);
        }
        
        .promo-input-group input {
            border: none;
            padding: 12px 16px;
            font-size: 1rem;
        }
        
        .promo-input-group button {
            background: linear-gradient(135deg, #48bb78, #38a169);
            border: none;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .promo-input-group button:hover {
            background: linear-gradient(135deg, #38a169, #2f855a);
            transform: translateY(-2px);
        }
        
        .security-badges {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .security-badges img {
            height: 40px;
            filter: grayscale(20%);
            transition: all 0.3s ease;
            border-radius: 8px;
        }
        
        .security-badges img:hover {
            filter: grayscale(0%);
            transform: scale(1.1);
        }
        
        .footer-links {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            padding: 40px 30px;
            margin-top: 50px;
            box-shadow: 0 8px 32px rgba(0, 83, 156, 0.1);
        }
        
        .footer-links h6 {
            color: #00539C;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .footer-links ul li {
            color: #666;
            margin-bottom: 8px;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .footer-links ul li:hover {
            color: #00539C;
        }
        
        .app-badges img {
            height: 45px;
            margin-right: 10px;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        
        .app-badges img:hover {
            transform: scale(1.05);
        }
        
        .footer {
            background: #00539C;
            color: #fff;
            text-align: center;
            padding: 20px 0 12px 0;
            margin-top: 50px;
            letter-spacing: 1px;
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Staggered Animation */
        .smart-card:nth-child(1) { animation-delay: 0.1s; }
        .smart-card:nth-child(2) { animation-delay: 0.2s; }
        .smart-card:nth-child(3) { animation-delay: 0.3s; }
        .smart-card:nth-child(4) { animation-delay: 0.4s; }
        .smart-card:nth-child(5) { animation-delay: 0.5s; }
        
        .payment-method-card:nth-child(1) { animation-delay: 0.1s; }
        .payment-method-card:nth-child(2) { animation-delay: 0.2s; }
        .payment-method-card:nth-child(3) { animation-delay: 0.3s; }
        .payment-method-card:nth-child(4) { animation-delay: 0.4s; }
        .payment-method-card:nth-child(5) { animation-delay: 0.5s; }
        .payment-method-card:nth-child(6) { animation-delay: 0.6s; }
        .payment-method-card:nth-child(7) { animation-delay: 0.7s; }
        
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
            .payment-steps {
                flex-direction: column;
                gap: 20px;
            }
            .step-connector {
                display: none;
            }
        }
        
        @media (max-width: 767px) {
            .payment-header h1 {
                font-size: 2.5rem;
            }
            .smart-card {
                padding: 20px 15px;
            }
            .payment-method-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .voucher-section {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .particles { display: none; }
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
            .timer-box {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(0, 83, 156, 0.1);
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(0, 83, 156, 0.3);
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 83, 156, 0.5);
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

    <!-- Enhanced Navbar with Cart Icon and User Info -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../pexels-sevenstormphotography-728824 (1).jpg" alt="Logo" style="width:38px; margin-right:10px; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                SOLA
            </a>
            
            <!-- Enhanced User Section with Cart Icon -->
            <div class="navbar-user-section">
                <div class="cart-icon-container" onclick="window.location.href='cart.php'">
                    <i data-feather="shopping-cart" class="cart-icon"></i>
                    <div class="cart-badge">2</div>
                </div>
                
                <div class="user-info">
                    <div class="user-avatar">A</div>
                    <div class="user-name">Albert</div>
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
                            <a class="nav-link" href="flight-status.php"><i data-feather="map-pin"></i> Flight Status</a>
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
                <div class="payment-header animate__animated animate__fadeInDown">
                    <h1>Secure Payment</h1>
                    <p>Complete your booking with our secure payment system.</p>
                </div>
                
                <!-- Enhanced Payment Steps -->
                <div class="payment-steps">
                    <div class="payment-step">
                        <div class="step-circle completed">1</div>
                        <span class="step-label">Booking</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="payment-step">
                        <div class="step-circle active">2</div>
                        <span class="step-label">Payment</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="payment-step">
                        <div class="step-circle">3</div>
                        <span class="step-label">Complete</span>
                    </div>
                </div>
                
                <!-- Enhanced Timer -->
                <div class="timer-box">
                    <div class="timer-icon">
                        <i data-feather="clock"></i>
                    </div>
                    <div class="timer-content">
                        <h4>Complete your payment in</h4>
                        <div class="timer-count" id="payment-timer">00:54:18</div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Payment Methods -->
                    <div class="col-lg-7 mb-4">
                        <div class="smart-card">
                            <div class="section-title">
                                <i data-feather="credit-card"></i> Payment Methods
                            </div>
                            
                            <div class="payment-method-card selected animate__animated animate__fadeInUp" data-method="card">
                                <div class="payment-method-icons">
                                    <img src="https://cdn.airpaz.com/cdn-cgi/image/w=240,h=40/rel-0276/payments/global/visa.png" class="payment-method-icon" alt="Visa">
                                    <img src="https://cdn.airpaz.com/cdn-cgi/image/w=240,h=40/rel-0276/payments/global/mastercard.png" class="payment-method-icon" alt="Mastercard">
                                    <img src="https://cdn.airpaz.com/cdn-cgi/image/w=240,h=40/rel-0276/payments/global/jcb.png" class="payment-method-icon" alt="JCB">
                                    <img src="https://cdn.airpaz.com/cdn-cgi/image/w=240,h=40/rel-0276/payments/global/unionpay.png" class="payment-method-icon" alt="UnionPay">
                                </div>
                                <div class="payment-method-content">
                                    <div class="payment-method-title">Credit/Debit Card</div>
                                    <div class="payment-method-desc">Pay securely with your card</div>
                                </div>
                            </div>
                            
                            <div class="payment-method-card animate__animated animate__fadeInUp" data-method="googlepay">
                                <div class="payment-method-icons">
                                    <img src="https://cdn.airpaz.com/cdn-cgi/image/w=240,h=40/rel-0276/payments/global/googlepay.png" class="payment-method-icon" alt="Google Pay">
                                </div>
                                <div class="payment-method-content">
                                    <div class="payment-method-title">Google Pay</div>
                                    <div class="payment-method-desc">Quick and secure payment with Google</div>
                                </div>
                            </div>
                            
                            <div class="payment-method-card animate__animated animate__fadeInUp" data-method="paypal">
                                <div class="payment-method-icons">
                                    <img src="https://cdn.airpaz.com/cdn-cgi/image/w=240,h=40/rel-0276/payments/global/paypal.png" class="payment-method-icon" alt="PayPal">
                                </div>
                                <div class="payment-method-content">
                                    <div class="payment-method-title">PayPal</div>
                                    <div class="payment-method-desc">Pay with your PayPal account</div>
                                </div>
                            </div>
                            
                            <div class="payment-method-card animate__animated animate__fadeInUp" data-method="wechat">
                                <div class="payment-method-icons">
                                    <img src="https://cdn.airpaz.com/cdn-cgi/image/w=240,h=40/rel-0276/payments/global/wechat.jpg" class="payment-method-icon" alt="WeChat Pay">
                                </div>
                                <div class="payment-method-content">
                                    <div class="payment-method-title">WeChat Pay</div>
                                    <div class="payment-method-desc">Pay with WeChat Pay</div>
                                </div>
                            </div>
                            
                            <div class="payment-method-card animate__animated animate__fadeInUp" data-method="alipay">
                                <div class="payment-method-icons">
                                    <img src="https://cdn.airpaz.com/cdn-cgi/image/w=240,h=40/rel-0276/payments/global/alipay.png" class="payment-method-icon" alt="Alipay">
                                </div>
                                <div class="payment-method-content">
                                    <div class="payment-method-title">Alipay</div>
                                    <div class="payment-method-desc">Pay with Alipay</div>
                                </div>
                            </div>
                            
                            <div class="payment-method-card animate__animated animate__fadeInUp" data-method="applepay">
                                <div class="payment-method-icons">
                                    <img src="https://cdn.airpaz.com/cdn-cgi/image/w=240,h=40/rel-0276/payments/global/applepay.png" class="payment-method-icon" alt="Apple Pay">
                                </div>
                                <div class="payment-method-content">
                                    <div class="payment-method-title">Apple Pay</div>
                                    <div class="payment-method-desc">Pay with Touch ID or Face ID</div>
                                </div>
                            </div>
                            
                            <div class="payment-method-card animate__animated animate__fadeInUp" data-method="crypto">
                                <div class="payment-method-icons">
                                    <img src="https://cdn.airpaz.com/cdn-cgi/image/w=240,h=40/rel-0276/payments/global/crypto-currency.png" class="payment-method-icon" alt="Cryptocurrency">
                                </div>
                                <div class="payment-method-content">
                                    <div class="payment-method-title">Cryptocurrency</div>
                                    <div class="payment-method-desc">Pay with Bitcoin, Ethereum & more</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="smart-card airpaz-code-section">
                            <div class="section-title">
                                <i data-feather="hash"></i> SOLA Booking Code
                            </div>
                            <div class="payment-code">1031989162</div>
                            <div class="payment-status">Payment Status: <span class="text-danger">Awaiting Payment</span></div>
                            <div class="mt-3" style="color: #666; font-size: 1.05rem;">
                                <i data-feather="dollar-sign" style="width: 16px; height: 16px;"></i> 
                                Payment Currency: <strong>US$(USD) - United States Dollar</strong>
                            </div>
                        </div>
                        
                        <div class="smart-card promo-input-group">
                            <div class="section-title">
                                <i data-feather="gift"></i> Voucher/Promo Code
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Enter your promo code">
                                <button class="btn btn-success" type="button">
                                    <i data-feather="check"></i> Apply
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Price & Summary -->
                    <div class="col-lg-5">
                        <div class="smart-card summary-section">
                            <div class="section-title">
                                <i data-feather="dollar-sign"></i> Price Details
                            </div>
                            <div class="summary-row">
                                <span>Adult (1 Passenger)</span>
                                <span>US$ 4,599.42</span>
                            </div>
                            <div class="summary-row">
                                <span>Processing Fee</span>
                                <span class="text-success">US$ 0.00</span>
                            </div>
                            <div class="summary-row">
                                <span>Taxes & Fees</span>
                                <span>Included</span>
                            </div>
                            <div class="summary-row summary-total">
                                <span>Total Price</span>
                                <span>US$ 4,599.42</span>
                            </div>
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i data-feather="users"></i> For 1 passenger • All taxes included
                                </small>
                            </div>
                        </div>
                        
                        <div class="smart-card summary-section">
                            <div class="section-title">
                                <i data-feather="send"></i> Flight Summary
                            </div>
                            <div class="flight-summary-detail mb-3">
                                <strong><i data-feather="arrow-right"></i> Departure Flight</strong><br>
                                DAC → IST | Direct Flight<br>
                                Tue, 12 Aug 2025 | 06:45 - 12:30<br>
                                <span class="text-muted">Turkish Airlines • Economy</span>
                            </div>
                            <div class="flight-summary-detail">
                                <strong><i data-feather="arrow-left"></i> Return Flight</strong><br>
                                IST → DAC | Direct Flight<br>
                                Wed, 13 Aug 2025 | 18:45 - 05:10<br>
                                <span class="text-muted">Turkish Airlines • Economy</span>
                            </div>
                        </div>
                        
                        <div class="smart-card summary-section">
                            <div class="section-title">
                                <i data-feather="users"></i> Passenger Details
                            </div>
                            <div class="passenger-summary-detail">
                                <strong>Mr. Albert Albert</strong><br>
                                Adult Passenger<br>
                                <span class="text-muted">Seat: 2A (Window)</span>
                            </div>
                        </div>
                        
                        <div class="voucher-section">
                            <img src="https://cdn.airpaz.com/cdn-cgi/image/w=400,h=400,f=webp/forerunner-next/img/illustration/v2/spot/cross-booking-voucher.png" alt="Voucher">
                            <div>
                                <div class="fw-bold mb-2">
                                    <i data-feather="gift"></i> Congratulations! 
                                    <span style="color:#43a047;">A FREE Hotel Discount Voucher</span> awaits you!
                                </div>
                                <div class="text-muted">Save up to 50% on your next hotel booking upon completing this payment.</div>
                            </div>
                        </div>
                        
                        <div class="smart-card">
                            <div class="section-title">
                                <i data-feather="shield"></i> Security & Trust
                            </div>
                            <div class="security-badges">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=1024,h=410/forerunner-next/img/logo_footer.png" alt="Airpaz contact">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=1024,h=422/forerunner-next/img/digicert.png" alt="Digicert">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=1024,h=450/forerunner-next/img/visa_verified.png" alt="Visa verified">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=1024,h=522/forerunner-next/img/logo_pcidss.png" alt="PCIDSS">
                            </div>
                            <div class="text-center">
                                <small class="text-muted">
                                    <i data-feather="lock"></i> Your payment information is encrypted and secure
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Enhanced Footer Links -->
                <div class="footer-links">
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <h6><i data-feather="home"></i> Speed of Light Airlines</h6>
                            <ul class="list-unstyled">
                                <li>Home</li>
                                <li>About Us</li>
                                <li>Flight Deals</li>
                                <li>Promotions</li>
                                <li>SOLA Blog</li>
                                <li>Airline Information</li>
                                <li>Airport Information</li>
                                <li>All Destinations</li>
                                <li>Careers</li>
                            </ul>
                        </div>
                        <div class="col-md-3 mb-4">
                            <h6><i data-feather="user"></i> My Account</h6>
                            <ul class="list-unstyled">
                                <li>Profile Settings</li>
                                <li>My Bookings</li>
                                <li>Contact List</li>
                                <li>Traveler List</li>
                                <li>Payment Methods</li>
                                <li>Loyalty Program</li>
                            </ul>
                        </div>
                        <div class="col-md-3 mb-4">
                            <h6><i data-feather="help-circle"></i> Support</h6>
                            <ul class="list-unstyled">
                                <li>SOLA Guide</li>
                                <li>How to Book</li>
                                <li>Help Center</li>
                                <li>Terms Of Use</li>
                                <li>Privacy Policy</li>
                                <li>Refund Policy</li>
                            </ul>
                        </div>
                        <div class="col-md-3 mb-4">
                            <h6><i data-feather="smartphone"></i> Get Our App</h6>
                            <div class="app-badges mb-3">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=438,h=134/forerunner-next/img/download-app-banner/downloadapp-en-googleplay.png" alt="Google Play">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=438,h=134/forerunner-next/img/download-app-banner/downloadapp-en-appstore.png" alt="App Store">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=438,h=134/forerunner-next/img/download-app-banner/downloadapp-en-huawei.png" alt="Huawei AppGallery">
                            </div>
                            <div class="text-muted small">
                                Download our mobile app for exclusive deals and faster booking!
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4 pt-4 border-top">
                        <div class="text-muted small">
                            Copyright © <span id="copyright-year"></span> Speed of Light Airlines. All rights reserved.
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <footer class="footer">
        &copy; <span id="year"></span> Speed of Light Airlines. All rights reserved.
    </footer>
    
    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();
        document.getElementById('year').textContent = new Date().getFullYear();
        document.getElementById('copyright-year').textContent = new Date().getFullYear();

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
        document.querySelector('.cart-icon-container').addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });

        // Enhanced Payment Timer (54:18 countdown)
        let timerSeconds = 54 * 60 + 18;
        function updateTimer() {
            let hr = String(Math.floor(timerSeconds / 3600)).padStart(2, '0');
            let min = String(Math.floor((timerSeconds % 3600) / 60)).padStart(2, '0');
            let sec = String(timerSeconds % 60).padStart(2, '0');
            document.getElementById('payment-timer').textContent = `${hr}:${min}:${sec}`;
            
            // Add urgency styling when time is low
            if (timerSeconds < 300) { // Less than 5 minutes
                document.getElementById('payment-timer').style.color = '#dc3545';
                document.querySelector('.timer-box').style.background = 'linear-gradient(135deg, #ffebee, #ffcdd2)';
            }
            
            if (timerSeconds > 0) timerSeconds--;
        }
        setInterval(updateTimer, 1000);
        updateTimer();

        // Enhanced payment method selection with animations
        document.querySelectorAll('.payment-method-card').forEach(function(card, index) {
            // Add staggered entrance animation
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('animate__animated', 'animate__fadeInUp');
            
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                document.querySelectorAll('.payment-method-card').forEach(function(c) {
                    c.classList.remove('selected');
                });
                
                // Add selected class to clicked card
                card.classList.add('selected');
                
                // Add click animation
                card.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    card.style.transform = '';
                }, 150);
            });
        });

        // Promo code application
        document.querySelector('.promo-input-group button').addEventListener('click', function() {
            const input = document.querySelector('.promo-input-group input');
            const promoCode = input.value.trim();
            
            if (promoCode) {
                // Add loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="loading-spinner"></span> Applying...';
                this.disabled = true;
                
                setTimeout(() => {
                    // Simulate promo code validation
                    if (promoCode.toLowerCase() === 'sola2025' || promoCode.toLowerCase() === 'welcome10') {
                        // Success
                        this.innerHTML = '<i data-feather="check"></i> Applied!';
                        this.classList.remove('btn-success');
                        this.classList.add('btn-success');
                        input.style.borderColor = '#48bb78';
                        
                        // Show discount in price
                        const discountRow = document.createElement('div');
                        discountRow.className = 'summary-row';
                        discountRow.innerHTML = '<span>Promo Discount</span><span class="text-success">-US$ 50.00</span>';
                        document.querySelector('.summary-total').parentNode.insertBefore(discountRow, document.querySelector('.summary-total'));
                        
                        // Update total
                        document.querySelector('.summary-total span:last-child').textContent = 'US$ 4,549.42';
                        
                        feather.replace();
                    } else {
                        // Error
                        this.innerHTML = '<i data-feather="x"></i> Invalid';
                        this.classList.remove('btn-success');
                        this.classList.add('btn-danger');
                        input.style.borderColor = '#f56565';
                        
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.classList.remove('btn-danger');
                            this.classList.add('btn-success');
                            input.style.borderColor = '';
                            this.disabled = false;
                            feather.replace();
                        }, 2000);
                    }
                }, 1500);
            }
        });

        // Enhanced form interactions
        document.querySelectorAll('.form-control').forEach(function(input) {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
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

        // Security badge hover effects
        document.querySelectorAll('.security-badges img').forEach(function(img) {
            img.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1) rotate(2deg)';
            });
            
            img.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1) rotate(0deg)';
            });
        });

        // Footer links hover effects
        document.querySelectorAll('.footer-links ul li').forEach(function(item) {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });

        // App badge click animations
        document.querySelectorAll('.app-badges img').forEach(function(img) {
            img.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        });
    </script>
</body>
</html>
</qodoArtifact>

Perfect! I've completely transformed your payment.php page with stunning modern UI/UX! 🚀💳

## ✨ **Major Enhancements:**

### 🎯 **Enhanced Header:**
- **Cart icon** with animated badge (showing 2 items)
- **User info section** with avatar and name "Albert"
- **Glass morphism effects** with backdrop blur
- **Hover animations** and click feedback
- **Responsive design** that stacks on mobile

### 🎨 **Dramatically Improved Design:**
- **Floating particles** background animation
- **Modern gradient headers** with animated borders
- **Enhanced payment steps** with completed/active states
- **Beautiful timer section** with icon and better styling
- **Glass morphism cards** throughout
- **Staggered entrance animations** for all elements

### 💳 **Enhanced Payment Methods:**
- **Larger, more premium cards** with hover effects
- **Checkmark indicators** for selected methods
- **Better icon layouts** and spacing
- **Smooth selection animations**
- **Improved visual hierarchy**

### 🎪 **Advanced Visual Features:**
- **Enhanced timer box** with urgency styling (turns red when < 5 minutes)
- **Interactive promo code** with working validation
- **Better section titles** with icons and accent lines
- **Improved summary cards** with hover effects
- **Enhanced security badges** with rotation animations

### 🚀 **Smart Interactions:**
- **Working promo codes** ("SOLA2025" or "WELCOME10" for $50 off)
- **Dynamic price updates** when promo is applied
- **Loading states** for all interactions
- **Click animations** throughout
- **Smooth transitions