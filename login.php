<?php

/**
 * Login Page for IoT Farm Monitoring System
 * 
 * Handles user authentication with role-based access control
 */

// Start output buffering to prevent header issues
ob_start();

// Start session first, before any output
session_start();

require_once 'config/database.php';
require_once 'logic/login_logic.php';

// Initialize login logic
$loginLogic = new LoginLogic();

// Check if already logged in and redirect if so
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    ob_end_clean(); // Clear any output buffer
    header('Location: dashboard.php');
    exit();
}

// Process login form submission
$loginSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginSuccess = $loginLogic->processLogin();

    if ($loginSuccess) {
        ob_end_clean(); // Clear any output buffer
        // Determine redirect URL based on user role
        $redirectUrl = 'dashboard.php';
        if (isset($_GET['redirect'])) {
            $redirectUrl = $_GET['redirect'];
        }
        header('Location: ' . $redirectUrl);
        exit();
    }
}

// Get data for view
$error = $loginLogic->getError();
$message = $loginLogic->getMessage();

// Check for logout or other messages from URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Check for session messages and clear them after reading
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Now that all PHP logic is complete, we can start HTML output
// Flush the output buffer since we're not redirecting
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IoT Farm Monitoring System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .slide-container {
            position: relative;
            overflow: hidden;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
        }

        .slide.active {
            opacity: 1;
        }

        .slide-indicators {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.5rem;
        }

        .indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .indicator.active {
            background: white;
            transform: scale(1.2);
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            width: 40px;
            height: 40px;
            top: 80%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .form-container {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .login-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
        }

        .alert-message {
            animation: slideInDown 0.5s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideOutUp {
            from {
                opacity: 1;
                transform: translateY(0);
            }

            to {
                opacity: 0;
                transform: translateY(-20px);
            }
        }

        .alert-dismiss {
            animation: slideOutUp 0.3s ease-out forwards;
        }

        @keyframes progressBar {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Left Side - Animated Slideshow -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-700 relative slide-container">
            <!-- Floating Background Elements -->
            <div class="floating-elements">
                <div class="floating-element"></div>
                <div class="floating-element"></div>
                <div class="floating-element"></div>
            </div>

            <!-- Slide 1 - Smart Monitoring -->
            <div class="slide active">
                <div class="mb-8">
                    <div class="w-24 h-24 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-chart-line text-4xl text-white"></i>
                    </div>
                    <h2 class="text-4xl font-bold text-white mb-4">Smart Farm Monitoring</h2>
                    <p class="text-xl text-blue-100 max-w-md">
                        Real-time sensor data collection and analysis for optimal crop management and yield optimization.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-4 max-w-sm">
                    <div class="bg-white bg-opacity-10 rounded-lg p-4 text-center">
                        <i class="fas fa-thermometer-half text-2xl text-white mb-2"></i>
                        <div class="text-white text-sm">Temperature</div>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-lg p-4 text-center">
                        <i class="fas fa-tint text-2xl text-white mb-2"></i>
                        <div class="text-white text-sm">Humidity</div>
                    </div>
                </div>
            </div>

            <!-- Slide 2 - Pest Detection -->
            <div class="slide">
                <div class="mb-8">
                    <div class="w-24 h-24 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-bug text-4xl text-white"></i>
                    </div>
                    <h2 class="text-4xl font-bold text-white mb-4">AI Pest Detection</h2>
                    <p class="text-xl text-blue-100 max-w-md">
                        Advanced computer vision technology to identify and track pest infestations before they spread.
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="bg-white bg-opacity-10 rounded-lg p-3">
                        <i class="fas fa-camera text-xl text-white"></i>
                    </div>
                    <div class="text-white">→</div>
                    <div class="bg-white bg-opacity-10 rounded-lg p-3">
                        <i class="fas fa-brain text-xl text-white"></i>
                    </div>
                    <div class="text-white">→</div>
                    <div class="bg-white bg-opacity-10 rounded-lg p-3">
                        <i class="fas fa-exclamation-triangle text-xl text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Slide 3 - Analytics Dashboard -->
            <div class="slide">
                <div class="mb-8">
                    <div class="w-24 h-24 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-chart-pie text-4xl text-white"></i>
                    </div>
                    <h2 class="text-4xl font-bold text-white mb-4">Comprehensive Analytics</h2>
                    <p class="text-xl text-blue-100 max-w-md">
                        Detailed reports and insights to make data-driven decisions for your agricultural operations.
                    </p>
                </div>
                <div class="grid grid-cols-3 gap-3 max-w-xs">
                    <div class="bg-white bg-opacity-10 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-white">24/7</div>
                        <div class="text-xs text-blue-100">Monitoring</div>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-white">95%</div>
                        <div class="text-xs text-blue-100">Accuracy</div>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-white">∞</div>
                        <div class="text-xs text-blue-100">Scalable</div>
                    </div>
                </div>
            </div>

            <!-- Slide Indicators -->
            <div class="slide-indicators">
                <div class="indicator active" onclick="goToSlide(0)"></div>
                <div class="indicator" onclick="goToSlide(1)"></div>
                <div class="indicator" onclick="goToSlide(2)"></div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6">
            <div class="max-w-sm w-full">
                <!-- Logo and Welcome -->
                <div class="text-center mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-seedling text-lg text-white"></i>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900 mb-1">Hello Again!</h1>
                    <p class="text-sm text-gray-600">Welcome back to your farm monitoring dashboard</p>
                </div>

                <!-- Alert Messages -->
                <?php if ($error): ?>
                    <div id="error-alert" class="mb-4 bg-red-50 border-l-4 border-red-400 p-3 rounded-r-lg alert-message relative overflow-hidden">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-400 mr-2 text-sm"></i>
                                <span class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></span>
                            </div>
                            <button onclick="dismissAlert('error-alert')" class="text-red-400 hover:text-red-600 ml-3 z-10 relative text-sm">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="absolute bottom-0 left-0 h-1 bg-red-300 progress-bar" style="animation: progressBar 5s linear forwards;"></div>
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div id="success-alert" class="mb-4 bg-green-50 border-l-4 border-green-400 p-3 rounded-r-lg alert-message relative overflow-hidden">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-400 mr-2 text-sm"></i>
                                <span class="text-green-700 text-sm"><?php echo htmlspecialchars($message); ?></span>
                            </div>
                            <button onclick="dismissAlert('success-alert')" class="text-green-400 hover:text-green-600 ml-3 z-10 relative text-sm">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="absolute bottom-0 left-0 h-1 bg-green-300 progress-bar" style="animation: progressBar 3s linear forwards;"></div>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="" class="space-y-4">
                    <div class="input-group">
                        <label for="username" class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                        <div class="relative">
                            <input type="text"
                                id="username"
                                name="username"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                placeholder="Enter your username"
                                required
                                autofocus
                                class="w-full px-3 py-2 pl-9 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300">
                            <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="password" class="block text-xs font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <input type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                                class="w-full px-3 py-2 pl-9 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300">
                            <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-3 h-3">
                            <span class="ml-2 text-xs text-gray-600">Remember Me</span>
                        </label>
                        <a href="#" class="text-xs text-blue-600 hover:text-blue-500">Forgot Password?</a>
                    </div>

                    <button type="submit" class="w-full login-btn text-white py-2 px-4 rounded-lg font-semibold text-sm">
                        Login
                    </button>
                </form>

                <!-- Google Sign In (Placeholder) -->
                <div class="mt-4">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-xs">
                            <span class="px-2 bg-white text-gray-500">Or continue with</span>
                        </div>
                    </div>

                    <button type="button" class="mt-3 w-full flex items-center justify-center px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 transition-colors duration-300 text-sm">
                        <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Sign in with Google
                    </button>
                </div>


                <!-- Sign Up Link -->
                <div class="text-center mt-5">
                    <p class="text-sm text-gray-600">
                        Don't have an account yet?
                        <a href="#" class="text-blue-600 hover:text-blue-500 font-semibold">Sign Up</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const indicators = document.querySelectorAll('.indicator');

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }

        function goToSlide(index) {
            currentSlide = index;
            showSlide(currentSlide);
        }

        // Auto-advance slides every 5 seconds
        setInterval(nextSlide, 5000);

        // Dismiss alert function
        function dismissAlert(alertId) {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.classList.add('alert-dismiss');
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }
        }

        // Auto-dismiss alerts after 5 seconds
        function autoDismissAlerts() {
            const errorAlert = document.getElementById('error-alert');
            const successAlert = document.getElementById('success-alert');

            if (errorAlert) {
                setTimeout(() => dismissAlert('error-alert'), 5000);
            }

            if (successAlert) {
                setTimeout(() => dismissAlert('success-alert'), 3000); // Success messages dismiss faster
            }
        }

        // Fill credentials function for demo accounts
        function fillCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;

            // Add a subtle animation to indicate the fields were filled
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');

            usernameField.style.transform = 'scale(1.02)';
            passwordField.style.transform = 'scale(1.02)';

            setTimeout(() => {
                usernameField.style.transform = 'scale(1)';
                passwordField.style.transform = 'scale(1)';
            }, 200);
        }

        // Initialize auto-dismiss for alerts
        document.addEventListener('DOMContentLoaded', function() {
            autoDismissAlerts();

            // Clear URL parameters after showing message to prevent persistence
            if (window.location.search.includes('message=')) {
                const url = new URL(window.location);
                url.searchParams.delete('message');
                window.history.replaceState({}, document.title, url.pathname);
            }
        });

        // Add focus animations
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>

</html>