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
        /* Animated Background Blobs */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: 0.7;
            animation: blob 20s infinite;
        }

        .blob-1 {
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            top: -10%;
            left: -10%;
            animation-delay: 0s;
        }

        .blob-2 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            bottom: -10%;
            right: -10%;
            animation-delay: 4s;
        }

        .blob-3 {
            width: 350px;
            height: 350px;
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 8s;
        }

        @keyframes blob {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            25% {
                transform: translate(20px, -50px) scale(1.1);
            }
            50% {
                transform: translate(-20px, 20px) scale(0.9);
            }
            75% {
                transform: translate(50px, 50px) scale(1.05);
            }
        }

        .dark .blob {
            opacity: 0.4;
        }

        /* Input Focus Effects */
        .input-group input:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .dark .input-group input:focus {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        /* Login Button */
        .login-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        /* Alert Animations */
        .alert-slide-in {
            animation: slideInDown 0.4s ease-out;
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

        /* Progress Bar */
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

        /* Glass Effect for Login Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .dark .glass-card {
            background: rgba(17, 24, 39, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body class="h-full bg-gray-50 dark:bg-gray-900 relative overflow-hidden">
    <!-- Animated Background Blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="min-h-screen flex relative z-10">
        <!-- Login Form Container -->
        <div class="w-full flex items-center justify-center p-4">
            <div class="max-w-md w-full animate-fade-in">
                <!-- Glass Card Effect -->
                <div class="glass-card rounded-2xl shadow-2xl p-8">
                    <!-- Header with Back Link and Theme Toggle -->
                    <div class="flex items-center justify-between mb-4">
                        <a href="index.php" class="inline-flex items-center text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors">
                            <i class="fas fa-arrow-left mr-1 text-xs"></i>
                            Back to Home
                        </a>
                        <button id="theme-toggle" onclick="toggleTheme()" class="p-1.5 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-moon text-sm"></i>
                        </button>
                    </div>

                    <!-- Logo and Welcome -->
                    <div class="text-center mb-6">
                        <div class="flex justify-center mb-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                                <img src="includes/sagay.png" alt="Sagay Eco-Farm Logo" class="w-9 h-9 object-contain">
                            </div>
                        </div>
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Welcome Back!</h1>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Sign in to your farm monitoring dashboard</p>
                    </div>



                    <!-- Alert Messages -->
                    <?php if ($error): ?>
                        <div id="error-alert" class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 rounded-lg alert-slide-in relative overflow-hidden">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-circle text-red-500 dark:text-red-400 mr-2 text-sm"></i>
                                    <span class="text-red-700 dark:text-red-300 text-xs"><?php echo htmlspecialchars($error); ?></span>
                                </div>
                                <button onclick="dismissAlert('error-alert')" class="text-red-400 dark:text-red-300 hover:text-red-600 dark:hover:text-red-200 ml-2">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                            <div class="absolute bottom-0 left-0 h-0.5 bg-red-400 dark:bg-red-600 progress-bar"></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div id="success-alert" class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 rounded-lg alert-slide-in relative overflow-hidden">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-2 text-sm"></i>
                                    <span class="text-green-700 dark:text-green-300 text-xs"><?php echo htmlspecialchars($message); ?></span>
                                </div>
                                <button onclick="dismissAlert('success-alert')" class="text-green-400 dark:text-green-300 hover:text-green-600 dark:hover:text-green-200 ml-2">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                            <div class="absolute bottom-0 left-0 h-0.5 bg-green-400 dark:bg-green-600 progress-bar"></div>
                        </div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="POST" action="" class="space-y-4">
                        <div class="input-group">
                            <label for="username" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Username</label>
                            <div class="relative">
                                <input type="text"
                                    id="username"
                                    name="username"
                                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                    placeholder="Enter your username"
                                    required
                                    autofocus
                                    class="w-full px-3 py-2 pl-9 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-300 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                                <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500 text-xs"></i>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="password" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                            <div class="relative">
                                <input type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Enter your password"
                                    required
                                    class="w-full px-3 py-2 pl-9 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-300 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                                <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500 text-xs"></i>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" id="show-password" onchange="togglePasswordVisibility()" class="rounded border-gray-300 dark:border-gray-600 text-green-600 focus:ring-green-500 w-3.5 h-3.5 bg-white dark:bg-gray-700">
                                <span class="ml-1.5 text-xs text-gray-600 dark:text-gray-400">Show password</span>
                            </label>
                            <a href="forgot_password.php" class="text-xs text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 transition-colors font-medium">Forgot password?</a>
                        </div>

                        <button type="submit" class="w-full login-btn text-white py-2.5 px-4 rounded-lg text-sm font-semibold transition-all duration-300 hover:shadow-lg">
                            <i class="fas fa-sign-in-alt mr-1.5 text-xs"></i>
                            Sign In
                        </button>
                    </form>

                    <!-- Trust Indicators -->
                    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center">
                                <i class="fas fa-shield-alt text-green-500 dark:text-green-400 mr-1 text-xs"></i>
                                <span>Secure</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock text-blue-500 dark:text-blue-400 mr-1 text-xs"></i>
                                <span>24/7 Access</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-mobile-alt text-purple-500 dark:text-purple-400 mr-1 text-xs"></i>
                                <span>Mobile Ready</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

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
                    icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
                }
            }
        }

        function dismissAlert(alertId) {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
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
        });
    </script>
</body>

</html>