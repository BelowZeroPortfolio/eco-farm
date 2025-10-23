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
            case 'update_sensor_interval':
                // Handle sensor logging interval update
                try {
                    $intervalMinutes = isset($_POST['sensor_interval']) ? floatval($_POST['sensor_interval']) : 0;
                    
                    // Validate interval value
                    if (!is_numeric($intervalMinutes)) {
                        throw new Exception('Interval must be a numeric value');
                    }

                    // Allow 5 seconds for testing, or standard intervals
                    $allowedIntervals = [0.0833, 5, 15, 30, 60, 120, 240]; // 0.0833 = 5 seconds in minutes
                    
                    if (!in_array($intervalMinutes, $allowedIntervals)) {
                        throw new Exception('Invalid interval selected. Please choose from available options.');
                    }

                    require_once 'includes/arduino-api.php';
                    $arduino = new ArduinoBridge();
                    $result = $arduino->setLoggingInterval($intervalMinutes, $currentUserId);

                    if (!$result['success']) {
                        throw new Exception($result['message']);
                    }
                    
                    $success = $result['message'];
                    
                } catch (Exception $e) {
                    $error = 'Failed to update sensor interval: ' . $e->getMessage();
                    error_log("Sensor interval update error: " . $e->getMessage());
                }
                break;

            case 'update_notification_settings':
                // Handle notification settings update
                $dailyReportEnabled = isset($_POST['daily_report_enabled']) ? 'true' : 'false';
                $dailyReportTime = $_POST['daily_report_time'] ?? '08:00';
                $emailRecipients = $_POST['email_recipients'] ?? '';

                // Update .env file
                $envFile = __DIR__ . '/.env';
                $envContent = file_exists($envFile) ? file_get_contents($envFile) : '';

                // Update or add settings
                $settings = [
                    'DAILY_REPORT_ENABLED' => $dailyReportEnabled,
                    'DAILY_REPORT_TIME' => $dailyReportTime,
                    'DAILY_REPORT_RECIPIENTS' => $emailRecipients
                ];

                foreach ($settings as $key => $value) {
                    if (preg_match("/^$key=.*/m", $envContent)) {
                        $envContent = preg_replace("/^$key=.*/m", "$key=$value", $envContent);
                    } else {
                        $envContent .= "\n$key=$value";
                    }
                }

                file_put_contents($envFile, $envContent);

                $success = 'Notification settings updated successfully!';
                break;
            case 'update_thresholds':
                // Handle sensor threshold updates
                try {
                    $tempMin = isset($_POST['temp_min']) ? floatval($_POST['temp_min']) : null;
                    $tempMax = isset($_POST['temp_max']) ? floatval($_POST['temp_max']) : null;
                    $humMin = isset($_POST['hum_min']) ? floatval($_POST['hum_min']) : null;
                    $humMax = isset($_POST['hum_max']) ? floatval($_POST['hum_max']) : null;
                    $soilMin = isset($_POST['soil_min']) ? floatval($_POST['soil_min']) : null;
                    $soilMax = isset($_POST['soil_max']) ? floatval($_POST['soil_max']) : null;
                    
                    // Validate thresholds
                    if ($tempMin >= $tempMax) {
                        throw new Exception('Temperature minimum must be less than maximum');
                    }
                    if ($humMin >= $humMax) {
                        throw new Exception('Humidity minimum must be less than maximum');
                    }
                    if ($soilMin >= $soilMax) {
                        throw new Exception('Soil moisture minimum must be less than maximum');
                    }
                    
                    $pdo = getDatabaseConnection();
                    
                    // Update temperature sensors
                    $stmt = $pdo->prepare("
                        UPDATE sensors 
                        SET alert_threshold_min = ?, alert_threshold_max = ? 
                        WHERE sensor_type = 'temperature'
                    ");
                    $stmt->execute([$tempMin, $tempMax]);
                    
                    // Update humidity sensors
                    $stmt = $pdo->prepare("
                        UPDATE sensors 
                        SET alert_threshold_min = ?, alert_threshold_max = ? 
                        WHERE sensor_type = 'humidity'
                    ");
                    $stmt->execute([$humMin, $humMax]);
                    
                    // Update soil moisture sensors
                    $stmt = $pdo->prepare("
                        UPDATE sensors 
                        SET alert_threshold_min = ?, alert_threshold_max = ? 
                        WHERE sensor_type = 'soil_moisture'
                    ");
                    $stmt->execute([$soilMin, $soilMax]);
                    
                    $success = 'Sensor thresholds updated successfully! Remarks will now use the new ranges.';
                    
                } catch (Exception $e) {
                    $error = 'Failed to update thresholds: ' . $e->getMessage();
                    error_log("Threshold update error: " . $e->getMessage());
                }
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

// Load notification settings from .env file
$envFile = __DIR__ . '/.env';
$isEnabled = false;
$reportTime = '08:00';
$recipients = '';

try {
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        
        // Parse DAILY_REPORT_ENABLED
        if (preg_match('/^DAILY_REPORT_ENABLED=(.*)$/m', $envContent, $matches)) {
            $isEnabled = trim($matches[1]) === 'true';
        }
        
        // Parse DAILY_REPORT_TIME
        if (preg_match('/^DAILY_REPORT_TIME=(.*)$/m', $envContent, $matches)) {
            $reportTime = trim($matches[1]);
        }
        
        // Parse DAILY_REPORT_RECIPIENTS
        if (preg_match('/^DAILY_REPORT_RECIPIENTS=(.*)$/m', $envContent, $matches)) {
            $recipients = trim($matches[1]);
        }
    }
} catch (Exception $e) {
    error_log("Error loading notification settings: " . $e->getMessage());
}

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
                <button onclick="showTab('sensor')" id="sensor-tab" class="tab-button active border-b-2 border-green-500 py-3 px-1 text-sm font-medium text-green-600 dark:text-green-400">
                    <i class="fas fa-thermometer-half mr-2"></i>
                    <span data-translate="sensor_settings">Sensor Settings</span>
                </button>
                <button onclick="showTab('notifications')" id="notifications-tab" class="tab-button border-b-2 border-transparent py-3 px-1 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
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

        <!-- Sensor Settings Tab -->
        <div id="sensor-content" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-thermometer-half text-blue-600 mr-2"></i>
                        <span data-translate="sensor_settings">Sensor Logging Settings</span>
                    </h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Configure how often sensor data is logged to the database</p>
                </div>
                <div class="p-4">
                    <?php
                    // Get current interval setting
                    require_once 'includes/arduino-api.php';
                    $arduino = new ArduinoBridge();
                    $intervalSetting = $arduino->getLoggingIntervalSetting();
                    $currentInterval = $intervalSetting['interval_minutes'] ?? 30;
                    ?>

                    <form method="POST" action="" id="sensor-interval-form">
                        <input type="hidden" name="action" value="update_sensor_interval">

                        <div class="space-y-6">
                            <!-- Current Setting Display -->
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-1">Current Logging Interval</h4>
                                        <p class="text-xs text-blue-700 dark:text-blue-300">Sensor data is logged every <strong id="current-interval-display"><?php echo $intervalSetting['formatted'] ?? '30 minutes'; ?></strong></p>
                                    </div>
                                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Interval Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    <i class="fas fa-stopwatch mr-1"></i>
                                    Select Logging Interval
                                </label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <!-- 5 seconds (Testing Only) -->
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="sensor_interval" value="0.0833" <?php echo abs($currentInterval - 0.0833) < 0.01 ? 'checked' : ''; ?> 
                                               class="peer sr-only" onchange="updateIntervalPreview(0.0833, '5 seconds')">
                                        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg peer-checked:border-orange-500 peer-checked:bg-orange-50 dark:peer-checked:bg-orange-900/20 hover:border-gray-300 dark:hover:border-gray-600 transition-all">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-semibold text-gray-900 dark:text-white">5 Seconds</div>
                                                    <div class="text-xs text-orange-600 dark:text-orange-400">⚠️ Testing only - High DB load</div>
                                                </div>
                                                <i class="fas fa-check-circle text-orange-500 text-xl hidden peer-checked:block"></i>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- 5 minutes -->
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="sensor_interval" value="5" <?php echo $currentInterval == 5 ? 'checked' : ''; ?> 
                                               class="peer sr-only" onchange="updateIntervalPreview(5, '5 minutes')">
                                        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 hover:border-gray-300 dark:hover:border-gray-600 transition-all">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-semibold text-gray-900 dark:text-white">5 Minutes</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">High frequency</div>
                                                </div>
                                                <i class="fas fa-check-circle text-blue-500 text-xl hidden peer-checked:block"></i>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- 15 minutes -->
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="sensor_interval" value="15" <?php echo $currentInterval == 15 ? 'checked' : ''; ?> 
                                               class="peer sr-only" onchange="updateIntervalPreview(15, '15 minutes')">
                                        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 hover:border-gray-300 dark:hover:border-gray-600 transition-all">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-semibold text-gray-900 dark:text-white">15 Minutes</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Frequent monitoring</div>
                                                </div>
                                                <i class="fas fa-check-circle text-blue-500 text-xl hidden peer-checked:block"></i>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- 30 minutes -->
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="sensor_interval" value="30" <?php echo $currentInterval == 30 ? 'checked' : ''; ?> 
                                               class="peer sr-only" onchange="updateIntervalPreview(30, '30 minutes')">
                                        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 hover:border-gray-300 dark:hover:border-gray-600 transition-all">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-semibold text-gray-900 dark:text-white">30 Minutes</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Recommended (default)</div>
                                                </div>
                                                <i class="fas fa-check-circle text-blue-500 text-xl hidden peer-checked:block"></i>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- 1 hour -->
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="sensor_interval" value="60" <?php echo $currentInterval == 60 ? 'checked' : ''; ?> 
                                               class="peer sr-only" onchange="updateIntervalPreview(60, '1 hour')">
                                        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 hover:border-gray-300 dark:hover:border-gray-600 transition-all">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-semibold text-gray-900 dark:text-white">1 Hour</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Standard monitoring</div>
                                                </div>
                                                <i class="fas fa-check-circle text-blue-500 text-xl hidden peer-checked:block"></i>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- 2 hours -->
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="sensor_interval" value="120" <?php echo $currentInterval == 120 ? 'checked' : ''; ?> 
                                               class="peer sr-only" onchange="updateIntervalPreview(120, '2 hours')">
                                        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 hover:border-gray-300 dark:hover:border-gray-600 transition-all">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-semibold text-gray-900 dark:text-white">2 Hours</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Reduced frequency</div>
                                                </div>
                                                <i class="fas fa-check-circle text-blue-500 text-xl hidden peer-checked:block"></i>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- 4 hours -->
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="sensor_interval" value="240" <?php echo $currentInterval == 240 ? 'checked' : ''; ?> 
                                               class="peer sr-only" onchange="updateIntervalPreview(240, '4 hours')">
                                        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 hover:border-gray-300 dark:hover:border-gray-600 transition-all">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-semibold text-gray-900 dark:text-white">4 Hours</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Low frequency</div>
                                                </div>
                                                <i class="fas fa-check-circle text-blue-500 text-xl hidden peer-checked:block"></i>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Info Box -->
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <h4 class="font-semibold text-yellow-900 dark:text-yellow-200 mb-2 flex items-center text-sm">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Important Notes
                                </h4>
                                <ul class="text-xs text-yellow-800 dark:text-yellow-300 space-y-1 list-disc list-inside">
                                    <li>Arduino continues reading sensors every 3 seconds regardless of this setting</li>
                                    <li>This setting controls how often readings are saved to the database</li>
                                    <li>Lower intervals = more data storage, higher intervals = less storage</li>
                                    <li>Dashboard always shows real-time data from Arduino</li>
                                    <li>Historical reports use database-logged data</li>
                                </ul>
                            </div>

                            <!-- Save Button -->
                            <div class="flex justify-end pt-3 border-t border-gray-200 dark:border-gray-600">
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                                    <i class="fas fa-save mr-2"></i>
                                    <span data-translate="save_settings">Save Interval Setting</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

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
                                <form method="POST" action="" id="notification-settings-form">
                                    <input type="hidden" name="action" value="update_notification_settings">

                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg mb-3">
                                        <div>
                                            <span class="text-sm text-gray-900 dark:text-white" data-translate="daily_reports">Daily Reports</span>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">Send daily summary reports via email</p>
                                        </div>
                                        <button type="button" id="daily-report-toggle" class="relative inline-flex h-6 w-11 items-center rounded-full <?php echo $isEnabled ? 'bg-green-600' : 'bg-gray-200 dark:bg-gray-600'; ?> transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform <?php echo $isEnabled ? 'translate-x-6' : 'translate-x-1'; ?>"></span>
                                        </button>
                                        <input type="hidden" name="daily_report_enabled" id="daily-report-enabled-input" value="<?php echo $isEnabled ? '1' : '0'; ?>">
                                    </div>
                                    <div id="daily-report-settings" class="<?php echo $isEnabled ? '' : 'hidden'; ?> space-y-3 pl-3 border-l-2 border-green-500">
                                        <!-- Report Time -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                <i class="fas fa-clock mr-1"></i>
                                                Report Time (UTC+8)
                                            </label>
                                            <input type="time" name="daily_report_time" value="<?php echo htmlspecialchars($reportTime); ?>" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Reports will be sent daily at this time (Philippine Time)</p>
                                        </div>

                                        <!-- Email Recipients -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                <i class="fas fa-envelope mr-1"></i>
                                                Email Recipients
                                            </label>
                                            <textarea name="email_recipients" rows="2" placeholder="admin@example.com, farmer@example.com" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"><?php echo htmlspecialchars($recipients); ?></textarea>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Separate multiple emails with commas</p>
                                        </div>

                                        <!-- Info Note -->
                                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                            <p class="text-xs text-blue-700 dark:text-blue-300">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                <strong>Note:</strong> Configure EmailJS credentials (Service ID, Template ID, Public Key) in the <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded">.env</code> file before enabling daily reports.
                                            </p>
                                        </div>

                                        <!-- Save Button -->
                                        <div class="flex justify-end pt-2">
                                            <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                                                <i class="fas fa-save mr-2"></i>
                                                Save Settings
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Alert Thresholds -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Sensor Alert Thresholds</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">Set optimal ranges for each sensor type. Remarks will be generated based on these thresholds.</p>
                            
                            <?php
                            // Get current threshold settings from database
                            try {
                                $pdo = getDatabaseConnection();
                                $stmt = $pdo->query("
                                    SELECT sensor_type, alert_threshold_min, alert_threshold_max 
                                    FROM sensors 
                                    WHERE sensor_name LIKE 'Arduino%'
                                    GROUP BY sensor_type
                                ");
                                $thresholds = [];
                                while ($row = $stmt->fetch()) {
                                    $thresholds[$row['sensor_type']] = [
                                        'min' => $row['alert_threshold_min'] ?? 0,
                                        'max' => $row['alert_threshold_max'] ?? 0
                                    ];
                                }
                                
                                // Set defaults if not found
                                if (!isset($thresholds['temperature'])) {
                                    $thresholds['temperature'] = ['min' => 20, 'max' => 28];
                                }
                                if (!isset($thresholds['humidity'])) {
                                    $thresholds['humidity'] = ['min' => 60, 'max' => 80];
                                }
                                if (!isset($thresholds['soil_moisture'])) {
                                    $thresholds['soil_moisture'] = ['min' => 40, 'max' => 60];
                                }
                            } catch (Exception $e) {
                                error_log("Error loading thresholds: " . $e->getMessage());
                                $thresholds = [
                                    'temperature' => ['min' => 20, 'max' => 28],
                                    'humidity' => ['min' => 60, 'max' => 80],
                                    'soil_moisture' => ['min' => 40, 'max' => 60]
                                ];
                            }
                            ?>
                            
                            <form method="POST" action="" id="threshold-settings-form">
                                <input type="hidden" name="action" value="update_thresholds">
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- Temperature -->
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-thermometer-half text-red-600 dark:text-red-400"></i>
                                            <label class="block text-sm font-medium text-gray-900 dark:text-white">Temperature (°C)</label>
                                        </div>
                                        <div class="space-y-2">
                                            <div>
                                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Minimum (Optimal)</label>
                                                <input type="number" name="temp_min" step="0.1" value="<?php echo $thresholds['temperature']['min']; ?>" 
                                                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-red-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Maximum (Optimal)</label>
                                                <input type="number" name="temp_max" step="0.1" value="<?php echo $thresholds['temperature']['max']; ?>" 
                                                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-red-500">
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Current: <?php echo $thresholds['temperature']['min']; ?>-<?php echo $thresholds['temperature']['max']; ?>°C
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Humidity -->
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-tint text-blue-600 dark:text-blue-400"></i>
                                            <label class="block text-sm font-medium text-gray-900 dark:text-white">Humidity (%)</label>
                                        </div>
                                        <div class="space-y-2">
                                            <div>
                                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Minimum (Optimal)</label>
                                                <input type="number" name="hum_min" step="0.1" value="<?php echo $thresholds['humidity']['min']; ?>" 
                                                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Maximum (Optimal)</label>
                                                <input type="number" name="hum_max" step="0.1" value="<?php echo $thresholds['humidity']['max']; ?>" 
                                                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Current: <?php echo $thresholds['humidity']['min']; ?>-<?php echo $thresholds['humidity']['max']; ?>%
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Soil Moisture -->
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-seedling text-green-600 dark:text-green-400"></i>
                                            <label class="block text-sm font-medium text-gray-900 dark:text-white">Soil Moisture (%)</label>
                                        </div>
                                        <div class="space-y-2">
                                            <div>
                                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Minimum (Optimal)</label>
                                                <input type="number" name="soil_min" step="0.1" value="<?php echo $thresholds['soil_moisture']['min']; ?>" 
                                                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Maximum (Optimal)</label>
                                                <input type="number" name="soil_max" step="0.1" value="<?php echo $thresholds['soil_moisture']['max']; ?>" 
                                                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Current: <?php echo $thresholds['soil_moisture']['min']; ?>-<?php echo $thresholds['soil_moisture']['max']; ?>%
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Info Box -->
                                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                    <p class="text-xs text-blue-700 dark:text-blue-300">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <strong>Note:</strong> These thresholds determine when sensors show "Optimal", "Warning", or "Critical" status. 
                                        Adjust based on your crop requirements and local climate conditions.
                                    </p>
                                </div>
                                
                                <!-- Save Button -->
                                <div class="flex justify-end mt-4">
                                    <button type="submit" class="px-6 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                                        <i class="fas fa-save mr-2"></i>
                                        Save Thresholds
                                    </button>
                                </div>
                            </form>
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

    // Initialize with sensor tab active
    document.addEventListener('DOMContentLoaded', function() {
        showTab('sensor');
    });

    // Update interval preview
    function updateIntervalPreview(minutes, formatted) {
        const display = document.getElementById('current-interval-display');
        if (display) {
            display.textContent = formatted;
            display.style.transform = 'scale(1.1)';
            display.style.color = '#2563eb';
            setTimeout(() => {
                display.style.transform = 'scale(1)';
                display.style.color = '';
            }, 300);
        }
    }

    // Daily Report Toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const dailyReportToggle = document.getElementById('daily-report-toggle');
        const dailyReportSettings = document.getElementById('daily-report-settings');
        const dailyReportInput = document.getElementById('daily-report-enabled-input');

        if (dailyReportToggle) {
            dailyReportToggle.addEventListener('click', function() {
                const span = this.querySelector('span');
                const isActive = span.classList.contains('translate-x-6');

                if (isActive) {
                    // Turn off
                    span.classList.remove('translate-x-6');
                    span.classList.add('translate-x-1');
                    this.classList.remove('bg-green-600');
                    this.classList.add('bg-gray-200', 'dark:bg-gray-600');
                    dailyReportSettings.classList.add('hidden');
                    dailyReportInput.value = '0';
                } else {
                    // Turn on
                    span.classList.remove('translate-x-1');
                    span.classList.add('translate-x-6');
                    this.classList.remove('bg-gray-200', 'dark:bg-gray-600');
                    this.classList.add('bg-green-600');
                    dailyReportSettings.classList.remove('hidden');
                    dailyReportInput.value = '1';
                }
            });
        }

        // Other toggle switches functionality
        const toggleButtons = document.querySelectorAll('button[class*="inline-flex h-6 w-11"]:not(#daily-report-toggle)');

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