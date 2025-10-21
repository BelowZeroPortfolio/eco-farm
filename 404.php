<?php
/**
 * 404 Error Page - Page Not Found
 * IoT Farm Monitoring System
 */

// Start session to check if user is logged in
session_start();

$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | IoT Farm Monitoring System</title>
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
        * {
            box-sizing: border-box;
        }

        :root {
            --hue: 142;
            --sat: 70%;
            --light: hsl(var(--hue), var(--sat), 95%);
            --dark: hsl(var(--hue), var(--sat), 5%);
            --trans-dur: 0.3s;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        .face {
            display: block;
            width: 20em;
            height: auto;
            max-width: 90vw;
            color: #10b981;
        }

        .dark .face {
            color: #34d399;
        }

        .face__eyes,
        .face__eye-lid,
        .face__mouth-left,
        .face__mouth-right,
        .face__nose,
        .face__pupil {
            animation: eyes 1s 0.3s cubic-bezier(0.65, 0, 0.35, 1) forwards;
        }

        .face__eye-lid,
        .face__pupil {
            animation-duration: 4s;
            animation-delay: 1.3s;
            animation-iteration-count: infinite;
        }

        .face__eye-lid {
            animation-name: eye-lid;
        }

        .face__mouth-left,
        .face__mouth-right {
            animation-timing-function: cubic-bezier(0.33, 1, 0.68, 1);
        }

        .face__mouth-left {
            animation-name: mouth-left;
        }

        .face__mouth-right {
            animation-name: mouth-right;
        }

        .face__nose {
            animation-name: nose;
        }

        .face__pupil {
            animation-name: pupil;
        }

        /* Animations */
        @keyframes eye-lid {

            from,
            40%,
            45%,
            to {
                transform: translateY(0);
            }

            42.5% {
                transform: translateY(17.5px);
            }
        }

        @keyframes eyes {
            from {
                transform: translateY(112.5px);
            }

            to {
                transform: translateY(15px);
            }
        }

        @keyframes pupil {

            from,
            37.5%,
            40%,
            45%,
            87.5%,
            to {
                stroke-dashoffset: 0;
                transform: translate(0, 0);
            }

            12.5%,
            25%,
            62.5%,
            75% {
                stroke-dashoffset: 0;
                transform: translate(-35px, 0);
            }

            42.5% {
                stroke-dashoffset: 35;
                transform: translate(0, 17.5px);
            }
        }

        @keyframes mouth-left {

            from,
            50% {
                stroke-dashoffset: -102;
            }

            to {
                stroke-dashoffset: 0;
            }
        }

        @keyframes mouth-right {

            from,
            50% {
                stroke-dashoffset: 102;
            }

            to {
                stroke-dashoffset: 0;
            }
        }

        @keyframes nose {
            from {
                transform: translate(0, 0);
            }

            to {
                transform: translate(0, 22.5px);
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        /* Background blobs */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: 0.3;
            animation: blob 20s infinite;
        }

        .blob-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            top: -10%;
            left: -10%;
        }

        .blob-2 {
            width: 350px;
            height: 350px;
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            bottom: -10%;
            right: -10%;
            animation-delay: 4s;
        }

        @keyframes blob {

            0%,
            100% {
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
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center relative overflow-hidden transition-colors duration-300 ">
    <!-- Background Blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <!-- Main Content -->
    <div class="relative z-10 text-center px-4 py-8 mt-20">
        <!-- Animated Face -->
        <div class="flex justify-center mb-8 float-animation">
            <svg class="face" viewBox="0 0 320 380" width="320px" height="380px" aria-label="A 404 becomes a face, looks to the sides, and blinks. The 4s slide up, the 0 slides down, and then a mouth appears.">
                <g fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="25">
                    <g class="face__eyes" transform="translate(0, 112.5)">
                        <g transform="translate(15, 0)">
                            <polyline class="face__eye-lid" points="37,0 0,120 75,120" />
                            <polyline class="face__pupil" points="55,120 55,155" stroke-dasharray="35 35" />
                        </g>
                        <g transform="translate(230, 0)">
                            <polyline class="face__eye-lid" points="37,0 0,120 75,120" />
                            <polyline class="face__pupil" points="55,120 55,155" stroke-dasharray="35 35" />
                        </g>
                    </g>
                    <rect class="face__nose" rx="4" ry="4" x="132.5" y="112.5" width="55" height="155" />
                    <g stroke-dasharray="102 102" transform="translate(65, 334)">
                        <path class="face__mouth-left" d="M 0 30 C 0 30 40 0 95 0" stroke-dashoffset="-102" />
                        <path class="face__mouth-right" d="M 95 0 C 150 0 190 30 190 30" stroke-dashoffset="102" />
                    </g>
                </g>
            </svg>
        </div>

        <!-- Error Message -->
        <div class="max-w-2xl mx-auto">
            <h1 class="text-6xl md:text-8xl font-bold text-gray-900 dark:text-white mb-4">404</h1>
            <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 dark:text-gray-200 mb-4">
                Oops! Page Not Found
            </h2>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <i class="fas fa-home mr-2"></i>
                        Go to Dashboard
                    </a>
                    <a href="javascript:history.back()" class="inline-flex items-center px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Go Back
                    </a>
                <?php else: ?>
                    <a href="index.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <i class="fas fa-home mr-2"></i>
                        Go to Home
                    </a>
                    <a href="login.php" class="inline-flex items-center px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-300">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Sign In
                    </a>
                <?php endif; ?>
                </div>
        </div>

        <!-- Theme Toggle -->
        <div class="fixed top-4 right-4">
            <button id="theme-toggle" onclick="toggleTheme()" class="p-3 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                <i class="fas fa-moon"></i>
            </button>
        </div>

        <!-- Footer -->
        <div class="mt-16 text-sm text-gray-500 dark:text-gray-400">
            <p>
                <i class="fas fa-leaf text-green-500 mr-1"></i>
                Sagay Eco-Farm IoT Agricultural System
            </p>
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
                    icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
                }
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();

            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (localStorage.getItem('theme') === 'auto') {
                    document.documentElement.classList.toggle('dark', e.matches);
                    updateThemeToggleIcon(e.matches ? 'dark' : 'light');
                }
            });
        });
    </script>
</body>

</html>
