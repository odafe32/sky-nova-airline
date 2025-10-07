<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkyNova Airlines - Soar Above the Ordinary</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- GSAP for Advanced Animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollToPlugin.min.js"></script>

    <style>
        :root {
            --primary: #10b981;
            --secondary: #059669;
            --accent: #34d399;
            --dark: #0f172a;
            --light: #ffffff;
            --glass: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            background: var(--dark);
            color: var(--light);
        }

        /* Cursor Effects */
        .cursor {
            width: 20px;
            height: 20px;
            border: 2px solid var(--primary);
            border-radius: 50%;
            position: fixed;
            pointer-events: none;
            z-index: 9999;
            transition: all 0.1s ease;
            backdrop-filter: blur(2px);
        }

        .cursor-follower {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            position: fixed;
            pointer-events: none;
            z-index: 9998;
            transition: all 0.15s ease;
        }

        /* Particle Background */
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
        }

        /* BEAUTIFUL FIXED GREEN TRANSPARENT NAVBAR */
        .glass-nav {
            background: linear-gradient(135deg,
                    rgba(16, 185, 129, 0.15) 0%,
                    rgba(5, 150, 105, 0.12) 50%,
                    rgba(52, 211, 153, 0.10) 100%
                );
            backdrop-filter: blur(25px);
            border-bottom: 1px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.1);
            transition: all 0.3s ease;
        }

        /* Remove the dark scrolled state - keep it beautiful always */
        .glass-nav.scrolled {
            background: linear-gradient(135deg,
                    rgba(16, 185, 129, 0.18) 0%,
                    rgba(5, 150, 105, 0.15) 50%,
                    rgba(52, 211, 153, 0.12) 100%);
            backdrop-filter: blur(30px);
            border-bottom: 1px solid rgba(16, 185, 129, 0.4);
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.15);
        }

        /* Enhanced navbar brand with green gradient */
        .navbar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(45deg,
                    #10b981,
                    #34d399,
                    #6ee7b7
                );
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 2px 8px rgba(16, 185, 129, 0.3));
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            filter: drop-shadow(0 4px 12px rgba(16, 185, 129, 0.5));
            transform: translateY(-1px);
        }

        /* Beautiful nav links with green accents */
        .nav-link {
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            color: rgba(255, 255, 255, 0.9) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 50%;
            background: linear-gradient(45deg,
                    #10b981,
                    #34d399
                );
            transition: all 0.3s ease;
            transform: translateX(-50%);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
        }

        .nav-link:hover {
            color: #10b981 !important;
            text-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
            transform: translateY(-1px);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        /* Enhanced Book Now button with green theme */
        .nav-link.neon-btn {
            background: linear-gradient(45deg,
                    rgba(16, 185, 129, 0.8),
                    rgba(52, 211, 153, 0.9)
                ) !important;
            border: 1px solid rgba(16, 185, 129, 0.6);
            border-radius: 25px;
            padding: 12px 24px !important;
            font-weight: 700;
            color: white !important;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .nav-link.neon-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, 0.3),
                    transparent);
            transition: left 0.6s;
        }

        .nav-link.neon-btn:hover::before {
            left: 100%;
        }

        .nav-link.neon-btn:hover {
            background: linear-gradient(45deg,
                    rgba(16, 185, 129, 1),
                    rgba(52, 211, 153, 1)
                ) !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5) !important;
            border-color: rgba(16, 185, 129, 0.8);
        }

        .nav-link.neon-btn:hover::after {
            display: none;
            /* Remove the underline for the button */
        }

        /* Mobile navbar toggler with green theme */
        .navbar-toggler {
            border: 1px solid rgba(16, 185, 129, 0.5) !important;
            background: rgba(16, 185, 129, 0.1);
            backdrop-filter: blur(10px);
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25) !important;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2816, 185, 129, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
        }

        /* Responsive adjustments */
        @media (max-width: 991px) {
            .navbar-collapse {
                background: linear-gradient(135deg, rgba(16, 185, 129, 0.95) 0%, rgba(5, 150, 105, 0.92) 100%);
                background: linear-gradient(135deg,
                        rgba(16, 185, 129, 0.95) 0%,
                        rgba(5, 150, 105, 0.92) 100%);
                backdrop-filter: blur(20px);
                border-radius: 15px;
                margin-top: 15px;
                padding: 20px;
                border: 1px solid rgba(16, 185, 129, 0.3);
                box-shadow: 0 8px 32px rgba(16, 185, 129, 0.2);
            }

            .nav-link {
                padding: 12px 0 !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .nav-link:last-child {
                border-bottom: none;
            }
        }

        /* Add a subtle glow effect to the entire navbar */
        .glass-nav::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg,
                    transparent 0%,
                    rgba(16, 185, 129, 0.1) 50%,
                    transparent 100%);
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .glass-nav:hover::before {
            opacity: 1;
        }

        /* STUNNING HERO SECTION WITH CAROUSEL BACKGROUND */
        .hero {
            height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Carousel Background Container */
        .hero-carousel {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0;
            transition: opacity 2s ease-in-out;
            animation: kenBurns 15s ease-in-out infinite;
        }

        .carousel-slide.active {
            opacity: 1;
        }

        /* Ken Burns Effect for Dynamic Movement */
        @keyframes kenBurns {
            0% {
                transform: scale(1) rotate(0deg);
            }

            25% {
                transform: scale(1.1) rotate(0.5deg);
            }

            50% {
                transform: scale(1.05) rotate(-0.3deg);
            }

            75% {
                transform: scale(1.08) rotate(0.2deg);
            }

            100% {
                transform: scale(1) rotate(0deg);
            }
        }

        /* Gradient Overlay for Text Readability */
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg,
                    rgba(15, 23, 42, 0.7) 0%,
                    rgba(5, 150, 105, 0.3) 25%,
                    rgba(16, 185, 129, 0.2) 50%,
                    rgba(15, 23, 42, 0.6) 100%);
            z-index: -1;
        }

        /* Floating Elements */
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .floating-plane {
            position: absolute;
            font-size: 2rem;
            color: rgba(255, 255, 255, 0.15);
            animation: float 25s infinite linear;
            filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.3));
        }

        @keyframes float {
            0% {
                transform: translateX(-100px) translateY(0px) rotate(0deg);
            }

            25% {
                transform: translateX(calc(100vw + 100px)) translateY(-80px) rotate(8deg);
            }

            50% {
                transform: translateX(calc(100vw + 100px)) translateY(80px) rotate(-8deg);
            }

            75% {
                transform: translateX(-100px) translateY(40px) rotate(4deg);
            }

            100% {
                transform: translateX(-100px) translateY(0px) rotate(0deg);
            }
        }

        .hero-content {
            text-align: center;
            z-index: 2;
            max-width: 900px;
            position: relative;
        }

        .hero-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(3.5rem, 10vw, 7rem);
            font-weight: 900;
            background: linear-gradient(45deg, #fff, var(--primary), var(--accent), #fff);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            text-shadow: 0 0 50px rgba(16, 185, 129, 0.4);
            animation: gradientShift 4s ease-in-out infinite;
            letter-spacing: -2px;
            line-height: 0.9;
        }

        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        .hero-subtitle {
            font-size: 1.8rem;
            font-weight: 300;
            margin-bottom: 3rem;
            opacity: 0.95;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.5);
            letter-spacing: 0.5px;
        }

        /* Carousel Navigation Dots */
        .carousel-dots {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
            z-index: 3;
        }

        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.6);
        }

        .carousel-dot.active {
            background: var(--primary);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.6);
            transform: scale(1.2);
        }

        .carousel-dot:hover {
            background: rgba(255, 255, 255, 0.8);
            transform: scale(1.1);
        }

        /* Glassmorphism Search Box */
        .glass-search {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 2.5rem;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .glass-search::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.8s;
        }

        .glass-search:hover::before {
            left: 100%;
        }

        .glass-search:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 80px rgba(16, 185, 129, 0.3);
            border-color: rgba(16, 185, 129, 0.4);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 18px;
            color: white;
            padding: 18px 25px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .glass-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary);
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.4);
            color: white;
            transform: translateY(-2px);
        }

        .glass-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
        }

        /* Enhanced Neon Button */
        .neon-btn {
            background: linear-gradient(45deg, var(--primary), var(--accent));
            border: none;
            border-radius: 18px;
            padding: 18px 35px;
            font-weight: 700;
            color: white;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .neon-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s;
        }

        .neon-btn:hover::before {
            left: 100%;
        }

        .neon-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(16, 185, 129, 0.5);
            filter: brightness(1.1);
        }

        /* Rest of the existing styles remain the same... */
        .morph-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .morph-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .morph-card:hover {
            transform: translateY(-20px) scale(1.05);
            box-shadow: 0 20px 60px rgba(16, 185, 129, 0.3);
        }

        /* Equal Height Cards Fix */
        .row.g-4 .col-md-4 {
            display: flex !important;
        }

        .morph-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100%;
        }

        .morph-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .morph-card h4 {
            margin: 1rem 0 0.5rem 0;
        }

        .morph-card p {
            flex-grow: 1;
            margin-bottom: 1.5rem;
        }

        .morph-card .neon-btn {
            margin-top: auto;
        }

        .morph-card:hover::before {
            opacity: 0.1;
        }

        /* Liquid Background Sections */
        .liquid-bg {
            position: relative;
            overflow: hidden;
        }

        .liquid-bg::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, var(--primary), var(--accent), var(--secondary));
            animation: liquidMove 20s ease-in-out infinite;
            opacity: 0.1;
            z-index: -1;
        }

        @keyframes liquidMove {

            0%,
            100% {
                transform: rotate(0deg) scale(1);
            }

            25% {
                transform: rotate(90deg) scale(1.1);
            }

            50% {
                transform: rotate(180deg) scale(0.9);
            }

            75% {
                transform: rotate(270deg) scale(1.05);
            }
        }

        /* Testimonial Cards with Tilt Effect */
        .tilt-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
            transform-style: preserve-3d;
        }

        /* Glowing Icons */
        .glow-icon {
            font-size: 3rem;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 20px rgba(16, 185, 129, 0.5));
        }

        /* Animated Counter */
        .counter {
            font-size: 3rem;
            font-weight: 900;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Footer with Gradient */
        .gradient-footer {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            position: relative;
        }

        .gradient-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 3.5rem;
                letter-spacing: -1px;
            }

            .hero-subtitle {
                font-size: 1.3rem;
            }

            .glass-search {
                padding: 1.5rem;
            }

            .morph-card {
                margin-bottom: 2rem;
            }

            .carousel-dots {
                bottom: 20px;
            }
        }

        /* Loading Animation */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            transition: opacity 0.5s ease;
        }

        .loading-plane {
            font-size: 4rem;
            color: var(--primary);
            animation: loadingFly 2s ease-in-out infinite;
        }

        @keyframes loadingFly {

            0%,
            100% {
                transform: translateX(-20px) rotate(-5deg);
            }

            50% {
                transform: translateX(20px) rotate(5deg);
            }
        }

        /* Scroll Indicator */
        .scroll-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transform-origin: left;
            transform: scaleX(0);
            z-index: 9999;
        }
    </style>
