<?php
/**
 * Professional Header Component with Design System Integration
 * 
 * Enhanced header with typography, design tokens, and professional styling
 */

// Include design system
require_once 'includes/design-system.php';

// Include error handling utilities
require_once 'includes/error-display.php';
require_once 'includes/validation-rules.php';

// Get page title if not set
if (!isset($pageTitle)) {
    $pageTitle = 'IoT Farm Monitoring System';
}

// Get additional CSS files if specified
$additionalCSS = $additionalCSS ?? [];

// Get additional JS files if specified  
$additionalJS = $additionalJS ?? [];
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Professional IoT Farm Monitoring System - Real-time agricultural data and analytics">
    <meta name="keywords" content="IoT, agriculture, farming, sensors, monitoring, analytics">
    <meta name="author" content="Farm Monitor Team">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Preconnect to external domains for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Professional Typography - Inter & Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome Pro Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Additional CSS files -->
    <?php foreach ($additionalCSS as $cssFile): ?>
        <link href="<?php echo htmlspecialchars($cssFile); ?>" rel="stylesheet">
    <?php endforeach; ?>
    
    <!-- Enhanced Tailwind Configuration with Design System -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                        'display': ['Poppins', 'Inter', 'system-ui', 'sans-serif'],
                        'mono': ['JetBrains Mono', 'Fira Code', 'Monaco', 'Consolas', 'monospace']
                    },
                    colors: {
                        // Primary theme colors (easily switchable)
                        primary: {
                            50: '<?php echo getThemeColor("primary", "50"); ?>',
                            100: '<?php echo getThemeColor("primary", "100"); ?>',
                            200: '<?php echo getThemeColor("primary", "200"); ?>',
                            300: '<?php echo getThemeColor("primary", "300"); ?>',
                            400: '<?php echo getThemeColor("primary", "400"); ?>',
                            500: '<?php echo getThemeColor("primary", "500"); ?>',
                            600: '<?php echo getThemeColor("primary", "600"); ?>',
                            700: '<?php echo getThemeColor("primary", "700"); ?>',
                            800: '<?php echo getThemeColor("primary", "800"); ?>',
                            900: '<?php echo getThemeColor("primary", "900"); ?>',
                        },
                        secondary: {
                            50: '<?php echo getThemeColor("secondary", "50"); ?>',
                            100: '<?php echo getThemeColor("secondary", "100"); ?>',
                            200: '<?php echo getThemeColor("secondary", "200"); ?>',
                            300: '<?php echo getThemeColor("secondary", "300"); ?>',
                            400: '<?php echo getThemeColor("secondary", "400"); ?>',
                            500: '<?php echo getThemeColor("secondary", "500"); ?>',
                            600: '<?php echo getThemeColor("secondary", "600"); ?>',
                            700: '<?php echo getThemeColor("secondary", "700"); ?>',
                            800: '<?php echo getThemeColor("secondary", "800"); ?>',
                            900: '<?php echo getThemeColor("secondary", "900"); ?>',
                        },
                        accent: {
                            50: '<?php echo getThemeColor("accent", "50"); ?>',
                            100: '<?php echo getThemeColor("accent", "100"); ?>',
                            200: '<?php echo getThemeColor("accent", "200"); ?>',
                            300: '<?php echo getThemeColor("accent", "300"); ?>',
                            400: '<?php echo getThemeColor("accent", "400"); ?>',
                            500: '<?php echo getThemeColor("accent", "500"); ?>',
                            600: '<?php echo getThemeColor("accent", "600"); ?>',
                            700: '<?php echo getThemeColor("accent", "700"); ?>',
                            800: '<?php echo getThemeColor("accent", "800"); ?>',
                            900: '<?php echo getThemeColor("accent", "900"); ?>',
                        }
                    },
                    spacing: {
                        '18': '4.5rem',
                        '88': '22rem',
                        '128': '32rem',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-in': 'slideIn 0.3s ease-out',
                        'bounce-subtle': 'bounceSubtle 2s infinite',
                        'pulse-slow': 'pulse 3s infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideIn: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(0)' }
                        },
                        bounceSubtle: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-5px)' }
                        }
                    },
                    boxShadow: {
                        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                        'medium': '0 4px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                        'strong': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    }
                }
            }
        }
    </script>
    
    <!-- Professional Design System Styles -->
    <style>
        <?php echo generateCSSCustomProperties(); ?>
        
        /* Enhanced Typography Hierarchy */
        .text-display-2xl { font-size: 4.5rem; line-height: 1; font-weight: 800; }
        .text-display-xl { font-size: 3.75rem; line-height: 1; font-weight: 800; }
        .text-display-lg { font-size: 3rem; line-height: 1.1; font-weight: 700; }
        .text-display-md { font-size: 2.25rem; line-height: 1.2; font-weight: 700; }
        .text-display-sm { font-size: 1.875rem; line-height: 1.3; font-weight: 600; }
        
        .text-heading-xl { font-size: 1.5rem; line-height: 1.4; font-weight: 600; }
        .text-heading-lg { font-size: 1.25rem; line-height: 1.4; font-weight: 600; }
        .text-heading-md { font-size: 1.125rem; line-height: 1.5; font-weight: 600; }
        .text-heading-sm { font-size: 1rem; line-height: 1.5; font-weight: 600; }
        
        .text-body-xl { font-size: 1.125rem; line-height: 1.6; font-weight: 400; }
        .text-body-lg { font-size: 1rem; line-height: 1.6; font-weight: 400; }
        .text-body-md { font-size: 0.875rem; line-height: 1.5; font-weight: 400; }
        .text-body-sm { font-size: 0.75rem; line-height: 1.5; font-weight: 400; }
        
        /* Professional Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--color-secondary-100);
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--color-secondary-300);
            border-radius: 3px;
            transition: background-color 0.2s ease;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--color-secondary-400);
        }
        
        /* Enhanced Focus States for Accessibility */
        .focus-ring:focus {
            outline: 2px solid transparent;
            outline-offset: 2px;
            box-shadow: 0 0 0 3px var(--color-primary-500), 0 0 0 1px var(--color-primary-600);
        }
        
        /* Professional Card System */
        .card-elevated {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-soft);
            border: 1px solid var(--color-secondary-200);
            transition: all var(--duration-normal) cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-elevated:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
            border-color: var(--color-secondary-300);
        }
        
        .card-interactive {
            cursor: pointer;
            transition: all var(--duration-normal) cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-interactive:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-strong);
        }
        
        .card-interactive:active {
            transform: translateY(-1px);
            box-shadow: var(--shadow-medium);
        }
        
        /* Loading States */
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        .loading-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        .loading-skeleton {
            background: linear-gradient(90deg, var(--color-secondary-200) 25%, var(--color-secondary-100) 50%, var(--color-secondary-200) 75%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        /* Professional Animations */
        .animate-fade-in {
            animation: fadeIn var(--duration-slow) ease-out;
        }
        
        .animate-slide-up {
            animation: slideUp var(--duration-normal) ease-out;
        }
        
        .animate-scale-in {
            animation: scaleIn var(--duration-normal) ease-out;
        }
        
        /* Toast Notification Animations */
        .toast-notification {
            animation: slideInRight 0.3s ease-out;
        }
        
        .toast-notification.closing {
            animation: slideOutRight 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes slideOutRight {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(100%); }
        }
        
        /* Status Indicators */
        .status-online {
            position: relative;
        }
        
        .status-online::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 8px;
            height: 8px;
            background: #10b981;
            border: 2px solid white;
            border-radius: 50%;
            animation: pulse-green 2s infinite;
        }
        
        @keyframes pulse-green {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Professional Form Elements */
        .form-input {
            background: white;
            border: 1px solid var(--color-secondary-300);
            border-radius: var(--radius-lg);
            padding: 0.75rem 1rem;
            font-size: var(--font-size-base);
            transition: all var(--duration-fast) ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--color-primary-500);
            box-shadow: 0 0 0 3px var(--color-primary-100);
        }
        
        .form-input:invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        /* Button System */
        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-500));
            color: white;
            border: none;
            border-radius: var(--radius-lg);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: var(--font-size-sm);
            transition: all var(--duration-fast) ease;
            box-shadow: var(--shadow-sm);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--color-primary-700), var(--color-primary-600));
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: var(--shadow-sm);
        }
        
        /* Responsive Typography */
        @media (max-width: 640px) {
            .text-display-2xl { font-size: 3rem; }
            .text-display-xl { font-size: 2.5rem; }
            .text-display-lg { font-size: 2rem; }
            .text-display-md { font-size: 1.75rem; }
        }
        
        /* Dark mode support */
        .dark {
            --color-background: var(--color-secondary-900);
            --color-surface: var(--color-secondary-800);
            --color-text-primary: var(--color-secondary-100);
            --color-text-secondary: var(--color-secondary-300);
        }
        
        /* Loading states and animations */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            text-align: center;
        }
        
        .dark .loading-content {
            background: var(--color-secondary-800);
            color: var(--color-secondary-100);
        }
        
        /* Responsive chart containers */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        @media (max-width: 640px) {
            .chart-container {
                height: 250px;
            }
        }
        
        /* Enhanced mobile responsiveness */
        @media (max-width: 768px) {
            .mobile-stack {
                flex-direction: column;
            }
            
            .mobile-full {
                width: 100%;
            }
            
            .mobile-hidden {
                display: none;
            }
            
            .mobile-text-sm {
                font-size: 0.875rem;
            }
        }
        
        /* Improved focus states for accessibility */
        .focus-visible:focus-visible {
            outline: 2px solid var(--color-primary-500);
            outline-offset: 2px;
        }
        
        /* Enhanced button states */
        .btn-loading {
            position: relative;
            color: transparent;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        /* Theme transition */
        * {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }
    </style>
    
    <!-- Additional JavaScript files -->
    <?php foreach ($additionalJS as $jsFile): ?>
        <script src="<?php echo htmlspecialchars($jsFile); ?>"></script>
    <?php endforeach; ?>
    
    <!-- Include notification system -->
    <?php 
    if (file_exists('includes/notifications.php')) {
        require_once 'includes/notifications.php';
    }
    ?>
    
    <!-- Performance and Analytics -->
    <script>
        // Performance monitoring
        window.addEventListener('load', function() {
            // Log page load time for performance monitoring
            const loadTime = performance.now();
            console.log(`Page loaded in ${Math.round(loadTime)}ms`);
        });
        
        // Theme switching functionality
        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme === 'auto' ? (prefersDark ? 'dark' : 'light') : savedTheme;
            
            document.documentElement.classList.toggle('dark', theme === 'dark');
            
            // Update theme toggle button if it exists
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                updateThemeToggleIcon(theme);
            }
        }
        
        function toggleTheme() {
            const isDark = document.documentElement.classList.contains('dark');
            const newTheme = isDark ? 'light' : 'dark';
            
            document.documentElement.classList.toggle('dark', newTheme === 'dark');
            localStorage.setItem('theme', newTheme);
            
            updateThemeToggleIcon(newTheme);
            
            // Dispatch theme change event
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));
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
        
        // Initialize theme on page load
        initializeTheme();
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (localStorage.getItem('theme') === 'auto') {
                document.documentElement.classList.toggle('dark', e.matches);
                updateThemeToggleIcon(e.matches ? 'dark' : 'light');
            }
        });
    </script>
    
    <!-- Error Handling CSS -->
    <?php echo getErrorHandlingCSS(); ?>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 font-sans antialiased transition-colors duration-300">