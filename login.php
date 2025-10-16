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
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - IoT Farm Monitoring System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'bounce-subtle': 'bounceSubtle 2s infinite',
                        'pulse-slow': 'pulse 3s infinite',
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': {
                                opacity: '0',
                                transform: 'translateY(10px)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'translateY(0)'
                            }
                        },
                        slideUp: {
                            '0%': {
                                opacity: '0',
                                transform: 'translateY(20px)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'translateY(0)'
                            }
                        },
                        bounceSubtle: {
                            '0%, 100%': {
                                transform: 'translateY(0)'
                            },
                            '50%': {
                                transform: 'translateY(-5px)'
                            }
                        },
                        float: {
                            '0%, 100%': {
                                transform: 'translateY(0px) rotate(0deg)'
                            },
                            '50%': {
                                transform: 'translateY(-20px) rotate(180deg)'
                            }
                        }
                    }
                }
            }
        }
    </script>

    <style>
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
            padding: 1.5rem;
        }

        .slide.active {
            opacity: 1;
        }

        .slide-indicators {
            position: absolute;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.375rem;
        }

        .indicator {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .indicator.active {
            background: white;
            transform: scale(1.2);
        }

        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            width: 60px;
            height: 60px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 40px;
            height: 40px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            width: 30px;
            height: 30px;
            top: 80%;
            left: 20%;
            animation-delay: 4s;
        }

        .input-group input:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .dark .input-group input:focus {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .login-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .alert-slide-in {
            animation: slideInDown 0.4s ease-out;
        }

        .alert-slide-out {
            animation: slideOutUp 0.3s ease-in forwards;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
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
                transform: translateY(-15px);
            }
        }

        .progress-bar {
            animation: progressBar 4s linear forwards;
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

<body class="h-full bg-gradient-to-br from-green-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <div class="min-h-screen flex">
        <!-- Left Side - Animated Slideshow -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-green-600 via-emerald-600 to-blue-600 relative slide-container">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="grid" width="8" height="8" patternUnits="userSpaceOnUse">
                            <path d="M 8 0 L 0 0 0 8" fill="none" stroke="currentColor" stroke-width="0.5" />
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid)" />
                </svg>
            </div>

            <!-- Floating Background Elements -->
            <div class="absolute inset-0">
                <div class="floating-element"></div>
                <div class="floating-element"></div>
                <div class="floating-element"></div>
            </div>

            <!-- Slide 1 - Smart Monitoring -->
            <div class="slide active">
                <div class="mb-6">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mb-4 mx-auto">
                        <i class="fas fa-chart-line text-2xl text-white"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-3">Smart Farm Monitoring</h2>
                    <p class="text-sm text-green-100 max-w-sm leading-relaxed">
                        Real-time sensor data collection and analysis for optimal crop management and yield optimization.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 max-w-xs">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                        <i class="fas fa-thermometer-half text-lg text-white mb-1"></i>
                        <div class="text-white text-xs">Temperature</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                        <i class="fas fa-tint text-lg text-white mb-1"></i>
                        <div class="text-white text-xs">Humidity</div>
                    </div>
                </div>
            </div>

            <!-- Slide 2 - Pest Detection -->
            <div class="slide">
                <div class="mb-6">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mb-4 mx-auto">
                        <i class="fas fa-bug text-2xl text-white"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-3">AI Pest Detection</h2>
                    <p class="text-sm text-green-100 max-w-sm leading-relaxed">
                        Advanced computer vision technology to identify and track pest infestations before they spread.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-2">
                        <i class="fas fa-camera text-sm text-white"></i>
                    </div>
                    <div class="text-white text-sm">→</div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-2">
                        <i class="fas fa-brain text-sm text-white"></i>
                    </div>
                    <div class="text-white text-sm">→</div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-2">
                        <i class="fas fa-exclamation-triangle text-sm text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Slide 3 - Analytics Dashboard -->
            <div class="slide">
                <div class="mb-6">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mb-4 mx-auto">
                        <i class="fas fa-chart-pie text-2xl text-white"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-3">Comprehensive Analytics</h2>
                    <p class="text-sm text-green-100 max-w-sm leading-relaxed">
                        Detailed reports and insights to make data-driven decisions for your agricultural operations.
                    </p>
                </div>
                <div class="grid grid-cols-3 gap-2 max-w-xs">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-2 text-center">
                        <div class="text-lg font-bold text-white">24/7</div>
                        <div class="text-xs text-green-100">Monitoring</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-2 text-center">
                        <div class="text-lg font-bold text-white">95%</div>
                        <div class="text-xs text-green-100">Accuracy</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-2 text-center">
                        <div class="text-lg font-bold text-white">∞</div>
                        <div class="text-xs text-green-100">Scalable</div>
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
        <div class="w-full lg:w-1/2 flex items-center justify-center p-4 lg:p-6">
            <div class="max-w-sm w-full animate-fade-in">
                <!-- Header with Back Link and Theme Toggle -->
                <div class="flex items-center justify-between mb-4">
                    <a href="index.php" class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Home
                    </a>
                    <button id="theme-toggle" onclick="toggleTheme()" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i class="fas fa-moon text-xs"></i>
                    </button>
                </div>

                <!-- Logo and Welcome -->
                <div class="text-center mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-green-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-seedling text-white"></i>
                    </div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Welcome Back!</h1>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Sign in to your farm monitoring dashboard</p>
                </div>



                <!-- Alert Messages -->
                <?php if ($error): ?>
                    <div id="error-alert" class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 rounded-lg alert-slide-in relative overflow-hidden">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-500 dark:text-red-400 mr-2 text-xs"></i>
                                <span class="text-red-700 dark:text-red-300 text-xs"><?php echo htmlspecialchars($error); ?></span>
                            </div>
                            <button onclick="dismissAlert('error-alert')" class="text-red-400 dark:text-red-300 hover:text-red-600 dark:hover:text-red-200 ml-2">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-red-300 dark:bg-red-600 progress-bar"></div>
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div id="success-alert" class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 rounded-lg alert-slide-in relative overflow-hidden">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-2 text-xs"></i>
                                <span class="text-green-700 dark:text-green-300 text-xs"><?php echo htmlspecialchars($message); ?></span>
                            </div>
                            <button onclick="dismissAlert('success-alert')" class="text-green-400 dark:text-green-300 hover:text-green-600 dark:hover:text-green-200 ml-2">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-green-300 dark:bg-green-600 progress-bar"></div>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="" class="space-y-4">
                    <div class="input-group">
                        <label for="username" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                        <div class="relative">
                            <input type="text"
                                id="username"
                                name="username"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                placeholder="Enter your username"
                                required
                                autofocus
                                class="w-full px-3 py-2.5 pl-9 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-300 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
                            <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500 text-xs"></i>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="password" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                        <div class="relative">
                            <input type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                                class="w-full px-3 py-2.5 pl-9 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-300 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
                            <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500 text-xs"></i>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="show-password" onchange="togglePasswordVisibility()" class="rounded border-gray-300 dark:border-gray-600 text-green-600 focus:ring-green-500 w-3 h-3 bg-white dark:bg-gray-800">
                            <span class="ml-2 text-xs text-gray-600 dark:text-gray-400">Show password</span>
                        </label>
                        <a href="#" class="text-xs text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 transition-colors">Forgot password?</a>
                    </div>

                    <button type="submit" class="w-full login-btn text-white py-2.5 px-4 rounded-lg font-semibold text-sm transition-all duration-300 hover:shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2 text-xs"></i>
                        Sign In
                    </button>
                </form>
                <!-- Trust Indicators -->
                <div class="mt-6 flex items-center justify-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt text-green-500 dark:text-green-400 mr-1"></i>
                        <span>Secure</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-clock text-blue-500 dark:text-blue-400 mr-1"></i>
                        <span>24/7 Access</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-mobile-alt text-purple-500 dark:text-purple-400 mr-1"></i>
                        <span>Mobile Ready</span>
                    </div>
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
                alert.classList.add('alert-slide-out');
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }
        }

        // Auto-dismiss alerts
        function autoDismissAlerts() {
            const errorAlert = document.getElementById('error-alert');
            const successAlert = document.getElementById('success-alert');

            if (errorAlert) {
                setTimeout(() => dismissAlert('error-alert'), 4000);
            }

            if (successAlert) {
                setTimeout(() => dismissAlert('success-alert'), 3000);
            }
        }

        // Password visibility toggle function
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const showPasswordCheckbox = document.getElementById('show-password');

            if (showPasswordCheckbox.checked) {
                passwordField.type = 'text';
            } else {
                passwordField.type = 'password';
            }

            // Add subtle animation to the password field
            passwordField.style.transform = 'scale(1.01)';
            setTimeout(() => {
                passwordField.style.transform = 'scale(1)';
            }, 150);
        }

        // Theme functionality (same as landing page)
        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme === 'auto' ? (prefersDark ? 'dark' : 'light') : savedTheme;

            document.documentElement.classList.toggle('dark', theme === 'dark');
            updateThemeToggleIcon(theme);
        }

        function toggleTheme() {
            const isDark = document.documentElement.classList.contains('dark');
            const newTheme = isDark ? 'light' : 'dark';

            document.documentElement.classList.toggle('dark', newTheme === 'dark');
            localStorage.setItem('theme', newTheme);
            updateThemeToggleIcon(newTheme);
        }

        function updateThemeToggleIcon(theme) {
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                const icon = themeToggle.querySelector('i');
                if (icon) {
                    icon.className = theme === 'dark' ? 'fas fa-sun text-xs' : 'fas fa-moon text-xs';
                }
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize theme
            initializeTheme();

            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (localStorage.getItem('theme') === 'auto') {
                    document.documentElement.classList.toggle('dark', e.matches);
                    updateThemeToggleIcon(e.matches ? 'dark' : 'light');
                }
            });

            autoDismissAlerts();

            // Clear URL parameters
            if (window.location.search.includes('message=')) {
                const url = new URL(window.location);
                url.searchParams.delete('message');
                window.history.replaceState({}, document.title, url.pathname);
            }

            // Add subtle hover effects to form elements
            document.querySelectorAll('input').forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-1px)';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>

</html>