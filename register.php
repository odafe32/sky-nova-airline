<?php
// Start session at the very beginning - ONLY ONCE
session_start();

// Get form data and error/success messages
$form_data = $_SESSION['form_data'] ?? [];
if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}

$error_message = '';
$success_message = '';

if (isset($_SESSION['register_error'])) {
    $error_message = $_SESSION['register_error'];
    unset($_SESSION['register_error']);
}

if (isset($_SESSION['register_success'])) {
    $success_message = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="SkyNova Airlines - Create Your Premium Account" />
    <meta name="keywords" content="airline, register, signup, SKYNOVA, aviation, premium account">
    <meta name="author" content="SkyNova Airlines" />
    <title>Create Account | SkyNova Airlines</title>
    <link rel="icon" href="User/assets/images/airline-favicon.ico" type="image/x-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Feather Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

    <style>
        :root {
            --brand: #10b981;
            --brand-dark: #059669;
            --brand-light: #34d399;
            --accent: #22c55e;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #ffffff;
            --muted: #64748b;
            --bg-gradient: linear-gradient(135deg, #10b981 0%, #059669 50%, #34d399 100%);
            --card-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #34d399 100%);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            left: 10%;
            animation-delay: 0s;
            animation-duration: 25s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            left: 20%;
            animation-delay: 2s;
            animation-duration: 30s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            left: 70%;
            animation-delay: 4s;
            animation-duration: 20s;
        }

        .shape:nth-child(4) {
            width: 100px;
            height: 100px;
            left: 80%;
            animation-delay: 6s;
            animation-duration: 35s;
        }

        .shape:nth-child(5) {
            width: 140px;
            height: 140px;
            left: 50%;
            animation-delay: 8s;
            animation-duration: 28s;
        }

        .shape:nth-child(6) {
            width: 90px;
            height: 90px;
            left: 30%;
            animation-delay: 10s;
            animation-duration: 22s;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Particles */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: particle-float 15s infinite linear;
        }

        @keyframes particle-float {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-10px) translateX(100px);
                opacity: 0;
            }
        }

        /* Main Container */
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 950px;
            position: relative;
            animation: slideInUp 1s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(60px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 700px;
        }

        /* Left Side - Branding */
        .brand-side {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .brand-side::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: grain-move 20s linear infinite;
        }

        @keyframes grain-move {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(-50px, -50px);
            }
        }

        .brand-logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
            animation: pulse-glow 3s ease-in-out infinite;
        }

        @keyframes pulse-glow {

            0%,
            100% {
                box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
                transform: scale(1);
            }

            50% {
                box-shadow: 0 0 40px rgba(255, 255, 255, 0.5);
                transform: scale(1.05);
            }
        }

        .brand-logo i {
            font-size: 3rem;
            color: white;
        }

        .brand-title {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
            animation: fadeInLeft 1s ease-out 0.5s both;
        }

        .brand-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
            animation: fadeInLeft 1s ease-out 0.7s both;
        }

        .brand-features {
            list-style: none;
            position: relative;
            z-index: 2;
        }

        .brand-features li {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            opacity: 0;
            animation: fadeInLeft 0.6s ease-out forwards;
        }

        .brand-features li:nth-child(1) {
            animation-delay: 0.9s;
        }

        .brand-features li:nth-child(2) {
            animation-delay: 1.1s;
        }

        .brand-features li:nth-child(3) {
            animation-delay: 1.3s;
        }

        .brand-features li:nth-child(4) {
            animation-delay: 1.5s;
        }

        .brand-features i {
            margin-right: 12px;
            color: var(--accent);
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Right Side - Register Form */
        .form-side {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInRight 1s ease-out 0.3s both;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--brand-dark);
            margin-bottom: 8px;
        }

        .form-subtitle {
            color: var(--muted);
            font-size: 1rem;
        }

        .register-form {
            animation: fadeInRight 1s ease-out 0.5s both;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--brand-dark);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid rgba(16, 185, 129, 0.1);
            border-radius: 12px;
            font-size: 1rem;
            background: rgba(248, 250, 252, 0.8);
            transition: all 0.3s ease;
            outline: none;
        }

        .form-input:focus {
            border-color: var(--brand);
            background: white;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            transition: color 0.3s ease;
        }

        .form-input:focus+.input-icon {
            color: var(--brand);
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--brand);
        }

        .form-options {
            margin-bottom: 30px;
        }

        .register-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .register-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .register-btn:hover::before {
            left: 100%;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .register-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .login-link {
            text-align: center;
            margin-top: 30px;
            color: var(--muted);
            animation: fadeInRight 1s ease-out 0.7s both;
        }

        .login-link a {
            color: var(--brand);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: var(--brand-dark);
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            animation: slideDown 0.3s ease-out;
        }

        .alert.success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #16a34a;
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .register-content {
                grid-template-columns: 1fr;
            }

            .brand-side {
                padding: 40px 30px;
                min-height: 300px;
            }

            .brand-title {
                font-size: 2rem;
            }

            .form-side {
                padding: 40px 30px;
            }

            .form-title {
                font-size: 1.5rem;
            }

            .register-container {
                padding: 10px;
            }
        }

        @media (max-width: 480px) {
            .brand-side {
                padding: 30px 20px;
            }

            .form-side {
                padding: 30px 20px;
            }

            .brand-logo {
                width: 80px;
                height: 80px;
            }

            .brand-logo i {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        <div class="particles" id="particles"></div>
    </div>

    <!-- Main Register Container -->
    <div class="register-container">
        <div class="register-card">
            <div class="register-content">
                <!-- Brand Side -->
                <div class="brand-side">
                    <div class="brand-logo">
                        <i data-feather="navigation"></i>
                    </div>
                    <h1 class="brand-title">SKYNOVA</h1>
                    <p class="brand-subtitle">Premium Aviation Experience</p>
                    <ul class="brand-features">
                        <li>
                            <i data-feather="user-plus"></i>
                            Premium Membership
                        </li>
                        <li>
                            <i data-feather="shield"></i>
                            Secure Registration
                        </li>
                        <li>
                            <i data-feather="zap"></i>
                            Instant Activation
                        </li>
                        <li>
                            <i data-feather="globe"></i>
                            Global Access
                        </li>
                    </ul>
                </div>

                <!-- Form Side -->
                <div class="form-side">
                    <div class="form-header">
                        <h2 class="form-title">Create Account</h2>
                        <p class="form-subtitle">Join SkyNova's premium travel community</p>
                    </div>

                    <!-- PHP Error/Success Messages -->
                    <?php if ($error_message): ?>
                        <div class="alert error" style="display: block;">
                            <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                        <div class="alert success" style="display: block;">
                            <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <!-- CHANGED: Form action now points to process_register.php instead of new_process_register.php -->
                    <form class="register-form" id="registerForm" action="process_register.php" method="post">
                        <div class="form-group">
                            <label class="form-label" for="fullname">Full Name</label>
                            <div class="input-wrapper">
                                <input type="text" class="form-input" id="fullname" name="fullname"
                                    placeholder="Enter your full name" required
                                    value="<?php echo htmlspecialchars($form_data['fullname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="input-icon" data-feather="user"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <div class="input-wrapper">
                                <input type="email" class="form-input" id="email" name="email"
                                    placeholder="Enter your email address" required
                                    value="<?php echo htmlspecialchars($form_data['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="input-icon" data-feather="mail"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-wrapper">
                                <input type="password" class="form-input" id="password" name="password"
                                    placeholder="Create a strong password" required>
                                <i class="input-icon" data-feather="lock"></i>
                                <button type="button" class="password-toggle" id="passwordToggle">
                                    <i data-feather="eye"></i>
                                </button>
                            </div>
                            <small style="color: #64748b; font-size: 0.85rem; margin-top: 5px; display: block;">
                                Password must be at least 8 characters with uppercase, lowercase, and number
                            </small>
                        </div>

                        <button type="submit" class="register-btn" id="registerBtn">
                            <div class="btn-content">
                                <div class="spinner" id="spinner"></div>
                                <i data-feather="user-plus" id="registerIcon"></i>
                                <span id="btnText">Create Account</span>
                            </div>
                        </button>
                    </form>

                    <div class="login-link">
                        Already have an account? <a href="login.php">Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        // Initialize Feather Icons
        feather.replace();

        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 60;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Form elements
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        const spinner = document.getElementById('spinner');
        const registerIcon = document.getElementById('registerIcon');
        const btnText = document.getElementById('btnText');

        // Password toggle functionality
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('passwordToggle');

        passwordToggle.addEventListener('click', () => {
            const toggleIcon = passwordToggle.querySelector('i');
            if (toggleIcon) {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                toggleIcon.setAttribute('data-feather', isPassword ? 'eye-off' : 'eye');
                feather.replace();
            }
        });

        // Form submission loading state
        registerForm.addEventListener('submit', (e) => {
            // Show loading state
            registerBtn.classList.add('loading');
            spinner.style.display = 'block';
            registerIcon.style.display = 'none';
            btnText.textContent = 'Creating Account...';
            registerBtn.disabled = true;
        });

        // Input focus animations
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.style.transform = 'scale(1.02)';
            });

            input.addEventListener('blur', () => {
                input.parentElement.style.transform = 'scale(1)';
            });
        });

        // Brand logo interaction
        const brandLogo = document.querySelector('.brand-logo');
        brandLogo.addEventListener('mouseenter', () => {
            brandLogo.style.transform = 'scale(1.1) rotate(5deg)';
        });

        brandLogo.addEventListener('mouseleave', () => {
            brandLogo.style.transform = 'scale(1) rotate(0deg)';
        });

        // Initialize particles on load
        window.addEventListener('load', () => {
            createParticles();
        });

        // Add ripple effect to register button
        registerBtn.addEventListener('click', (e) => {
            if (registerBtn.disabled) return;

            const ripple = document.createElement('span');
            const rect = registerBtn.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            `;

            registerBtn.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        });

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>
</qodoArtifact>