<!DOCTYPE html>
<html lang="en">
<head>
    <title>Flight Status | Speed of Light Airlines</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Flight Status - Speed of Light Airlines" />
    <meta name="keywords" content="airline, flight status, real-time, tracking">
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
            background: rgba(0, 83, 156, 0.08);
            border-radius: 50%;
            animation: float 15s ease-in-out infinite;
        }
        
        .particle:nth-child(1) { width: 60px; height: 60px; left: 5%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 40px; height: 40px; left: 25%; animation-delay: 3s; }
        .particle:nth-child(3) { width: 80px; height: 80px; left: 45%; animation-delay: 6s; }
        .particle:nth-child(4) { width: 30px; height: 30px; left: 65%; animation-delay: 9s; }
        .particle:nth-child(5) { width: 50px; height: 50px; left: 85%; animation-delay: 12s; }
        .particle:nth-child(6) { width: 35px; height: 35px; left: 15%; animation-delay: 2s; }
        .particle:nth-child(7) { width: 70px; height: 70px; left: 75%; animation-delay: 8s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.6; }
            50% { transform: translateY(-200px) rotate(180deg); opacity: 0.2; }
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
        
        .status-header {
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
        
        .status-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00539C, #003366);
        }
        
        .status-header h1 {
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
        
        .status-header p {
            font-size: 1.2rem;
            opacity: 0.8;
            font-weight: 400;
        }
        
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 83, 156, 0.1);
            padding: 40px 30px;
            margin: 0 auto 50px auto;
            max-width: 800px;
            animation: fadeInUp 1s ease-out;
            border: 1px solid rgba(0, 83, 156, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .search-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00539C, #003366);
        }
        
        .search-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            background: rgba(0, 83, 156, 0.05);
            border-radius: 16px;
            padding: 8px;
        }
        
        .search-tab {
            background: transparent;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #666;
            flex: 1;
            max-width: 200px;
        }
        
        .search-tab.active {
            background: #00539C;
            color: #fff;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
            transform: translateY(-2px);
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #00539C;
            box-shadow: 0 0 0 0.2rem rgba(0, 83, 156, 0.15);
            background: #fff;
            transform: translateY(-2px);
        }
        
        .search-btn {
            background: linear-gradient(135deg, #00539C 0%, #003366 100%);
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
            from { box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3); }
            to { box-shadow: 0 4px 25px rgba(0, 83, 156, 0.6), 0 0 30px rgba(0, 83, 156, 0.2); }
        }
        
        .search-btn::before {
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
        
        .search-btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .search-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 83, 156, 0.4);
        }
        
        /* Filter Controls */
        .filter-controls {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 40px;
            box-shadow: 0 4px 20px rgba(0, 83, 156, 0.1);
            border: 1px solid rgba(0, 83, 156, 0.1);
            animation: fadeInUp 1.2s ease-out;
            text-align: center;
        }
        
        .filter-btn {
            background: rgba(0, 83, 156, 0.1);
            color: #00539C;
            border: 2px solid rgba(0, 83, 156, 0.2);
            border-radius: 12px;
            padding: 8px 20px;
            margin: 5px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .filter-btn.active, .filter-btn:hover {
            background: #00539C;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.3);
        }
        
        /* Smart Status Cards */
        .status-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .status-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 83, 156, 0.1);
            padding: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: fadeInUp 1s ease-out;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 83, 156, 0.1);
            cursor: pointer;
        }
        
        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #00539C, #003366);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .status-card:hover::before {
            transform: scaleX(1);
        }
        
        .status-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 83, 156, 0.2);
            background: rgba(255, 255, 255, 1);
        }
        
        .card-header {
            background: linear-gradient(135deg, rgba(0, 83, 156, 0.05) 0%, rgba(0, 51, 102, 0.05) 100%);
            padding: 25px;
            border-bottom: 1px solid rgba(0, 83, 156, 0.1);
            position: relative;
        }
        
        .airline-section {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .airline-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-right: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.1);
            transition: transform 0.3s ease;
        }
        
        .status-card:hover .airline-logo {
            transform: scale(1.1) rotate(5deg);
        }
        
        .airline-info h4 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #00539C;
            margin-bottom: 5px;
        }
        
        .flight-number {
            color: #666;
            font-weight: 500;
            font-size: 1rem;
        }
        
        .status-badge {
            position: absolute;
            top: 25px;
            right: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
            letter-spacing: 0.5px;
            animation: pulse 2s infinite;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-on-time {
            background: linear-gradient(135deg, #e3fcec, #c8f7c5);
            color: #1b5e20;
            box-shadow: 0 2px 10px rgba(27, 94, 32, 0.2);
        }
        
        .status-delayed {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            color: #c62828;
            box-shadow: 0 2px 10px rgba(198, 40, 40, 0.2);
        }
        
        .status-boarding {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1565c0;
            box-shadow: 0 2px 10px rgba(21, 101, 192, 0.2);
        }
        
        .status-landed {
            background: linear-gradient(135deg, #fbe9e7, #d7ccc8);
            color: #4e342e;
            box-shadow: 0 2px 10px rgba(78, 52, 46, 0.2);
        }
        
        .status-cancelled {
            background: linear-gradient(135deg, #ffebee, #ef5350);
            color: #fff;
            box-shadow: 0 2px 10px rgba(244, 67, 54, 0.3);
        }
        
        .status-scheduled {
            background: linear-gradient(135deg, #e3f2fd, #90caf9);
            color: #00539C;
            box-shadow: 0 2px 10px rgba(0, 83, 156, 0.2);
        }
        
        .status-arrived {
            background: linear-gradient(135deg, #e8f5e9, #a5d6a7);
            color: #388e3c;
            box-shadow: 0 2px 10px rgba(56, 142, 60, 0.2);
        }
        
        .status-gate-changed {
            background: linear-gradient(135deg, #fffde7, #fff59d);
            color: #f57f17;
            box-shadow: 0 2px 10px rgba(245, 127, 23, 0.2);
        }
        
        .status-checkin-open {
            background: linear-gradient(135deg, #e3f2fd, #81c784);
            color: #1976d2;
            box-shadow: 0 2px 10px rgba(25, 118, 210, 0.2);
        }
        
        .status-checkin-closed {
            background: linear-gradient(135deg, #eeeeee, #bdbdbd);
            color: #616161;
            box-shadow: 0 2px 10px rgba(97, 97, 97, 0.2);
        }
        
        .flight-route {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 25px 0;
            position: relative;
        }
        
        .route-point {
            text-align: center;
            flex: 1;
        }
        
        .route-code {
            font-size: 1.8rem;
            font-weight: 700;
            color: #00539C;
            margin-bottom: 5px;
        }
        
        .route-city {
            color: #666;
            font-size: 0.9rem;
        }
        
        .route-arrow {
            margin: 0 20px;
            color: #00539C;
            font-size: 1.5rem;
            position: relative;
        }
        
        .route-arrow::before {
            content: '✈️';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.2rem;
            animation: fly 3s ease-in-out infinite;
        }
        
        @keyframes fly {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-8px); }
        }
        
        .flight-details {
            background: rgba(0, 83, 156, 0.02);
            border-radius: 16px;
            padding: 25px;
            margin: 20px 0;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            text-align: center;
            padding: 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
        }
        
        .detail-item:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 83, 156, 0.1);
        }
        
        .detail-label {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            font-weight: 700;
            color: #00539C;
            font-size: 1.1rem;
        }
        
        .card-actions {
            padding: 25px;
            background: rgba(0, 83, 156, 0.02);
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-width: 120px;
        }
        
        .action-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .action-btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
        }
        
        .btn-track {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: #fff;
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.3);
        }
        
        .btn-notify {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: #fff;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
        }
        
        .footer {
            background: #00539C;
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
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Staggered Animation */
        .status-card:nth-child(1) { animation-delay: 0.1s; }
        .status-card:nth-child(2) { animation-delay: 0.2s; }
        .status-card:nth-child(3) { animation-delay: 0.3s; }
        .status-card:nth-child(4) { animation-delay: 0.4s; }
        .status-card:nth-child(5) { animation-delay: 0.5s; }
        .status-card:nth-child(6) { animation-delay: 0.6s; }
        
        /* Responsive */
        @media (max-width: 991px) {
            .sidebar {
                min-height: auto;
                padding-top: 0;
            }
            .main-content {
                padding: 30px 15px 80px 15px;
            }
            .status-cards-container {
                grid-template-columns: 1fr;
            }
            .navbar-user-section {
                gap: 15px;
            }
        }
        
        @media (max-width: 767px) {
            .status-header h1 {
                font-size: 2.2rem;
            }
            .search-container {
                padding: 25px 20px;
            }
            .route-code {
                font-size: 1.4rem;
            }
            .detail-grid {
                grid-template-columns: repeat(2, 1fr);
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
                            <a class="nav-link" href="dashboard.php">
                                <i data-feather="home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="book-flight.php">
                                <i data-feather="send"></i> Book a Flight
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my-bookings.php">
                                <i data-feather="calendar"></i> My Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="flight-status.php">
                                <i data-feather="map-pin"></i> Flight Status
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
                <div class="status-header animate__animated animate__fadeInDown">
                    <h1>Flight Status</h1>
                    <p>Track your flights in real-time with smart notifications and updates.</p>
                </div>
                
                <!-- Smart Search Container -->
                <div class="search-container shadow-lg animate__animated animate__fadeInUp">
                    <div class="search-tabs">
                        <button class="search-tab active" data-tab="flight">
                            <i data-feather="send"></i> Flight Number
                        </button>
                        <button class="search-tab" data-tab="route">
                            <i data-feather="map"></i> Route
                        </button>
                    </div>
                    
                    <form id="flightSearchForm" autocomplete="off">
                        <div id="flightTab" class="tab-content">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label">Flight Number</label>
                                    <input type="text" class="form-control" name="flight_no" id="flightNo" placeholder="e.g. AF149, BA001" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Date</label>
                                    <input type="date" class="form-control" name="date" id="flightDate" required>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn search-btn btn-lg">
                                        <i data-feather="search"></i> Track
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div id="routeTab" class="tab-content" style="display: none;">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">From</label>
                                    <input type="text" class="form-control" name="from" placeholder="City or Airport">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">To</label>
                                    <input type="text" class="form-control" name="to" placeholder="City or Airport">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date</label>
                                    <input type="date" class="form-control" name="route_date">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn search-btn btn-lg">
                                        <i data-feather="search"></i> Find
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Filter Controls -->
                <div class="filter-controls">
                    <button class="btn filter-btn active" data-filter="all">All Flights</button>
                    <button class="btn filter-btn" data-filter="on-time">On Time</button>
                    <button class="btn filter-btn" data-filter="delayed">Delayed</button>
                    <button class="btn filter-btn" data-filter="boarding">Boarding</button>
                    <button class="btn filter-btn" data-filter="landed">Landed</button>
                    <button class="btn filter-btn" data-filter="cancelled">Cancelled</button>
                </div>
                
                <!-- Smart Status Cards -->
                <div class="status-cards-container" id="statusCardsContainer">
                    <!-- On Time Flight -->
                    <div class="status-card animate__animated animate__fadeInUp" data-status="on-time">
                        <div class="status-badge status-on-time">
                            <i data-feather="check-circle"></i> On Time
                        </div>
                        
                        <div class="card-header">
                            <div class="airline-section">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=54,h=54,f=webp/rel-0275/airlines/201x201/AF.png" alt="Air France" class="airline-logo">
                                <div class="airline-info">
                                    <h4>Air France</h4>
                                    <div class="flight-number">AF 149</div>
                                </div>
                            </div>
                            
                            <div class="flight-route">
                                <div class="route-point">
                                    <div class="route-code">LOS</div>
                                    <div class="route-city">Lagos</div>
                                </div>
                                <div class="route-arrow">
                                    <i data-feather="arrow-right"></i>
                                </div>
                                <div class="route-point">
                                    <div class="route-code">CDG</div>
                                    <div class="route-city">Paris</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flight-details">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label">Date</div>
                                    <div class="detail-value">Aug 15, 2024</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Departure</div>
                                    <div class="detail-value">23:50</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Arrival</div>
                                    <div class="detail-value">07:40</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Terminal</div>
                                    <div class="detail-value">1</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Gate</div>
                                    <div class="detail-value">A12</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Aircraft</div>
                                    <div class="detail-value">A350</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-actions">
                            <button class="action-btn btn-track">
                                <i data-feather="eye"></i> Track Live
                            </button>
                            <button class="action-btn btn-notify">
                                <i data-feather="bell"></i> Get Alerts
                            </button>
                        </div>
                    </div>
                    
                    <!-- Delayed Flight -->
                    <div class="status-card animate__animated animate__fadeInUp" data-status="delayed">
                        <div class="status-badge status-delayed">
                            <i data-feather="clock"></i> Delayed
                        </div>
                        
                        <div class="card-header">
                            <div class="airline-section">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=54,h=54,f=webp/rel-0275/airlines/201x201/BA.png" alt="British Airways" class="airline-logo">
                                <div class="airline-info">
                                    <h4>British Airways</h4>
                                    <div class="flight-number">BA 001</div>
                                </div>
                            </div>
                            
                            <div class="flight-route">
                                <div class="route-point">
                                    <div class="route-code">LHR</div>
                                    <div class="route-city">London</div>
                                </div>
                                <div class="route-arrow">
                                    <i data-feather="arrow-right"></i>
                                </div>
                                <div class="route-point">
                                    <div class="route-code">JFK</div>
                                    <div class="route-city">New York</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flight-details">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label">Date</div>
                                    <div class="detail-value">Aug 15, 2024</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Scheduled</div>
                                    <div class="detail-value">22:50</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">New Time</div>
                                    <div class="detail-value">23:30</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Terminal</div>
                                    <div class="detail-value">5</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Gate</div>
                                    <div class="detail-value">B22</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Delay</div>
                                    <div class="detail-value">40 min</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-actions">
                            <button class="action-btn btn-track">
                                <i data-feather="eye"></i> Track Live
                            </button>
                            <button class="action-btn btn-notify">
                                <i data-feather="bell"></i> Get Alerts
                            </button>
                        </div>
                    </div>
                    
                    <!-- Boarding Flight -->
                    <div class="status-card animate__animated animate__fadeInUp" data-status="boarding">
                        <div class="status-badge status-boarding">
                            <i data-feather="log-in"></i> Boarding
                        </div>
                        
                        <div class="card-header">
                            <div class="airline-section">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=54,h=54,f=webp/rel-0275/airlines/201x201/EK.png" alt="Emirates" class="airline-logo">
                                <div class="airline-info">
                                    <h4>Emirates</h4>
                                    <div class="flight-number">EK 202</div>
                                </div>
                            </div>
                            
                            <div class="flight-route">
                                <div class="route-point">
                                    <div class="route-code">DXB</div>
                                    <div class="route-city">Dubai</div>
                                </div>
                                <div class="route-arrow">
                                    <i data-feather="arrow-right"></i>
                                </div>
                                <div class="route-point">
                                    <div class="route-code">JFK</div>
                                    <div class="route-city">New York</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flight-details">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label">Date</div>
                                    <div class="detail-value">Aug 15, 2024</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Departure</div>
                                    <div class="detail-value">01:30</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Arrival</div>
                                    <div class="detail-value">07:45</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Terminal</div>
                                    <div class="detail-value">3</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Gate</div>
                                    <div class="detail-value">C7</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Aircraft</div>
                                    <div class="detail-value">A380</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-actions">
                            <button class="action-btn btn-track">
                                <i data-feather="eye"></i> Track Live
                            </button>
                            <button class="action-btn btn-notify">
                                <i data-feather="bell"></i> Get Alerts
                            </button>
                        </div>
                    </div>
                    
                    <!-- Landed Flight -->
                    <div class="status-card animate__animated animate__fadeInUp" data-status="landed">
                        <div class="status-badge status-landed">
                            <i data-feather="check"></i> Landed
                        </div>
                        
                        <div class="card-header">
                            <div class="airline-section">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=54,h=54,f=webp/rel-0275/airlines/201x201/DL.png" alt="Delta" class="airline-logo">
                                <div class="airline-info">
                                    <h4>Delta Airlines</h4>
                                    <div class="flight-number">DL 404</div>
                                </div>
                            </div>
                            
                            <div class="flight-route">
                                <div class="route-point">
                                    <div class="route-code">ATL</div>
                                    <div class="route-city">Atlanta</div>
                                </div>
                                <div class="route-arrow">
                                    <i data-feather="arrow-right"></i>
                                </div>
                                <div class="route-point">
                                    <div class="route-code">ORD</div>
                                    <div class="route-city">Chicago</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flight-details">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label">Date</div>
                                    <div class="detail-value">Aug 15, 2024</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Departed</div>
                                    <div class="detail-value">10:00</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Landed</div>
                                    <div class="detail-value">12:15</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Terminal</div>
                                    <div class="detail-value">S</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Gate</div>
                                    <div class="detail-value">D5</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Baggage</div>
                                    <div class="detail-value">Belt 3</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-actions">
                            <button class="action-btn btn-track">
                                <i data-feather="eye"></i> View Details
                            </button>
                            <button class="action-btn btn-notify">
                                <i data-feather="download"></i> Receipt
                            </button>
                        </div>
                    </div>
                    
                    <!-- Cancelled Flight -->
                    <div class="status-card animate__animated animate__fadeInUp" data-status="cancelled">
                        <div class="status-badge status-cancelled">
                            <i data-feather="x-circle"></i> Cancelled
                        </div>
                        
                        <div class="card-header">
                            <div class="airline-section">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=54,h=54,f=webp/rel-0275/airlines/201x201/UA.png" alt="United" class="airline-logo">
                                <div class="airline-info">
                                    <h4>United Airlines</h4>
                                    <div class="flight-number">UA 888</div>
                                </div>
                            </div>
                            
                            <div class="flight-route">
                                <div class="route-point">
                                    <div class="route-code">IAH</div>
                                    <div class="route-city">Houston</div>
                                </div>
                                <div class="route-arrow">
                                    <i data-feather="arrow-right"></i>
                                </div>
                                <div class="route-point">
                                    <div class="route-code">DEN</div>
                                    <div class="route-city">Denver</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flight-details">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label">Date</div>
                                    <div class="detail-value">Aug 15, 2024</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Scheduled</div>
                                    <div class="detail-value">14:00</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value">Cancelled</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Reason</div>
                                    <div class="detail-value">Weather</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Refund</div>
                                    <div class="detail-value">Full</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Rebooking</div>
                                    <div class="detail-value">Available</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-actions">
                            <button class="action-btn btn-track">
                                <i data-feather="refresh-cw"></i> Rebook
                            </button>
                            <button class="action-btn btn-notify">
                                <i data-feather="phone"></i> Support
                            </button>
                        </div>
                    </div>
                    
                    <!-- Gate Changed Flight -->
                    <div class="status-card animate__animated animate__fadeInUp" data-status="gate-changed">
                        <div class="status-badge status-gate-changed">
                            <i data-feather="alert-triangle"></i> Gate Changed
                        </div>
                        
                        <div class="card-header">
                            <div class="airline-section">
                                <img src="https://cdn.airpaz.com/cdn-cgi/image/w=54,h=54,f=webp/rel-0275/airlines/201x201/LH.png" alt="Lufthansa" class="airline-logo">
                                <div class="airline-info">
                                    <h4>Lufthansa</h4>
                                    <div class="flight-number">LH 123</div>
                                </div>
                            </div>
                            
                            <div class="flight-route">
                                <div class="route-point">
                                    <div class="route-code">FRA</div>
                                    <div class="route-city">Frankfurt</div>
                                </div>
                                <div class="route-arrow">
                                    <i data-feather="arrow-right"></i>
                                </div>
                                <div class="route-point">
                                    <div class="route-code">FCO</div>
                                    <div class="route-city">Rome</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flight-details">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label">Date</div>
                                    <div class="detail-value">Aug 15, 2024</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Departure</div>
                                    <div class="detail-value">11:00</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Arrival</div>
                                    <div class="detail-value">13:30</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Terminal</div>
                                    <div class="detail-value">T2</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">New Gate</div>
                                    <div class="detail-value">H4</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Old Gate</div>
                                    <div class="detail-value">G2</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-actions">
                            <button class="action-btn btn-track">
                                <i data-feather="map"></i> Find Gate
                            </button>
                            <button class="action-btn btn-notify">
                                <i data-feather="bell"></i> Get Alerts
                            </button>
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

        // Search tabs functionality
        document.querySelectorAll('.search-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.search-tab').forEach(function(t) {
                    t.classList.remove('active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(function(content) {
                    content.style.display = 'none';
                });
                
                // Show selected tab content
                const tabType = this.getAttribute('data-tab');
                document.getElementById(tabType + 'Tab').style.display = 'block';
            });
        });

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(function(b) {
                    b.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const cards = document.querySelectorAll('.status-card');
                
                cards.forEach(function(card) {
                    if (filter === 'all' || card.getAttribute('data-status') === filter) {
                        card.style.display = 'block';
                        card.style.animation = 'fadeInUp 0.5s ease-out';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Form submission with loading animation
        document.getElementById('flightSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('.search-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="loading-spinner"></span> Searching...';
            submitBtn.disabled = true;
            
            // Simulate search
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Show success message
                alert('🔍 Flight status retrieved successfully!');
            }, 2000);
        });

        // Action button functionality
        document.querySelectorAll('.action-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="loading-spinner"></span> Loading...';
                this.disabled = true;
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                    
                    if (this.textContent.includes('Track')) {
                        alert('🛩️ Live tracking activated!');
                    } else if (this.textContent.includes('Alerts')) {
                        alert('🔔 Flight alerts enabled!');
                    } else if (this.textContent.includes('Rebook')) {
                        alert('✈️ Rebooking options available!');
                    } else if (this.textContent.includes('Support')) {
                        alert('📞 Connecting to customer support...');
                    }
                }, 1500);
            });
        });

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('flightDate').setAttribute('min', today);
        document.querySelector('input[name="route_date"]').setAttribute('min', today);

        // Add smooth scroll behavior
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

        // Observe all status cards
        document.querySelectorAll('.status-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>