<?php

/**
 * System Settings Page
 * 
 * Allows administrators to configure system settings,
 * user preferences, and system-wide configurations
 */

// Start session
session_start();

// Simple authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/language.php';

// Get current user data
$currentUserId = $_SESSION['user_id'];
$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'admin'
];

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Handle different settings actions here
        switch ($_POST['action']) {
            case 'update_system_settings':
                // Handle system settings update
                $success = 'System settings updated successfully!';
                break;
            case 'update_preferences':
                // Handle preferences update
                $language = $_POST['language'] ?? 'en';
                $timezone = $_POST['timezone'] ?? 'UTC+8';

                // Store preferences in session (in a real app, you'd save to database)
                $_SESSION['user_language'] = $language;
                $_SESSION['user_timezone'] = $timezone;

                $success = 'Preferences updated successfully!';
                break;
            default:
                $error = 'Invalid action specified.';
        }
    }
}

// Get current user preferences
$currentLanguage = $_SESSION['user_language'] ?? 'en';
$currentTimezone = $_SESSION['user_timezone'] ?? 'UTC+8';

// Set page title and additional resources
$pageTitle = 'System Settings - IoT Farm Monitoring System';
$additionalCSS = [];
$additionalJS = [];

// Include header and navigation
include 'includes/header.php';
include 'includes/navigation.php';

// Add language support JavaScript
$currentLanguage = getCurrentLanguage();
$translations = getTranslations();
$jsTranslations = $translations[$currentLanguage] ?? $translations['en'];
?>

<script>
    // Initialize language system for this page
    const pageLanguage = '<?php echo $currentLanguage; ?>';
    const pageTranslations = <?php echo json_encode($jsTranslations); ?>;
</script>
<script src="includes/language.js"></script>

<?php
?>

<div class="p-4 max-w-7xl mx-auto">

    <!-- Success Message -->
    <?php if ($success): ?>
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 mb-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-3"></i>
                <span class="text-green-700 dark:text-green-300 font-medium"><?php echo htmlspecialchars($success); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if ($error): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-500 dark:text-red-400 mr-3"></i>
                <span class="text-red-700 dark:text-red-300 font-medium"><?php echo htmlspecialchars($error); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl mb-4">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-8 px-4" aria-label="Tabs">
                <button onclick="showTab('notifications')" id="notifications-tab" class="tab-button active border-b-2 border-green-500 py-3 px-1 text-sm font-medium text-green-600 dark:text-green-400">
                    <i class="fas fa-bell mr-2"></i>
                    <span data-translate="notifications">Notifications</span>
                </button>
                <button onclick="showTab('preferences')" id="preferences-tab" class="tab-button border-b-2 border-transparent py-3 px-1 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
                    <i class="fas fa-cog mr-2"></i>
                    <span data-translate="preferences">Preferences</span>
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="space-y-4">

        <!-- Notifications Tab -->
        <div id="notifications-content" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-bell text-yellow-600 mr-2"></i>
                        <span data-translate="notification_settings">Notification Settings</span>
                    </h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Configure system-wide notification preferences</p>
                </div>
                <div class="p-4">
                    <div class="space-y-6">
                        <!-- Email Notifications -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Email Notifications</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <span class="text-sm text-gray-900 dark:text-white" data-translate="system_alerts">System Alerts</span>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Send email for critical system events</p>
                                    </div>
                                    <button type="button" class="relative inline-flex h-6 w-11 items-center rounded-full bg-green-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-6"></span>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <span class="text-sm text-gray-900 dark:text-white" data-translate="daily_reports">Daily Reports</span>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Send daily summary reports</p>
                                    </div>
                                    <button type="button" class="relative inline-flex h-6 w-11 items-center rounded-full bg-green-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-6"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Alert Thresholds -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Alert Thresholds</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Temperature (°C)</label>
                                    <div class="flex space-x-2">
                                        <input type="number" placeholder="Min" value="15" class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                                        <input type="number" placeholder="Max" value="35" class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Humidity (%)</label>
                                    <div class="flex space-x-2">
                                        <input type="number" placeholder="Min" value="40" class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                                        <input type="number" placeholder="Max" value="80" class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Soil Moisture (%)</label>
                                    <div class="flex space-x-2">
                                        <input type="number" placeholder="Min" value="30" class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                                        <input type="number" placeholder="Max" value="70" class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preferences Tab -->
        <div id="preferences-content" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-cog text-purple-600 mr-2"></i>
                        <span data-translate="user_preferences">User Preferences</span>
                    </h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Customize your experience and notification settings</p>
                </div>
                <div class="p-4 space-y-6">
                    <!-- Theme Preferences -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Theme & Display</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="text-sm text-gray-900 dark:text-white" data-translate="dark_mode">Dark Mode</span>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Switch between light and dark themes</p>
                                </div>
                                <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-200 dark:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-1"></span>
                                </button>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="text-sm text-gray-900 dark:text-white" data-translate="compact_view">Compact View</span>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Use smaller spacing and fonts</p>
                                </div>
                                <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-200 dark:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-1"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Preferences -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Notifications</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="text-sm text-gray-900 dark:text-white" data-translate="email_notifications">Email Notifications</span>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Receive updates via email</p>
                                </div>
                                <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-green-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-6"></span>
                                </button>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="text-sm text-gray-900 dark:text-white" data-translate="pest_alerts">Pest Alerts</span>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Get notified about pest detection</p>
                                </div>
                                <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-green-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-6"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Language & Region -->
                    <form method="POST" action="" id="preferences-form">
                        <input type="hidden" name="action" value="update_preferences">

                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Language & Region</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" data-translate="language">Language</label>
                                    <select name="language" id="language-select" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                        <option value="en" <?php echo $currentLanguage === 'en' ? 'selected' : ''; ?> data-translate="english">English</option>
                                        <option value="tl" <?php echo $currentLanguage === 'tl' ? 'selected' : ''; ?> data-translate="tagalog">Tagalog</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" data-translate="timezone">Timezone</label>
                                    <select name="timezone" id="timezone-select" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                        <option value="UTC+8" <?php echo $currentTimezone === 'UTC+8' ? 'selected' : ''; ?>>UTC+8 (Philippine Time)</option>
                                        <option value="UTC-5" <?php echo $currentTimezone === 'UTC-5' ? 'selected' : ''; ?>>UTC-5 (Eastern Time)</option>
                                        <option value="UTC-6" <?php echo $currentTimezone === 'UTC-6' ? 'selected' : ''; ?>>UTC-6 (Central Time)</option>
                                        <option value="UTC-7" <?php echo $currentTimezone === 'UTC-7' ? 'selected' : ''; ?>>UTC-7 (Mountain Time)</option>
                                        <option value="UTC-8" <?php echo $currentTimezone === 'UTC-8' ? 'selected' : ''; ?>>UTC-8 (Pacific Time)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="flex justify-end pt-3 border-t border-gray-200 dark:border-gray-600">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200">
                                <i class="fas fa-save mr-2"></i>
                                <span data-translate="save_preferences">Save Preferences</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Tab Switching JavaScript -->
