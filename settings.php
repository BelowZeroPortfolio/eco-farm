<?php

/**
 * Settings Management Page for IoT Farm Monitoring System
 * 
 * Allows users to configure dashboard appearance, notification preferences,
 * and system settings with placeholders for future IoT/AI configurations
 */

// Start session
session_start();

// Simple authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update_appearance':
                $result = updateAppearanceSettings($_POST);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;

            case 'update_notifications':
                $result = updateNotificationSettings($_POST);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;

            case 'update_system':
                $result = updateSystemSettings($_POST);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
    }
}

/**
 * Update appearance settings
 */
function updateAppearanceSettings($data)
{
    try {
        $userId = getUserId();
        $settings = [
            'theme' => $data['theme'] ?? 'light',
            'dashboard_layout' => $data['dashboard_layout'] ?? 'grid',
            'sidebar_collapsed' => isset($data['sidebar_collapsed']) ? '1' : '0',
            'chart_style' => $data['chart_style'] ?? 'modern'
        ];

        foreach ($settings as $key => $value) {
            saveSetting($userId, $key, $value);
        }

        return ['success' => true, 'message' => 'Appearance settings updated successfully.'];
    } catch (Exception $e) {
        error_log("Update appearance settings failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update appearance settings.'];
    }
}

/**
 * Update notification settings
 */
function updateNotificationSettings($data)
{
    try {
        $userId = getUserId();
        $settings = [
            'email_notifications' => isset($data['email_notifications']) ? '1' : '0',
            'pest_alerts' => isset($data['pest_alerts']) ? '1' : '0',
            'sensor_alerts' => isset($data['sensor_alerts']) ? '1' : '0',
            'system_alerts' => isset($data['system_alerts']) ? '1' : '0',
            'alert_frequency' => $data['alert_frequency'] ?? 'immediate',
            'quiet_hours_start' => $data['quiet_hours_start'] ?? '',
            'quiet_hours_end' => $data['quiet_hours_end'] ?? ''
        ];

        foreach ($settings as $key => $value) {
            saveSetting($userId, $key, $value);
        }

        return ['success' => true, 'message' => 'Notification settings updated successfully.'];
    } catch (Exception $e) {
        error_log("Update notification settings failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update notification settings.'];
    }
}

/**
 * Update system settings (admin only)
 */
function updateSystemSettings($data)
{
    try {
        if (!isAdmin()) {
            return ['success' => false, 'message' => 'Access denied. Admin privileges required.'];
        }

        $userId = getUserId();
        $settings = [
            'data_retention_days' => $data['data_retention_days'] ?? '365',
            'auto_backup' => isset($data['auto_backup']) ? '1' : '0',
            'maintenance_mode' => isset($data['maintenance_mode']) ? '1' : '0',
            'debug_mode' => isset($data['debug_mode']) ? '1' : '0'
        ];

        foreach ($settings as $key => $value) {
            saveSetting($userId, 'system_' . $key, $value);
        }

        return ['success' => true, 'message' => 'System settings updated successfully.'];
    } catch (Exception $e) {
        error_log("Update system settings failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update system settings.'];
    }
}

/**
 * Save user setting to database
 */
function saveSetting($userId, $key, $value)
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            INSERT INTO user_settings (user_id, setting_key, setting_value) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value), 
            updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$userId, $key, $value]);
    } catch (Exception $e) {
        error_log("Save setting failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user setting from database
 */
function getSetting($userId, $key, $default = '')
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = ?");
        $stmt->execute([$userId, $key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        error_log("Get setting failed: " . $e->getMessage());
        return $default;
    }
}

// Get current settings
$userId = getUserId();
$currentSettings = [
    'theme' => getSetting($userId, 'theme', 'light'),
    'dashboard_layout' => getSetting($userId, 'dashboard_layout', 'grid'),
    'sidebar_collapsed' => getSetting($userId, 'sidebar_collapsed', '0'),
    'chart_style' => getSetting($userId, 'chart_style', 'modern'),
    'email_notifications' => getSetting($userId, 'email_notifications', '1'),
    'pest_alerts' => getSetting($userId, 'pest_alerts', '1'),
    'sensor_alerts' => getSetting($userId, 'sensor_alerts', '1'),
    'system_alerts' => getSetting($userId, 'system_alerts', '1'),
    'alert_frequency' => getSetting($userId, 'alert_frequency', 'immediate'),
    'quiet_hours_start' => getSetting($userId, 'quiet_hours_start', ''),
    'quiet_hours_end' => getSetting($userId, 'quiet_hours_end', ''),
    'data_retention_days' => getSetting($userId, 'system_data_retention_days', '365'),
    'auto_backup' => getSetting($userId, 'system_auto_backup', '0'),
    'maintenance_mode' => getSetting($userId, 'system_maintenance_mode', '0'),
    'debug_mode' => getSetting($userId, 'system_debug_mode', '0')
];

// Set page title for header component
$pageTitle = 'Settings - IoT Farm Monitoring System';

// Include shared header
include 'includes/header.php';
?>
<?php
// Include shared navigation component (sidebar)
include 'includes/navigation.php';
?>

<!-- Settings Content -->
<div class="p-6 lg:p-8 space-y-8">

    <!-- Page Header -->
    <div class="animate-slide-up">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-display-md font-display text-secondary-900 mb-2">Settings</h1>
                <p class="text-body-lg text-secondary-600">Customize your dashboard experience and system preferences</p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-cog text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($message): ?>
        <div class="animate-fade-in">
            <div class="p-4 rounded-lg border <?php echo $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3"></i>
                    <span class="text-body-md font-medium"><?php echo htmlspecialchars($message); ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Settings Sections -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">

        <!-- Dashboard Appearance Settings -->
        <div class="animate-slide-up">
            <div class="card-elevated">
                <div class="bg-gradient-to-r from-primary-50 to-primary-100 px-6 py-4 border-b border-primary-200">
                    <h2 class="text-heading-lg text-secondary-900 flex items-center">
                        <i class="fas fa-palette text-primary-600 mr-3"></i>
                        Dashboard Appearance
                    </h2>
                    <p class="text-body-sm text-secondary-600 mt-1">Customize the look and feel of your dashboard</p>
                </div>
                <form method="POST" class="p-6 space-y-6">
                    <input type="hidden" name="action" value="update_appearance">

                    <!-- Theme Selection -->
                    <div>
                        <label class="block text-body-md font-medium text-secondary-900 mb-3">Theme</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="theme" value="light" <?php echo $currentSettings['theme'] === 'light' ? 'checked' : ''; ?> class="sr-only">
                                <div class="p-4 border-2 rounded-lg transition-all duration-200 hover:border-primary-300 <?php echo $currentSettings['theme'] === 'light' ? 'border-primary-500 bg-primary-50' : 'border-secondary-200'; ?>">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-white border border-secondary-300 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-sun text-yellow-500"></i>
                                        </div>
                                        <div>
                                            <div class="text-body-md font-medium text-secondary-900">Light Theme</div>
                                            <div class="text-body-sm text-secondary-600">Clean and bright interface</div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="theme" value="dark" <?php echo $currentSettings['theme'] === 'dark' ? 'checked' : ''; ?> class="sr-only">
                                <div class="p-4 border-2 rounded-lg transition-all duration-200 hover:border-primary-300 <?php echo $currentSettings['theme'] === 'dark' ? 'border-primary-500 bg-primary-50' : 'border-secondary-200'; ?>">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-secondary-800 border border-secondary-600 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-moon text-blue-400"></i>
                                        </div>
                                        <div>
                                            <div class="text-body-md font-medium text-secondary-900">Dark Theme</div>
                                            <div class="text-body-sm text-secondary-600">Easy on the eyes</div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Dashboard Layout -->
                    <div>
                        <label class="block text-body-md font-medium text-secondary-900 mb-3">Dashboard Layout</label>
                        <select name="dashboard_layout" class="w-full form-input">
                            <option value="grid" <?php echo $currentSettings['dashboard_layout'] === 'grid' ? 'selected' : ''; ?>>Grid Layout</option>
                            <option value="list" <?php echo $currentSettings['dashboard_layout'] === 'list' ? 'selected' : ''; ?>>List Layout</option>
                            <option value="compact" <?php echo $currentSettings['dashboard_layout'] === 'compact' ? 'selected' : ''; ?>>Compact Layout</option>
                        </select>
                    </div>

                    <!-- Chart Style -->
                    <div>
                        <label class="block text-body-md font-medium text-secondary-900 mb-3">Chart Style</label>
                        <select name="chart_style" class="w-full form-input">
                            <option value="modern" <?php echo $currentSettings['chart_style'] === 'modern' ? 'selected' : ''; ?>>Modern</option>
                            <option value="classic" <?php echo $currentSettings['chart_style'] === 'classic' ? 'selected' : ''; ?>>Classic</option>
                            <option value="minimal" <?php echo $currentSettings['chart_style'] === 'minimal' ? 'selected' : ''; ?>>Minimal</option>
                        </select>
                    </div>

                    <!-- Sidebar Options -->
                    <div>
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="sidebar_collapsed" <?php echo $currentSettings['sidebar_collapsed'] === '1' ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                            <div>
                                <div class="text-body-md font-medium text-secondary-900">Collapse Sidebar by Default</div>
                                <div class="text-body-sm text-secondary-600">Start with a collapsed navigation sidebar</div>
                            </div>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-secondary-200">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            Save Appearance Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notification Preferences -->
        <div class="animate-slide-up">
            <div class="card-elevated">
                <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 px-6 py-4 border-b border-yellow-200">
                    <h2 class="text-heading-lg text-secondary-900 flex items-center">
                        <i class="fas fa-bell text-yellow-600 mr-3"></i>
                        Notification Preferences
                    </h2>
                    <p class="text-body-sm text-secondary-600 mt-1">Configure how and when you receive alerts</p>
                </div>
                <form method="POST" class="p-6 space-y-6">
                    <input type="hidden" name="action" value="update_notifications">

                    <!-- Notification Types -->
                    <div>
                        <label class="block text-body-md font-medium text-secondary-900 mb-4">Alert Types</label>
                        <div class="space-y-4">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="email_notifications" <?php echo $currentSettings['email_notifications'] === '1' ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                                <div>
                                    <div class="text-body-md font-medium text-secondary-900">Email Notifications</div>
                                    <div class="text-body-sm text-secondary-600">Receive alerts via email</div>
                                </div>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="pest_alerts" <?php echo $currentSettings['pest_alerts'] === '1' ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                                <div>
                                    <div class="text-body-md font-medium text-secondary-900">Pest Detection Alerts</div>
                                    <div class="text-body-sm text-secondary-600">Notifications for pest events</div>
                                </div>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="sensor_alerts" <?php echo $currentSettings['sensor_alerts'] === '1' ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                                <div>
                                    <div class="text-body-md font-medium text-secondary-900">Sensor Alerts</div>
                                    <div class="text-body-sm text-secondary-600">Notifications for sensor issues</div>
                                </div>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="system_alerts" <?php echo $currentSettings['system_alerts'] === '1' ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                                <div>
                                    <div class="text-body-md font-medium text-secondary-900">System Alerts</div>
                                    <div class="text-body-sm text-secondary-600">System status notifications</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Alert Frequency -->
                    <div>
                        <label class="block text-body-md font-medium text-secondary-900 mb-3">Alert Frequency</label>
                        <select name="alert_frequency" class="w-full form-input">
                            <option value="immediate" <?php echo $currentSettings['alert_frequency'] === 'immediate' ? 'selected' : ''; ?>>Immediate</option>
                            <option value="hourly" <?php echo $currentSettings['alert_frequency'] === 'hourly' ? 'selected' : ''; ?>>Hourly Digest</option>
                            <option value="daily" <?php echo $currentSettings['alert_frequency'] === 'daily' ? 'selected' : ''; ?>>Daily Digest</option>
                            <option value="weekly" <?php echo $currentSettings['alert_frequency'] === 'weekly' ? 'selected' : ''; ?>>Weekly Digest</option>
                        </select>
                    </div>

                    <!-- Quiet Hours -->
                    <div>
                        <label class="block text-body-md font-medium text-secondary-900 mb-3">Quiet Hours</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-body-sm text-secondary-600 mb-2">Start Time</label>
                                <input type="time" name="quiet_hours_start" value="<?php echo htmlspecialchars($currentSettings['quiet_hours_start']); ?>" class="w-full form-input">
                            </div>
                            <div>
                                <label class="block text-body-sm text-secondary-600 mb-2">End Time</label>
                                <input type="time" name="quiet_hours_end" value="<?php echo htmlspecialchars($currentSettings['quiet_hours_end']); ?>" class="w-full form-input">
                            </div>
                        </div>
                        <p class="text-body-xs text-secondary-500 mt-2">No notifications will be sent during quiet hours</p>
                    </div>

                    <div class="pt-4 border-t border-secondary-200">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            Save Notification Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- System Settings (Admin Only) -->
    <?php if (isAdmin()): ?>
        <div class="animate-slide-up">
            <div class="card-elevated">
                <div class="bg-gradient-to-r from-red-50 to-red-100 px-6 py-4 border-b border-red-200">
                    <h2 class="text-heading-lg text-secondary-900 flex items-center">
                        <i class="fas fa-server text-red-600 mr-3"></i>
                        System Settings
                        <span class="ml-3 px-2 py-1 bg-red-100 text-red-800 text-body-xs font-medium rounded-full">Admin Only</span>
                    </h2>
                    <p class="text-body-sm text-secondary-600 mt-1">Configure system-wide settings and maintenance options</p>
                </div>
                <form method="POST" class="p-6 space-y-6">
                    <input type="hidden" name="action" value="update_system">

                    <!-- Data Retention -->
                    <div>
                        <label class="block text-body-md font-medium text-secondary-900 mb-3">Data Retention Period</label>
                        <select name="data_retention_days" class="w-full form-input">
                            <option value="30" <?php echo $currentSettings['data_retention_days'] === '30' ? 'selected' : ''; ?>>30 Days</option>
                            <option value="90" <?php echo $currentSettings['data_retention_days'] === '90' ? 'selected' : ''; ?>>90 Days</option>
                            <option value="180" <?php echo $currentSettings['data_retention_days'] === '180' ? 'selected' : ''; ?>>6 Months</option>
                            <option value="365" <?php echo $currentSettings['data_retention_days'] === '365' ? 'selected' : ''; ?>>1 Year</option>
                            <option value="730" <?php echo $currentSettings['data_retention_days'] === '730' ? 'selected' : ''; ?>>2 Years</option>
                        </select>
                        <p class="text-body-xs text-secondary-500 mt-2">How long to keep sensor data and alerts</p>
                    </div>

                    <!-- System Options -->
                    <div class="space-y-4">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="auto_backup" <?php echo $currentSettings['auto_backup'] === '1' ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                            <div>
                                <div class="text-body-md font-medium text-secondary-900">Automatic Backups</div>
                                <div class="text-body-sm text-secondary-600">Enable daily database backups</div>
                            </div>
                        </label>
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="maintenance_mode" <?php echo $currentSettings['maintenance_mode'] === '1' ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                            <div>
                                <div class="text-body-md font-medium text-secondary-900">Maintenance Mode</div>
                                <div class="text-body-sm text-secondary-600">Restrict access for system maintenance</div>
                            </div>
                        </label>
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="debug_mode" <?php echo $currentSettings['debug_mode'] === '1' ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                            <div>
                                <div class="text-body-md font-medium text-secondary-900">Debug Mode</div>
                                <div class="text-body-sm text-secondary-600">Enable detailed error logging</div>
                            </div>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-secondary-200">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            Save System Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Future IoT/AI Configuration Placeholders -->
    <div class="animate-slide-up">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">

            <!-- IoT Device Configuration -->
            <div class="card-elevated opacity-60">
                <div class="bg-gradient-to-r from-green-50 to-green-100 px-6 py-4 border-b border-green-200">
                    <h2 class="text-heading-lg text-secondary-900 flex items-center">
                        <i class="fas fa-microchip text-green-600 mr-3"></i>
                        IoT Device Configuration
                        <span class="ml-3 px-2 py-1 bg-yellow-100 text-yellow-800 text-body-xs font-medium rounded-full">Coming Soon</span>
                    </h2>
                    <p class="text-body-sm text-secondary-600 mt-1">Configure real IoT sensor connections and protocols</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4 text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto">
                            <i class="fas fa-wifi text-green-600 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-heading-md text-secondary-900 mb-2">Real-time IoT Integration</h3>
                            <p class="text-body-md text-secondary-600 mb-4">Connect and configure physical sensors, set communication protocols, and manage device networks.</p>
                            <div class="space-y-2 text-body-sm text-secondary-500">
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    MQTT Protocol Support
                                </div>
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    LoRaWAN Integration
                                </div>
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    Device Management
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Configuration -->
            <div class="card-elevated opacity-60">
                <div class="bg-gradient-to-r from-purple-50 to-purple-100 px-6 py-4 border-b border-purple-200">
                    <h2 class="text-heading-lg text-secondary-900 flex items-center">
                        <i class="fas fa-brain text-purple-600 mr-3"></i>
                        AI & Machine Learning
                        <span class="ml-3 px-2 py-1 bg-yellow-100 text-yellow-800 text-body-xs font-medium rounded-full">Coming Soon</span>
                    </h2>
                    <p class="text-body-sm text-secondary-600 mt-1">Configure AI models for pest detection and predictive analytics</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4 text-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto">
                            <i class="fas fa-robot text-purple-600 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-heading-md text-secondary-900 mb-2">Intelligent Farm Monitoring</h3>
                            <p class="text-body-md text-secondary-600 mb-4">Advanced AI algorithms for pest detection, crop health analysis, and predictive maintenance.</p>
                            <div class="space-y-2 text-body-sm text-secondary-500">
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    Computer Vision Models
                                </div>
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    Predictive Analytics
                                </div>
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    Automated Alerts
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include shared footer
include 'includes/footer.php';
?>
</main>
</div>
</div>

<script>
    // Settings page JavaScript functionality
    document.addEventListener('DOMContentLoaded', function() {

        // Theme preview functionality
        const themeInputs = document.querySelectorAll('input[name="theme"]');
        themeInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Add visual feedback for theme selection
                const allLabels = document.querySelectorAll('input[name="theme"]').forEach(radio => {
                    const label = radio.closest('label');
                    const div = label.querySelector('div');
                    if (radio.checked) {
                        div.classList.add('border-primary-500', 'bg-primary-50');
                        div.classList.remove('border-secondary-200');
                    } else {
                        div.classList.remove('border-primary-500', 'bg-primary-50');
                        div.classList.add('border-secondary-200');
                    }
                });
            });
        });

        // Form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

                    // Re-enable button after 3 seconds to prevent permanent disable
                    setTimeout(() => {
                        submitButton.disabled = false;
                        submitButton.innerHTML = submitButton.innerHTML.replace('Saving...', 'Save Settings');
                    }, 3000);
                }
            });
        });

        // Quiet hours validation
        const startTimeInput = document.querySelector('input[name="quiet_hours_start"]');
        const endTimeInput = document.querySelector('input[name="quiet_hours_end"]');

        if (startTimeInput && endTimeInput) {
            function validateQuietHours() {
                const startTime = startTimeInput.value;
                const endTime = endTimeInput.value;

                if (startTime && endTime) {
                    const start = new Date('2000-01-01 ' + startTime);
                    const end = new Date('2000-01-01 ' + endTime);

                    if (start >= end) {
                        endTimeInput.setCustomValidity('End time must be after start time');
                    } else {
                        endTimeInput.setCustomValidity('');
                    }
                }
            }

            startTimeInput.addEventListener('change', validateQuietHours);
            endTimeInput.addEventListener('change', validateQuietHours);
        }

        // Auto-hide success messages
        const successMessages = document.querySelectorAll('.bg-green-50');
        successMessages.forEach(message => {
            setTimeout(() => {
                message.style.transition = 'opacity 0.5s ease-out';
                message.style.opacity = '0';
                setTimeout(() => {
                    message.remove();
                }, 500);
            }, 5000);
        });
    });
</script>