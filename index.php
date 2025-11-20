<?php
// Start session to check if user is already logged in
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit();
}

// Set page title for header component
$pageTitle = 'IoT Farm Monitoring System - Smart Agriculture Solutions';

// Include shared header
include 'includes/header.php';
?>

<!-- Landing Page Content -->
<div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">

    <!-- Navigation Header -->
    <nav class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-green-500 rounded-xl flex items-center justify-center mr-3">
                            <i class="fas fa-seedling text-white text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">FarmMonitor</h1>
                            <p class="text-xs text-gray-500 dark:text-gray-400">IoT Agriculture</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-8">
                        <a href="#features" class="text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 px-3 py-2 text-sm font-medium transition-colors">Features</a>
                        <a href="#about" class="text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 px-3 py-2 text-sm font-medium transition-colors">About</a>
                        <a href="#contact" class="text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 px-3 py-2 text-sm font-medium transition-colors">Contact</a>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-4">
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" onclick="toggleTheme()" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                        <i class="fas fa-moon"></i>
                    </button>

                    <!-- Login Button -->
                    <a href="login.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all hover:shadow-lg hover:-translate-y-0.5">
                        Sign In
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <svg class="w-full h-full" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5" />
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#grid)" />
            </svg>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24 relative">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <!-- Hero Content -->
                <div class="animate-fade-in">
                    <div class="inline-flex items-center px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full text-xs font-medium mb-4">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                        Smart Agriculture Technology
                    </div>

                    <h1 class="text-3xl lg:text-5xl font-bold text-gray-900 dark:text-white mb-4 leading-tight">
                        Monitor Your Farm with
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-blue-600">
                            IoT Intelligence
                        </span>
                    </h1>

                    <p class="text-lg text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                        Real-time sensor monitoring, AI-powered pest detection, and comprehensive analytics to optimize your agricultural operations.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="login.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-sm font-semibold transition-all hover:shadow-lg hover:-translate-y-0.5 flex items-center justify-center group">
                            <i class="fas fa-rocket mr-2 group-hover:animate-bounce"></i>
                            Get Started Free
                        </a>
                        <a href="#features" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 px-6 py-3 rounded-lg text-sm font-semibold transition-all hover:shadow-md flex items-center justify-center">
                            <i class="fas fa-play mr-2 text-xs"></i>
                            Watch Demo
                        </a>
                    </div>
                </div>

                <!-- Hero Visual with Creative Illustration -->
                <div class="relative animate-fade-in">
                    <!-- Floating Background Elements -->
                    <div class="absolute inset-0">
                        <div class="absolute top-10 left-10 w-20 h-20 bg-green-200 dark:bg-green-800 rounded-full opacity-20 animate-pulse"></div>
                        <div class="absolute bottom-10 right-10 w-16 h-16 bg-blue-200 dark:bg-blue-800 rounded-full opacity-20 animate-pulse" style="animation-delay: 1s;"></div>
                        <div class="absolute top-1/2 left-0 w-12 h-12 bg-yellow-200 dark:bg-yellow-800 rounded-full opacity-20 animate-pulse" style="animation-delay: 2s;"></div>
                    </div>

                    <div class="relative">
                        <!-- Main Dashboard Preview -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transform rotate-3 hover:rotate-0 transition-transform duration-500">
                            <div class="bg-gradient-to-r from-green-500 to-blue-500 px-4 py-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-2 h-2 bg-white/80 rounded-full"></div>
                                        <div class="w-2 h-2 bg-white/60 rounded-full"></div>
                                        <div class="w-2 h-2 bg-white/40 rounded-full"></div>
                                    </div>
                                    <div class="text-white text-xs font-medium">Farm Dashboard</div>
                                </div>
                            </div>
                            <div class="p-4">
                                <!-- Mini Stats with Icons -->
                                <div class="grid grid-cols-3 gap-3 mb-4">
                                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-3 rounded-lg relative overflow-hidden">
                                        <div class="absolute top-0 right-0 w-8 h-8 bg-green-200 dark:bg-green-700 rounded-full -mr-4 -mt-4 opacity-50"></div>
                                        <div class="text-lg font-bold text-green-600 dark:text-green-400">24.5°</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">Temp</div>
                                    </div>
                                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-3 rounded-lg relative overflow-hidden">
                                        <div class="absolute top-0 right-0 w-8 h-8 bg-blue-200 dark:bg-blue-700 rounded-full -mr-4 -mt-4 opacity-50"></div>
                                        <div class="text-lg font-bold text-blue-600 dark:text-blue-400">68%</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">Humidity</div>
                                    </div>
                                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-3 rounded-lg relative overflow-hidden">
                                        <div class="absolute top-0 right-0 w-8 h-8 bg-purple-200 dark:bg-purple-700 rounded-full -mr-4 -mt-4 opacity-50"></div>
                                        <div class="text-lg font-bold text-purple-600 dark:text-purple-400">9</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">Sensors</div>
                                    </div>
                                </div>

                                <!-- Mini Chart with Animation -->
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                    <div class="flex items-end justify-between h-12 mb-2 space-x-1">
                                        <div class="w-3 bg-gradient-to-t from-green-500 to-green-400 rounded-t animate-pulse" style="height: 60%; animation-delay: 0.1s;"></div>
                                        <div class="w-3 bg-gradient-to-t from-green-500 to-green-400 rounded-t animate-pulse" style="height: 80%; animation-delay: 0.2s;"></div>
                                        <div class="w-3 bg-gradient-to-t from-green-500 to-green-400 rounded-t animate-pulse" style="height: 70%; animation-delay: 0.3s;"></div>
                                        <div class="w-3 bg-gradient-to-t from-green-500 to-green-400 rounded-t animate-pulse" style="height: 90%; animation-delay: 0.4s;"></div>
                                        <div class="w-3 bg-gradient-to-t from-green-600 to-green-500 rounded-t animate-pulse" style="height: 100%; animation-delay: 0.5s;"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 text-center">Live Data</div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Sensor Icons -->
                        <div class="absolute -top-2 -right-2 w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center shadow-lg animate-bounce-subtle">
                            <i class="fas fa-thermometer-half text-white text-sm"></i>
                        </div>

                        <div class="absolute -bottom-2 -left-2 w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center shadow-lg animate-pulse-slow">
                            <i class="fas fa-tint text-white text-xs"></i>
                        </div>

                        <div class="absolute top-1/2 -right-4 w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center shadow-lg animate-bounce-subtle" style="animation-delay: 1s;">
                            <i class="fas fa-sun text-white text-xs"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 relative overflow-hidden">
        <!-- Background Decoration -->
        <div class="absolute top-0 left-0 w-full h-full opacity-5">
            <div class="absolute top-20 left-10 w-32 h-32 bg-green-300 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-10 w-40 h-40 bg-blue-300 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16">
                <div class="inline-flex items-center px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded-full text-sm font-medium mb-6">
                    <i class="fas fa-star mr-2"></i>
                    Powerful Features
                </div>
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Everything You Need for Smart Farming
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                    Comprehensive tools to monitor, analyze, and optimize your agricultural operations with cutting-edge technology.
                </p>
            </div>

            <!-- Main Feature Cards Grid -->
            <div class="space-y-8 mb-12">
                <!-- Real-time Monitoring - Large Feature Card -->
                <div class="group bg-white dark:bg-gray-800 rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 min-h-[400px]">
                        <!-- Content Side -->
                        <div class="p-8 lg:p-12 flex flex-col justify-center bg-gradient-to-br from-green-50 to-emerald-50 dark:from-gray-800 dark:to-gray-700">
                            <div class="mb-6">
                                <div class="inline-flex items-center px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full text-xs font-medium mb-4">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                                    Live Monitoring
                                </div>
                                <h3 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-4 leading-tight">
                                    Real-time Monitoring
                                </h3>
                                <p class="text-lg text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                                    Monitor temperature, humidity, soil moisture, and other critical parameters with our advanced sensor network. Get instant insights into your farm's conditions 24/7.
                                </p>
                            </div>

                            <!-- Feature List -->
                            <div class="space-y-4 mb-6">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-clock text-green-600 dark:text-green-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">24/7 Data Collection</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Continuous monitoring without interruption</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-microchip text-green-600 dark:text-green-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">Multiple Sensor Types</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Temperature, humidity, soil, light sensors</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-bell text-green-600 dark:text-green-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">Instant Alerts</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Real-time notifications for critical changes</div>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <div>
                                <a href="sensors.php" class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 group">
                                    View Live Data
                                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Image Side -->
                        <div class="relative bg-gradient-to-br from-green-100 to-emerald-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center p-8">
                            <!-- Placeholder for your image -->
                            <img src="includes/realtime.jpg" alt="Real-time Monitoring" class="w-full h-full object-cover rounded-2xl shadow-lg">

                            <!-- Floating Elements -->
                            <div class="absolute top-4 right-4 w-12 h-12 bg-green-500 rounded-full flex items-center justify-center shadow-lg animate-bounce-subtle">
                                <i class="fas fa-thermometer-half text-white"></i>
                            </div>
                            <div class="absolute bottom-4 left-4 w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center shadow-lg animate-pulse-slow">
                                <i class="fas fa-tint text-white text-sm"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Pest Detection - Large Feature Card -->
                <div class="group bg-white dark:bg-gray-800 rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 min-h-[400px]">
                        <!-- Image Side (Left on this card) -->
                        <div class="relative bg-gradient-to-br from-orange-100 to-red-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center p-8 order-2 lg:order-1">
                            <!-- Placeholder for your image -->
                            <img src="includes/pest.jpg" alt="Pest Detection" class="w-full h-full object-cover rounded-2xl shadow-lg">

                            <!-- Floating Elements -->
                            <div class="absolute top-4 left-4 w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center shadow-lg animate-bounce-subtle">
                                <i class="fas fa-bug text-white"></i>
                            </div>
                            <div class="absolute bottom-4 right-4 w-10 h-10 bg-red-500 rounded-full flex items-center justify-center shadow-lg animate-pulse-slow">
                                <i class="fas fa-eye text-white text-sm"></i>
                            </div>
                        </div>

                        <!-- Content Side -->
                        <div class="p-8 lg:p-12 flex flex-col justify-center bg-gradient-to-br from-orange-50 to-red-50 dark:from-gray-800 dark:to-gray-700 order-1 lg:order-2">
                            <div class="mb-6">
                                <div class="inline-flex items-center px-3 py-1.5 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-200 rounded-full text-xs font-medium mb-4">
                                    <div class="w-2 h-2 bg-orange-500 rounded-full mr-2 animate-pulse"></div>
                                    AI Powered
                                </div>
                                <h3 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-4 leading-tight">
                                    AI Pest Detection
                                </h3>
                                <p class="text-lg text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                                    Advanced computer vision and machine learning algorithms detect pests and diseases early, helping you take preventive action before damage occurs.
                                </p>
                            </div>

                            <!-- Feature List -->
                            <div class="space-y-4 mb-6">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-search text-orange-600 dark:text-orange-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">Early Detection</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Identify threats before they spread</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-brain text-orange-600 dark:text-orange-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">AI Image Analysis</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Machine learning powered recognition</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-prescription-bottle text-orange-600 dark:text-orange-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">Treatment Recommendations</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Actionable solutions for each threat</div>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <div>
                                <a href="pest_detection.php" class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 group">
                                    Try AI Detection
                                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Feature Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Smart Analytics -->
                <div class="group bg-gradient-to-br from-blue-50 to-purple-100 dark:from-gray-800 dark:to-gray-700 rounded-xl p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-blue-100 dark:border-gray-600 relative overflow-hidden">
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-chart-line text-white text-lg"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Smart Analytics</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 leading-relaxed">
                            Comprehensive data analysis and reporting tools to help you make informed decisions.
                        </p>
                        <div class="space-y-2">
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                <div class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-2"></div>
                                Trend Analysis
                            </div>
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                <div class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-2"></div>
                                Custom Reports
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Camera Monitoring -->
                <div class="group bg-gradient-to-br from-purple-50 to-pink-100 dark:from-gray-800 dark:to-gray-700 rounded-xl p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-purple-100 dark:border-gray-600 relative overflow-hidden">
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-camera text-white text-lg"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Camera Monitoring</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 leading-relaxed">
                            Live video feeds and automated image capture for visual monitoring of crops.
                        </p>
                        <div class="space-y-2">
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                <div class="w-1.5 h-1.5 bg-purple-500 rounded-full mr-2"></div>
                                Live Streaming
                            </div>
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                <div class="w-1.5 h-1.5 bg-purple-500 rounded-full mr-2"></div>
                                Motion Detection
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Access -->
                <div class="group bg-gradient-to-br from-indigo-50 to-blue-100 dark:from-gray-800 dark:to-gray-700 rounded-xl p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-indigo-100 dark:border-gray-600 relative overflow-hidden">
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-mobile-alt text-white text-lg"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Mobile Access</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 leading-relaxed">
                            Access your farm data anywhere, anytime with our responsive interface.
                        </p>
                        <div class="space-y-2">
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full mr-2"></div>
                                Responsive Design
                            </div>
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full mr-2"></div>
                                Push Notifications
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Export -->
                <div class="group bg-gradient-to-br from-teal-50 to-green-100 dark:from-gray-800 dark:to-gray-700 rounded-xl p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-teal-100 dark:border-gray-600 relative overflow-hidden">
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-green-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-download text-white text-lg"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Data Export</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 leading-relaxed">
                            Export your data in multiple formats for analysis and reporting.
                        </p>
                        <div class="space-y-2">
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                <div class="w-2 h-2 bg-teal-500 rounded-full mr-2"></div>
                                CSV & PDF Export
                            </div>
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                <div class="w-2 h-2 bg-teal-500 rounded-full mr-2"></div>
                                API Integration
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-gradient-to-r from-green-600 via-emerald-600 to-blue-600 relative overflow-hidden">
        <!-- Animated Background -->
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-32 h-32 bg-white/5 rounded-full animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-white/10 rounded-full animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-8">
                <h2 class="text-xl font-bold text-white mb-2">Trusted by Farmers Worldwide</h2>
                <p class="text-sm text-green-100">Real numbers from real farms</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 text-center">
                <div class="text-white group">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 hover:bg-white/20 transition-all">
                        <div class="text-2xl lg:text-3xl font-bold mb-1 group-hover:scale-110 transition-transform">500+</div>
                        <div class="text-xs text-green-100">Active Sensors</div>
                    </div>
                </div>
                <div class="text-white group">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 hover:bg-white/20 transition-all">
                        <div class="text-2xl lg:text-3xl font-bold mb-1 group-hover:scale-110 transition-transform">24/7</div>
                        <div class="text-xs text-green-100">Monitoring</div>
                    </div>
                </div>
                <div class="text-white group">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 hover:bg-white/20 transition-all">
                        <div class="text-2xl lg:text-3xl font-bold mb-1 group-hover:scale-110 transition-transform">99.9%</div>
                        <div class="text-xs text-green-100">Uptime</div>
                    </div>
                </div>
                <div class="text-white group">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 hover:bg-white/20 transition-all">
                        <div class="text-2xl lg:text-3xl font-bold mb-1 group-hover:scale-110 transition-transform">50+</div>
                        <div class="text-xs text-green-100">Farms Connected</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-16 bg-gray-50 dark:bg-gray-900 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div>
                    <div class="inline-flex items-center px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-xs font-medium mb-4">
                        <i class="fas fa-lightbulb mr-2 text-xs"></i>
                        Innovation in Agriculture
                    </div>

                    <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-4">
                        Revolutionizing Agriculture with Technology
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 leading-relaxed">
                        Our IoT Farm Monitoring System combines cutting-edge sensor technology, artificial intelligence, and intuitive design to help farmers make data-driven decisions.
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                        From small family farms to large agricultural enterprises, our platform scales to meet your needs while providing insights to increase productivity and sustainability.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="login.php" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-all hover:shadow-lg flex items-center justify-center">
                            <i class="fas fa-user-plus mr-2 text-xs"></i>
                            Start Your Journey
                        </a>
                        <a href="#contact" class="border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 px-5 py-2.5 rounded-lg text-sm font-semibold transition-all flex items-center justify-center">
                            <i class="fas fa-envelope mr-2 text-xs"></i>
                            Contact Us
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <!-- Creative Benefits Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 relative overflow-hidden">
                        <!-- Background Pattern -->
                        <div class="absolute top-0 right-0 w-32 h-32 opacity-5">
                            <svg viewBox="0 0 100 100" class="w-full h-full text-green-500">
                                <path d="M20,50 Q50,20 80,50 Q50,80 20,50" fill="currentColor" />
                                <circle cx="50" cy="50" r="15" fill="none" stroke="currentColor" stroke-width="2" />
                            </svg>
                        </div>

                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">System Benefits</h3>
                        <div class="space-y-3">
                            <div class="flex items-start group">
                                <div class="w-6 h-6 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3 mt-0.5 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-chart-line text-green-600 dark:text-green-400 text-xs"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Increase Yields</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-300">Optimize growing conditions for maximum crop production</p>
                                </div>
                            </div>
                            <div class="flex items-start group">
                                <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3 mt-0.5 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-tint text-blue-600 dark:text-blue-400 text-xs"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Save Water</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-300">Intelligent irrigation based on real soil moisture data</p>
                                </div>
                            </div>
                            <div class="flex items-start group">
                                <div class="w-6 h-6 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mr-3 mt-0.5 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-shield-alt text-yellow-600 dark:text-yellow-400 text-xs"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Prevent Losses</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-300">Early detection of pests and diseases</p>
                                </div>
                            </div>
                            <div class="flex items-start group">
                                <div class="w-6 h-6 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-3 mt-0.5 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-clock text-purple-600 dark:text-purple-400 text-xs"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Save Time</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-300">Automated monitoring reduces manual labor</p>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Indicators -->
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Farm Efficiency</span>
                                <span class="text-xs font-semibold text-green-600 dark:text-green-400">+85%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                <div class="bg-gradient-to-r from-green-500 to-green-600 h-1.5 rounded-full" style="width: 85%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Elements -->
                    <div class="absolute -top-3 -right-3 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shadow-lg animate-bounce-subtle">
                        <i class="fas fa-leaf text-white text-xs"></i>
                    </div>
                    <div class="absolute -bottom-3 -left-3 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center shadow-lg animate-pulse">
                        <i class="fas fa-droplet text-white text-xs"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-16 bg-white dark:bg-gray-800 relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute top-10 left-10 w-24 h-24 bg-green-300 rounded-full blur-2xl"></div>
            <div class="absolute bottom-10 right-10 w-32 h-32 bg-blue-300 rounded-full blur-2xl"></div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
            <div class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full text-xs font-medium mb-4">
                <i class="fas fa-rocket mr-2 text-xs"></i>
                Ready to Get Started?
            </div>

            <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-4">
                Transform Your Farm Today
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-8 max-w-2xl mx-auto">
                Join hundreds of farmers who are already using our IoT monitoring system to optimize their operations and increase yields.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center mb-8">
                <a href="login.php" class="group bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-sm font-semibold transition-all hover:shadow-lg hover:-translate-y-0.5 flex items-center justify-center">
                    <i class="fas fa-rocket mr-2 text-xs group-hover:animate-bounce"></i>
                    Get Started Free
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-green-600 to-green-500 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-seedling text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">FarmMonitor</h3>
                            <p class="text-xs text-gray-400">IoT Agriculture Solutions</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-300 mb-4 leading-relaxed">
                        Empowering farmers with intelligent monitoring solutions for sustainable and profitable agriculture.
                    </p>
                    <div class="flex space-x-3">
                        <a href="#" class="w-8 h-8 bg-gray-800 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 transition-all">
                            <i class="fab fa-twitter text-xs"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-gray-800 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 transition-all">
                            <i class="fab fa-facebook text-xs"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-gray-800 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 transition-all">
                            <i class="fab fa-linkedin text-xs"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-gray-800 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 transition-all">
                            <i class="fab fa-instagram text-xs"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-sm font-semibold mb-3">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-xs text-gray-300 hover:text-white transition-colors">Features</a></li>
                        <li><a href="#about" class="text-xs text-gray-300 hover:text-white transition-colors">About</a></li>
                        <li><a href="login.php" class="text-xs text-gray-300 hover:text-white transition-colors">Sign In</a></li>
                        <li><a href="#contact" class="text-xs text-gray-300 hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="text-sm font-semibold mb-3">Support</h4>
                    <ul class="space-y-2">
                        <li><a href="privacy_policy.php" class="text-xs text-gray-300 hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="terms_of_service.php" class="text-xs text-gray-300 hover:text-white transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-6 pt-6 text-center">
                <p class="text-xs text-gray-400">
                    &copy; <?php echo date('Y'); ?> FarmMonitor IoT Agriculture Solutions. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</div>

<!-- Smooth scrolling for anchor links -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                }
            });
        }, observerOptions);

        // Observe all feature cards and sections
        document.querySelectorAll('.bg-gray-50, .bg-white').forEach(el => {
            observer.observe(el);
        });
    });
</script>