<script>
    function showTab(tabName) {
        // Hide all tab contents
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(content => {
            content.classList.add('hidden');
        });

        // Remove active class from all tab buttons
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.classList.remove('active', 'border-green-500', 'text-green-600', 'dark:text-green-400');
            button.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400', 'hover:text-gray-700', 'dark:hover:text-gray-300', 'hover:border-gray-300');
        });

        // Show selected tab content
        const selectedContent = document.getElementById(tabName + '-content');
        if (selectedContent) {
            selectedContent.classList.remove('hidden');
        }

        // Add active class to selected tab button
        const selectedButton = document.getElementById(tabName + '-tab');
        if (selectedButton) {
            selectedButton.classList.add('active', 'border-green-500', 'text-green-600', 'dark:text-green-400');
            selectedButton.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400', 'hover:text-gray-700', 'dark:hover:text-gray-300', 'hover:border-gray-300');
        }
    }

    // Initialize with notifications tab active
    document.addEventListener('DOMContentLoaded', function() {
        showTab('notifications');
    });

    // Toggle switch functionality
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('button[class*="inline-flex h-6 w-11"]');

        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const span = this.querySelector('span');
                const isActive = span.classList.contains('translate-x-6');

                if (isActive) {
                    // Turn off
                    span.classList.remove('translate-x-6');
                    span.classList.add('translate-x-1');
                    this.classList.remove('bg-green-600');
                    this.classList.add('bg-gray-200', 'dark:bg-gray-600');
                } else {
                    // Turn on
                    span.classList.remove('translate-x-1');
                    span.classList.add('translate-x-6');
                    this.classList.remove('bg-gray-200', 'dark:bg-gray-600');
                    this.classList.add('bg-green-600');
                }
            });
        });
    });

    // Language change handler (using global language system)
    document.addEventListener('DOMContentLoaded', function() {
        const languageSelect = document.getElementById('language-select');
        if (languageSelect) {
            languageSelect.addEventListener('change', function() {
                // Use the global language system
                if (window.LanguageSystem) {
                    window.LanguageSystem.changeLanguage(this.value);
                }
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>