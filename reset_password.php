<?php
/**
 * Reset Password Page
 * Allows users to set a new password using a reset token
 */

session_start();
require_once 'config/database.php';
require_once 'logic/reset_password_logic.php';

$resetLogic = new ResetPasswordLogic();

// Get token from URL
$token = $_GET['token'] ?? '';

// Validate token
if (empty($token)) {
    header('Location: login.php?message=Invalid reset link');
    exit();
}

$tokenValid = $resetLogic->validateToken($token);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $result = $resetLogic->processReset($token);
    if ($result) {
        header('Location: login.php?message=Password reset successful. Please login with your new password.');
        exit();
    }
}

$error = $resetLogic->getError();
$message = $resetLogic->getMessage();
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - IoT Farm Monitoring</title>
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

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark .glass-card {
            background: rgba(17, 24, 39, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .input-group input:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .dark .input-group input:focus {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 relative overflow-hidden">
    <!-- Animated Background Blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="min-h-screen flex items-center justify-center p-4 relative z-10">
        <div class="max-w-md w-full">
            <div class="glass-card rounded-2xl shadow-2xl p-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-4">
                    <a href="login.php" class="inline-flex items-center text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors">
                        <i class="fas fa-arrow-left mr-1 text-xs"></i>
                        Back to Login
                    </a>
                    <button id="theme-toggle" onclick="toggleTheme()" class="p-1.5 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-moon text-sm"></i>
                    </button>
                </div>

                <!-- Icon -->
                <div class="text-center mb-6">
                    <div class="flex justify-center mb-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-lock text-white text-lg"></i>
                        </div>
                    </div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Reset Password</h1>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Enter your new password</p>
                </div>

                <?php if (!$tokenValid): ?>
                    <!-- Invalid Token -->
                    <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 rounded-lg text-center">
                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl mb-2"></i>
                        <p class="text-red-700 dark:text-red-300 text-sm font-medium">Invalid or Expired Reset Link</p>
                        <p class="text-red-600 dark:text-red-400 text-xs mt-2">This password reset link is invalid or has expired.</p>
                        <a href="forgot_password.php" class="inline-block mt-3 text-xs text-blue-600 dark:text-blue-400 hover:underline">
                            Request a new reset link
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Alert Messages -->
                    <?php if ($error): ?>
                        <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-500 dark:text-red-400 mr-2 text-sm"></i>
                                <span class="text-red-700 dark:text-red-300 text-xs"><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="POST" class="space-y-4">
                        <div class="input-group">
                            <label for="password" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">New Password</label>
                            <div class="relative">
                                <input type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Enter new password"
                                    required
                                    minlength="6"
                                    class="w-full px-3 py-2 pl-9 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-300 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                                <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500 text-xs"></i>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimum 6 characters</p>
                        </div>

                        <div class="input-group">
                            <label for="confirm_password" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Confirm Password</label>
                            <div class="relative">
                                <input type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    placeholder="Confirm new password"
                                    required
                                    minlength="6"
                                    class="w-full px-3 py-2 pl-9 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-300 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                                <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500 text-xs"></i>
                            </div>
                        </div>

                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="show-passwords" onchange="togglePasswords()" class="rounded border-gray-300 dark:border-gray-600 text-green-600 focus:ring-green-500 w-3.5 h-3.5 bg-white dark:bg-gray-700">
                            <span class="ml-1.5 text-xs text-gray-600 dark:text-gray-400">Show passwords</span>
                        </label>

                        <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-blue-600 text-white py-2.5 px-4 rounded-lg text-sm font-semibold transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                            <i class="fas fa-check mr-1.5 text-xs"></i>
                            Reset Password
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Theme functionality
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
                    icon.className = theme === 'dark' ? 'fas fa-sun text-sm' : 'fas fa-moon text-sm';
                }
            }
        }

        function togglePasswords() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const checkbox = document.getElementById('show-passwords');
            
            const type = checkbox.checked ? 'text' : 'password';
            password.type = type;
            confirmPassword.type = type;
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();
        });
    </script>
</body>
</html>