</head>

<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-plane">✈️</div>
    </div>

    <!-- Scroll Indicator -->
    <div class="scroll-indicator" id="scrollIndicator"></div>

    <!-- Custom Cursor -->
    <div class="cursor" id="cursor"></div>
    <div class="cursor-follower" id="cursorFollower"></div>

    <!-- Particle Background -->
    <div id="particles-js"></div>

    <!-- Glassmorphism Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top glass-nav" id="navbar">
        <div class="container">
            <a class="navbar-brand" href="./landing.php">🚀 SkyNova</a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="#destinations">Destinations</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimonials">Reviews</a></li>
                    <li class="nav-item"><a class="nav-link" href="#stats">Stats</a></li>
                    <li class="nav-item"><a class="nav-link neon-btn ms-2" href="./login.php">Book Now</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- STUNNING HERO SECTION WITH AIRPLANE CAROUSEL -->
    <section class="hero" id="hero">
        <!-- Carousel Background -->
        <div class="hero-carousel" id="heroCarousel">
            <div class="carousel-slide active" style="background-image: url('https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');"></div>
            <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1488646953014-85cb44e25828?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');"></div>
            <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1569629743817-70d8db6c323b?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');"></div>
            <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1436491865332-7a61a109cc05?ixlib=rb-4.0.3&auto=format&fit=crop&w=2074&q=80');"></div>
            <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1518684079-3c830dcef090?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80');"></div>
        </div>

        <!-- Floating Elements -->
        <div class="floating-elements">
            <div class="floating-plane" style="top: 15%; animation-delay: 0s;">✈️</div>
            <div class="floating-plane" style="top: 45%; animation-delay: -8s;">🛩️</div>
            <div class="floating-plane" style="top: 75%; animation-delay: -16s;">✈️</div>
            <div class="floating-plane" style="top: 30%; animation-delay: -12s;">🚁</div>
        </div>

        <div class="hero-content">
            <h1 class="hero-title" id="heroTitle">ELEVATE YOUR<br>JOURNEY</h1>
            <p class="hero-subtitle" id="heroSubtitle">Where luxury meets the sky - SkyNova Airlines</p>

            <div class="glass-search" id="searchBox">
                <form class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control glass-input" placeholder="✈️ From" id="fromInput">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control glass-input" placeholder="🏁 To" id="toInput">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control glass-input" id="dateInput">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control glass-input" placeholder="👥 Passengers" min="1" max="9" id="passengersInput">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn neon-btn w-100" id="searchBtn">
                            <i class="bi bi-search me-2"></i>Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Carousel Navigation Dots -->
        <div class="carousel-dots" id="carouselDots">
            <div class="carousel-dot active" data-slide="0"></div>
            <div class="carousel-dot" data-slide="1"></div>
            <div class="carousel-dot" data-slide="2"></div>
            <div class="carousel-dot" data-slide="3"></div>
            <div class="carousel-dot" data-slide="4"></div>
        </div>
    </section>

    <!-- Liquid Background Destinations -->
    <section id="destinations" class="py-5 liquid-bg">
        <div class="container">
            <h2 class="text-center mb-5" style="font-family: 'Space Grotesk', sans-serif; font-size: 3rem; font-weight: 700;">
                <span class="glow-icon">🌍</span> Explore the World
            </h2>

            <div class="row g-4">
                <div class="col-md-4 d-flex">
                    <div class="morph-card text-center w-100" data-tilt>
                        <img src="https://images.unsplash.com/photo-1518684079-3c830dcef090?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" class="img-fluid rounded-3 mb-3" alt="Paris">
                        <h4>Paris, France</h4>
                        <p>City of Love and Lights</p>
                        <!-- <div class="neon-btn">From $299</div> -->
                    </div>
                </div>
                <div class="col-md-4 d-flex">
                    <div class="morph-card text-center w-100" data-tilt>
                        <img src="https://images.unsplash.com/photo-1518684079-3c830dcef090?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" class="img-fluid rounded-3 mb-3" alt="Tokyo">
                        <h4>Tokyo, Japan</h4>
                        <p>Where Tradition Meets Future</p>
                        <!-- <div class="neon-btn">From $599</div> -->
                    </div>
                </div>
                <div class="col-md-4 d-flex">
                    <div class="morph-card text-center w-100" data-tilt>
                        <img src="https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" class="img-fluid rounded-3 mb-3" alt="New York">
                        <h4>New York, USA</h4>
                        <p>The City That Never Sleeps</p>
                        <!-- <div class="neon-btn">From $199</div> -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features with Morphing Cards -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5" style="font-family: 'Space Grotesk', sans-serif; font-size: 3rem; font-weight: 700;">
                Why Choose SkyNova Airlines?
            </h2>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="morph-card text-center">
                        <i class="bi bi-lightning-charge glow-icon"></i>
                        <h4 class="mt-3">Lightning Fast</h4>
                        <p>Book your flights in under 60 seconds with our AI-powered system</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="morph-card text-center">
                        <i class="bi bi-shield-check glow-icon"></i>
                        <h4 class="mt-3">100% Secure</h4>
                        <p>Military-grade encryption protects your data and payments</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="morph-card text-center">
                        <i class="bi bi-star-fill glow-icon"></i>
                        <h4 class="mt-3">Premium Experience</h4>
                        <p>Luxury service at affordable prices, every single time</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Animated Stats -->
    <section id="stats" class="py-5 liquid-bg">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="counter" data-target="1000000">0</div>
                    <p>Happy Travelers</p>
                </div>
                <div class="col-md-3">
                    <div class="counter" data-target="500">0</div>
                    <p>Destinations</p>
                </div>
                <div class="col-md-3">
                    <div class="counter" data-target="50">0</div>
                    <p>Airlines Partners</p>
                </div>
                <div class="col-md-3">
                    <div class="counter" data-target="99">0</div>
                    <p>Satisfaction Rate</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials with Tilt Effect -->
    <section id="testimonials" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5" style="font-family: 'Space Grotesk', sans-serif; font-size: 3rem; font-weight: 700;">
                <span class="glow-icon">💬</span> What Travelers Say
            </h2>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="tilt-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786" class="rounded-circle me-3" width="60" height="60" alt="Emily">
                            <div>
                                <h6>Sarah Mitchell</h6>
                                <div class="text-warning">⭐⭐⭐⭐⭐</div>
                            </div>
                        </div>
                        <p>"Absolutely incredible experience! The booking was seamless and the flight was perfect."</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="tilt-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d" class="rounded-circle me-3" width="60" height="60" alt="Daniel">
                            <div>
                                <h6>Michael Chen</h6>
                                <div class="text-warning">⭐⭐⭐⭐⭐</div>
                            </div>
                        </div>
                        <p>"Best prices I've found anywhere! The interface is so smooth and modern."</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="tilt-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb" class="rounded-circle me-3" width="60" height="60" alt="Sarah">
                            <div>
                                <h6>Jennifer Walsh</h6>
                                <div class="text-warning">⭐⭐⭐⭐⭐</div>
                            </div>
                        </div>
                        <p>"I'm never using another booking site again. This is the future of travel!"</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gradient Footer -->
    <footer class="gradient-footer py-5 text-center position-relative">
        <div class="container position-relative">
            <h3 class="mb-4" style="font-family: 'Space Grotesk', sans-serif;">Ready to Fly?</h3>
            <p class="mb-4">Join discerning travelers who choose SkyNova Airlines</p>
            <a href="User/login.php" class="neon-btn me-3">Start Your Journey</a>
            <a href="#" class="neon-btn">Download App</a>

            <div class="mt-5">
                <div class="row">
                    <div class="col-md-4">
                        <h5>Quick Links</h5>
                        <p><a href="#" class="text-light">About Us</a></p>
                        <p><a href="#" class="text-light">Careers</a></p>
                        <p><a href="#" class="text-light">Press</a></p>
                    </div>
                    <div class="col-md-4">
                        <h5>Support</h5>
                        <p><a href="#" class="text-light">Help Center</a></p>
                        <p><a href="#" class="text-light">Contact Us</a></p>
                        <p><a href="#" class="text-light">Live Chat</a></p>
                    </div>
                    <div class="col-md-4">
                        <h5>Follow Us</h5>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="#" class="text-light fs-4"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="text-light fs-4"><i class="bi bi-twitter"></i></a>
                            <a href="#" class="text-light fs-4"><i class="bi bi-instagram"></i></a>
                            <a href="#" class="text-light fs-4"><i class="bi bi-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <p>&copy; 2025 SkyNova Airlines. All rights reserved. Elevating travel experiences worldwide.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanilla-tilt@1.8.0/dist/vanilla-tilt.min.js"></script>

    <script>
        // Initialize GSAP
        gsap.registerPlugin(ScrollTrigger);

        // Loading Screen
        window.addEventListener('load', () => {
            gsap.to('#loadingScreen', {
                opacity: 0,
                duration: 1,
                onComplete: () => {
                    document.getElementById('loadingScreen').style.display = 'none';
                }
            });
        });

        // STUNNING CAROUSEL FUNCTIONALITY
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.carousel-dot');
        const totalSlides = slides.length;

        function showSlide(index) {
            // Remove active class from all slides and dots
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));

            // Add active class to current slide and dot
            slides[index].classList.add('active');
            dots[index].classList.add('active');

            currentSlide = index;
        }

        function nextSlide() {
            const next = (currentSlide + 1) % totalSlides;
            showSlide(next);
        }

        // Auto-advance carousel every 5 seconds
        setInterval(nextSlide, 5000);

        // Dot navigation
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
            });
        });

        // Custom Cursor
        const cursor = document.getElementById('cursor');
        const cursorFollower = document.getElementById('cursorFollower');

        document.addEventListener('mousemove', (e) => {
            gsap.to(cursor, {
                x: e.clientX,
                y: e.clientY,
                duration: 0.1
            });

            gsap.to(cursorFollower, {
                x: e.clientX,
                y: e.clientY,
                duration: 0.3
            });
        });

        // Scroll Indicator
        window.addEventListener('scroll', () => {
            const scrolled = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
            gsap.to('#scrollIndicator', {
                scaleX: scrolled / 100,
                duration: 0.1
            });
        });

        // Navbar Scroll Effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Particles.js
        particlesJS('particles-js', {
            particles: {
                number: {
                    value: 60,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: '#10b981'
                },
                shape: {
                    type: 'circle'
                },
                opacity: {
                    value: 0.3,
                    random: false
                },
                size: {
                    value: 3,
                    random: true
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#10b981',
                    opacity: 0.2,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 4,
                    direction: 'none',
                    random: false,
                    straight: false,
                    out_mode: 'out',
                    bounce: false
                }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: {
                        enable: true,
                        mode: 'repulse'
                    },
                    onclick: {
                        enable: true,
                        mode: 'push'
                    },
                    resize: true
                }
            },
            retina_detect: true
        });

        // Hero Animations
        gsap.timeline()
            .from('#heroTitle', {
                y: 120,
                opacity: 0,
                duration: 2,
                ease: 'power3.out'
            })
            .from('#heroSubtitle', {
                y: 60,
                opacity: 0,
                duration: 1.5,
                ease: 'power3.out'
            }, '-=1.2')
            .from('#searchBox', {
                y: 100,
                opacity: 0,
                duration: 1.5,
                ease: 'power3.out'
            }, '-=0.8')
            .from('.carousel-dots', {
                y: 50,
                opacity: 0,
                duration: 1,
                ease: 'power3.out'
            }, '-=0.5');

        // Scroll Animations
        gsap.utils.toArray('.morph-card').forEach((card, i) => {
            gsap.from(card, {
                y: 100,
                opacity: 0,
                duration: 1,
                delay: i * 0.2,
                scrollTrigger: {
                    trigger: card,
                    start: 'top 80%',
                    end: 'bottom 20%',
                    toggleActions: 'play none none reverse'
                }
            });
        });

        // Counter Animation
        gsap.utils.toArray('.counter').forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));

            ScrollTrigger.create({
                trigger: counter,
                start: 'top 80%',
                onEnter: () => {
                    gsap.to(counter, {
                        innerHTML: target,
                        duration: 2.5,
                        snap: {
                            innerHTML: 1
                        },
                        ease: 'power2.out'
                    });
                }
            });
        });

        // Tilt Effect
        VanillaTilt.init(document.querySelectorAll('[data-tilt]'), {
            max: 25,
            speed: 400,
            glare: true,
            'max-glare': 0.5
        });

        // Enhanced Search Form
        document.getElementById('searchBtn').addEventListener('click', (e) => {
            e.preventDefault();

            // Add loading animation
            const btn = e.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>Searching...';

            // Add pulsing effect
            gsap.to(btn, {
                scale: 1.05,
                duration: 0.1,
                yoyo: true,
                repeat: 5
            });

            // Simulate search
            setTimeout(() => {
                btn.innerHTML = originalText;
                window.location.href = 'User/login.php';
            }, 2500);
        });

        // Add spin animation for loading
        const style = document.createElement('style');
        style.textContent = `
      .spin {
        animation: spin 1s linear infinite;
      }
      @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }
    `;
        document.head.appendChild(style);

        // Smooth scrolling for navigation links (Native approach)
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const target = document.querySelector(targetId);

                if (target) {
                    // Add visual feedback
                    gsap.to(this, {
                        scale: 0.95,
                        duration: 0.1,
                        yoyo: true,
                        repeat: 1
                    });

                    // Calculate position with navbar offset
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - 80;

                    // Smooth scroll
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
        // Parallax effect for floating planes
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const planes = document.querySelectorAll('.floating-plane');

            planes.forEach((plane, index) => {
                const speed = 0.5 + (index * 0.2);
                gsap.to(plane, {
                    y: scrolled * speed,
                    duration: 0.1
                });
            });
        });

        console.log('🚀 SkyNova Airlines - Ready for Takeoff! Elevating Your Journey ✈️');
    </script>
</body>

</html>
