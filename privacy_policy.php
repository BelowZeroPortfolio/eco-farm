<?php
// Set page title for header component
$pageTitle = 'Privacy Policy - FarmMonitor IoT Agriculture';

// Include shared header
include 'includes/header.php';
?>

<!-- Privacy Policy Page -->
<div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">

    <!-- Navigation Header -->
    <nav class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0 flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-green-500 rounded-xl flex items-center justify-center mr-3">
                            <i class="fas fa-seedling text-white text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">FarmMonitor</h1>
                            <p class="text-xs text-gray-500 dark:text-gray-400">IoT Agriculture</p>
                        </div>
                    </a>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-4">
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" onclick="toggleTheme()" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                        <i class="fas fa-moon"></i>
                    </button>

                    <!-- Back to Home -->
                    <a href="index.php" class="text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 px-3 py-2 text-sm font-medium transition-colors">
                        <i class="fas fa-home mr-2"></i>Home
                    </a>

                    <!-- Login Button -->
                    <a href="login.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all hover:shadow-lg hover:-translate-y-0.5">
                        Sign In
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="relative overflow-hidden py-12 bg-gradient-to-r from-green-600 via-emerald-600 to-blue-600">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 w-20 h-20 bg-white rounded-full animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-32 h-32 bg-white rounded-full animate-pulse" style="animation-delay: 1s;"></div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
            <div class="inline-flex items-center px-3 py-1.5 bg-white/20 backdrop-blur-sm text-white rounded-full text-xs font-medium mb-4">
                <i class="fas fa-shield-alt mr-2"></i>
                Legal Information
            </div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white mb-4">Privacy Policy</h1>
            <p class="text-lg text-green-100">Last updated: <?php echo date('F d, Y'); ?></p>
        </div>
    </section>

    <!-- Content Section -->
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-8 lg:p-12">
                    
                    <!-- Introduction -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-info-circle text-green-600 dark:text-green-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Introduction</h2>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                            Welcome to FarmMonitor. We respect your privacy and are committed to protecting your personal data. This privacy policy will inform you about how we look after your personal data when you visit our platform and tell you about your privacy rights and how the law protects you.
                        </p>
                    </div>

                    <!-- Information We Collect -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-database text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Information We Collect</h2>
                        </div>
                        <div class="space-y-4">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Personal Information</h3>
                                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                        <span>Name, email address, and contact information</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                        <span>Account credentials and authentication data</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                        <span>Farm location and property information</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Sensor Data</h3>
                                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                        <span>Temperature, humidity, and soil moisture readings</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                        <span>Camera images and pest detection data</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                        <span>Device identifiers and sensor metadata</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Usage Information</h3>
                                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                        <span>Log data, IP addresses, and browser information</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                        <span>Platform usage patterns and preferences</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- How We Use Your Information -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-cogs text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">How We Use Your Information</h2>
                        </div>
                        <div class="prose dark:prose-invert max-w-none">
                            <p class="text-gray-600 dark:text-gray-300 mb-4">We use the information we collect to:</p>
                            <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                                <li class="flex items-start">
                                    <i class="fas fa-arrow-right text-green-500 mr-2 mt-1 text-sm"></i>
                                    <span>Provide and maintain our IoT monitoring services</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-arrow-right text-green-500 mr-2 mt-1 text-sm"></i>
                                    <span>Process and analyze sensor data for insights and alerts</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-arrow-right text-green-500 mr-2 mt-1 text-sm"></i>
                                    <span>Improve our AI pest detection algorithms</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-arrow-right text-green-500 mr-2 mt-1 text-sm"></i>
                                    <span>Send notifications and important updates</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-arrow-right text-green-500 mr-2 mt-1 text-sm"></i>
                                    <span>Provide customer support and respond to inquiries</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-arrow-right text-green-500 mr-2 mt-1 text-sm"></i>
                                    <span>Enhance platform security and prevent fraud</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Data Security -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-lock text-orange-600 dark:text-orange-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Data Security</h2>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                            We implement appropriate technical and organizational security measures to protect your personal data against unauthorized access, alteration, disclosure, or destruction. These measures include:
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-gray-700 dark:to-gray-600 rounded-lg p-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-shield-alt text-green-600 dark:text-green-400 mr-2"></i>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Encryption</h4>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-300">SSL/TLS encryption for data transmission</p>
                            </div>
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600 rounded-lg p-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-user-lock text-blue-600 dark:text-blue-400 mr-2"></i>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Access Control</h4>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Restricted access to personal data</p>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-gray-700 dark:to-gray-600 rounded-lg p-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-server text-purple-600 dark:text-purple-400 mr-2"></i>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Secure Storage</h4>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Protected database infrastructure</p>
                            </div>
                            <div class="bg-gradient-to-br from-orange-50 to-red-50 dark:from-gray-700 dark:to-gray-600 rounded-lg p-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-history text-orange-600 dark:text-orange-400 mr-2"></i>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Regular Backups</h4>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Automated data backup systems</p>
                            </div>
                        </div>
                    </div>

                    <!-- Your Rights -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-user-shield text-indigo-600 dark:text-indigo-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Your Rights</h2>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                            You have the following rights regarding your personal data:
                        </p>
                        <div class="space-y-3">
                            <div class="flex items-start bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <i class="fas fa-eye text-green-500 mr-3 mt-1"></i>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Right to Access</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Request copies of your personal data</p>
                                </div>
                            </div>
                            <div class="flex items-start bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <i class="fas fa-edit text-blue-500 mr-3 mt-1"></i>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Right to Rectification</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Request correction of inaccurate data</p>
                                </div>
                            </div>
                            <div class="flex items-start bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <i class="fas fa-trash text-red-500 mr-3 mt-1"></i>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Right to Erasure</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Request deletion of your personal data</p>
                                </div>
                            </div>
                            <div class="flex items-start bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <i class="fas fa-download text-purple-500 mr-3 mt-1"></i>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Right to Data Portability</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Request transfer of your data to another service</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="bg-gradient-to-br from-green-50 to-blue-50 dark:from-gray-700 dark:to-gray-600 rounded-xl p-6 border border-green-200 dark:border-gray-600">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-envelope text-white"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Contact Us</h2>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            If you have any questions about this Privacy Policy or wish to exercise your rights, please contact us at:
                        </p>
                        <div class="space-y-2 text-gray-700 dark:text-gray-200">
                            <p><i class="fas fa-envelope text-green-600 dark:text-green-400 mr-2"></i> Email: <a href="mailto:privacy@farmmonitor.com" class="text-green-600 dark:text-green-400 hover:underline">privacy@farmmonitor.com</a></p>
                            <p><i class="fas fa-globe text-green-600 dark:text-green-400 mr-2"></i> Website: <a href="index.php" class="text-green-600 dark:text-green-400 hover:underline">www.farmmonitor.com</a></p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Back to Home Button -->
            <div class="text-center mt-8">
                <a href="index.php" class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Home
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
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-sm font-semibold mb-3">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="index.php#features" class="text-xs text-gray-300 hover:text-white transition-colors">Features</a></li>
                        <li><a href="index.php#about" class="text-xs text-gray-300 hover:text-white transition-colors">About</a></li>
                        <li><a href="login.php" class="text-xs text-gray-300 hover:text-white transition-colors">Sign In</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="text-sm font-semibold mb-3">Support</h4>
                    <ul class="space-y-2">
                        <li><a href="privacy_policy.php" class="text-xs text-green-400 hover:text-white transition-colors">Privacy Policy</a></li>
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

<?php include 'includes/footer.php'; ?>
