<?php
// Set page title for header component
$pageTitle = 'Terms of Service - FarmMonitor IoT Agriculture';

// Include shared header
include 'includes/header.php';
?>

<!-- Terms of Service Page -->
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
                <i class="fas fa-file-contract mr-2"></i>
                Legal Agreement
            </div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white mb-4">Terms of Service</h1>
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
                                <i class="fas fa-handshake text-green-600 dark:text-green-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Agreement to Terms</h2>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                            Welcome to FarmMonitor. By accessing or using our IoT Farm Monitoring System, you agree to be bound by these Terms of Service. Please read them carefully before using our platform. If you do not agree to these terms, you may not access or use our services.
                        </p>
                    </div>

                    <!-- Account Registration -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-user-plus text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Account Registration</h2>
                        </div>
                        <div class="space-y-4">
                            <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                                To use FarmMonitor, you must create an account. When you register, you agree to:
                            </p>
                            <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span>Provide accurate, current, and complete information</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span>Maintain and update your information to keep it accurate</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span>Keep your password secure and confidential</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span>Accept responsibility for all activities under your account</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span>Notify us immediately of any unauthorized access</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Service Usage -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-laptop-code text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Acceptable Use</h2>
                        </div>
                        <div class="space-y-4">
                            <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                                You agree to use FarmMonitor only for lawful purposes. You must not:
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                                    <div class="flex items-start">
                                        <i class="fas fa-times-circle text-red-500 mr-2 mt-1"></i>
                                        <div>
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Prohibited Actions</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">Violate any laws or regulations</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                                    <div class="flex items-start">
                                        <i class="fas fa-times-circle text-red-500 mr-2 mt-1"></i>
                                        <div>
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">No Interference</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">Disrupt or interfere with services</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                                    <div class="flex items-start">
                                        <i class="fas fa-times-circle text-red-500 mr-2 mt-1"></i>
                                        <div>
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">No Unauthorized Access</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">Access systems without permission</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                                    <div class="flex items-start">
                                        <i class="fas fa-times-circle text-red-500 mr-2 mt-1"></i>
                                        <div>
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">No Malicious Code</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">Transmit viruses or harmful code</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Intellectual Property -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-copyright text-orange-600 dark:text-orange-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Intellectual Property</h2>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                            All content, features, and functionality of FarmMonitor, including but not limited to text, graphics, logos, software, and AI algorithms, are owned by us and protected by copyright, trademark, and other intellectual property laws.
                        </p>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Your Data Rights</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                You retain all rights to the data collected from your sensors and farm operations. We do not claim ownership of your agricultural data.
                            </p>
                        </div>
                    </div>

                    <!-- Service Availability -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-server text-indigo-600 dark:text-indigo-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Service Availability</h2>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                            We strive to provide reliable service, but we cannot guarantee uninterrupted access. We reserve the right to:
                        </p>
                        <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-green-500 mr-2 mt-1 text-sm"></i>
                                <span>Modify or discontinue services with or without notice</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-green-500 mr-2 mt-1 text-sm"></i>
                                <span>Perform scheduled maintenance and updates</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-green-500 mr-2 mt-1 text-sm"></i>
                                <span>Suspend access for violations of these terms</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Limitation of Liability -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Limitation of Liability</h2>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">
                                FarmMonitor is provided "as is" without warranties of any kind. To the maximum extent permitted by law, we shall not be liable for:
                            </p>
                            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                                <li class="flex items-start">
                                    <i class="fas fa-minus text-yellow-600 mr-2 mt-1 text-xs"></i>
                                    <span>Indirect, incidental, or consequential damages</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-minus text-yellow-600 mr-2 mt-1 text-xs"></i>
                                    <span>Loss of profits, data, or business opportunities</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-minus text-yellow-600 mr-2 mt-1 text-xs"></i>
                                    <span>Crop losses or agricultural damages</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-minus text-yellow-600 mr-2 mt-1 text-xs"></i>
                                    <span>Service interruptions or technical failures</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Termination -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-ban text-red-600 dark:text-red-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Termination</h2>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                            We may terminate or suspend your account and access to the service immediately, without prior notice, for any reason, including breach of these Terms. You may also terminate your account at any time through your account settings.
                        </p>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Upon Termination</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Your right to use the service will cease immediately. You may request a copy of your data within 30 days of termination.
                            </p>
                        </div>
                    </div>

                    <!-- Changes to Terms -->
                    <div class="mb-10">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900/30 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-sync-alt text-teal-600 dark:text-teal-400"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Changes to Terms</h2>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                            We reserve the right to modify these Terms at any time. We will notify you of any changes by posting the new Terms on this page and updating the "Last updated" date. Your continued use of the service after changes constitutes acceptance of the modified Terms.
                        </p>
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
                            If you have any questions about these Terms of Service, please contact us at:
                        </p>
                        <div class="space-y-2 text-gray-700 dark:text-gray-200">
                            <p><i class="fas fa-envelope text-green-600 dark:text-green-400 mr-2"></i> Email: <a href="mailto:support@farmmonitor.com" class="text-green-600 dark:text-green-400 hover:underline">support@farmmonitor.com</a></p>
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
                        <li><a href="privacy_policy.php" class="text-xs text-gray-300 hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="terms_of_service.php" class="text-xs text-green-400 hover:text-white transition-colors">Terms of Service</a></li>
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
