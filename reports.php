<?php

/**
 * Reports Generation Module for IoT Farm Monitoring System
 * 
 * Generates sensor and pest data reports with date range filtering,
 * Chart.js visualizations, and CSV/PDF export functionality
 */

// Start session
session_start();

// Simple authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/export-handler.php';

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Handle date range filtering
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$reportType = $_GET['report_type'] ?? 'sensor';

// Validate date range
if (strtotime($startDate) > strtotime($endDate)) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

// Handle export requests FIRST to prevent any output before headers
if (isset($_GET['export']) && in_array($_GET['export'], ['csv', 'pdf'])) {
    $exportHandler = new ExportHandler();

    try {
        $format = $_GET['export'];

        // Log export attempt
        $exportHandler->logExportActivity(
            $currentUser['id'],
            $reportType,
            $format,
            $startDate,
            $endDate,
            false // Will be updated to true if successful
        );

        if ($format === 'csv') {
            // Export to CSV directly
            $exportHandler->exportToCSV($currentUser['id'], $currentUser['role'], $reportType, $startDate, $endDate);
            exit();
        } elseif ($format === 'pdf') {
            $exportHandler->exportToPDF($currentUser['id'], $currentUser['role'], $reportType, $startDate, $endDate);
            exit();
        }

        // Log successful export
        $exportHandler->logExportActivity(
            $currentUser['id'],
            $reportType,
            $format,
            $startDate,
            $endDate,
            true
        );
    } catch (Exception $e) {
        // Log error and show user-friendly message
        error_log("Export error: " . $e->getMessage());
        $_SESSION['error_message'] = "Export failed: " . $e->getMessage();
        
        // Remove export parameter to prevent redirect loop
        $redirectParams = $_GET;
        unset($redirectParams['export']);
        
        header("Location: reports.php?" . http_build_query($redirectParams, '', '&', PHP_QUERY_RFC3986));
    }
    exit;
}

/**
 * Get sensor data report for date range
 */
function getSensorReport($startDate, $endDate)
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT 
                s.sensor_name,
                s.sensor_type,
                s.location,
                AVG(sr.value) as avg_value,
                MIN(sr.value) as min_value,
                MAX(sr.value) as max_value,
                COUNT(sr.id) as reading_count,
                sr.unit,
                DATE(sr.recorded_at) as date
            FROM sensors s
            JOIN sensor_readings sr ON s.id = sr.sensor_id
            WHERE DATE(sr.recorded_at) BETWEEN ? AND ?
            GROUP BY s.id, DATE(sr.recorded_at)
            ORDER BY sr.recorded_at DESC, s.sensor_type, s.sensor_name
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get sensor report: " . $e->getMessage());
        return [];
    }
}

/**
 * Get pest data report for date range with enhanced details
 */
function getPestReport($startDate, $endDate)
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT 
                pa.id,
                pa.pest_type,
                pa.location,
                pa.severity,
                pa.status,
                pa.confidence_score,
                pa.description,
                pa.suggested_actions,
                pa.detected_at,
                pa.image_path,
                pa.is_read,
                DATE(pa.detected_at) as date,
                TIME(pa.detected_at) as time,
                c.camera_name,
                c.location as camera_location,
                COUNT(*) OVER (PARTITION BY pa.pest_type) as type_count,
                COUNT(*) OVER (PARTITION BY pa.severity) as severity_count
            FROM pest_alerts pa
            LEFT JOIN cameras c ON pa.camera_id = c.id
            WHERE DATE(pa.detected_at) BETWEEN ? AND ?
            ORDER BY pa.detected_at DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get pest report: " . $e->getMessage());
        return [];
    }
}

/**
 * Get report summary statistics
 */
function getReportSummary($startDate, $endDate)
{
    try {
        $pdo = getDatabaseConnection();

        // Sensor statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT s.id) as total_sensors,
                COUNT(sr.id) as total_readings,
                COUNT(DISTINCT DATE(sr.recorded_at)) as active_days
            FROM sensors s
            LEFT JOIN sensor_readings sr ON s.id = sr.sensor_id
            WHERE DATE(sr.recorded_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $sensorStats = $stmt->fetch();

        // Pest statistics with enhanced metrics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_alerts,
                COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_alerts,
                COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_alerts,
                COUNT(CASE WHEN status = 'new' THEN 1 END) as new_alerts,
                COUNT(DISTINCT pest_type) as unique_pests,
                AVG(confidence_score) as avg_confidence,
                COUNT(DISTINCT camera_id) as cameras_with_detections
            FROM pest_alerts
            WHERE DATE(detected_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $pestStats = $stmt->fetch();

        return [
            'sensor_stats' => $sensorStats,
            'pest_stats' => $pestStats,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
                'days' => (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1
            ]
        ];
    } catch (Exception $e) {
        error_log("Failed to get report summary: " . $e->getMessage());
        return [
            'sensor_stats' => ['total_sensors' => 0, 'total_readings' => 0, 'active_days' => 0],
            'pest_stats' => ['total_alerts' => 0, 'critical_alerts' => 0, 'resolved_alerts' => 0, 'unique_pests' => 0],
            'date_range' => ['start' => $startDate, 'end' => $endDate, 'days' => 0]
        ];
    }
}

/**
 * Get chart data for visualizations
 */
function getChartData($reportType, $startDate, $endDate)
{
    try {
        $pdo = getDatabaseConnection();

        if ($reportType === 'sensor') {
            // Daily sensor averages
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(sr.recorded_at) as date,
                    s.sensor_type,
                    AVG(sr.value) as avg_value,
                    sr.unit
                FROM sensors s
                JOIN sensor_readings sr ON s.id = sr.sensor_id
                WHERE DATE(sr.recorded_at) BETWEEN ? AND ?
                GROUP BY DATE(sr.recorded_at), s.sensor_type, sr.unit
                ORDER BY date ASC
            ");
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll();
        } else {
            // Daily pest alert counts
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(detected_at) as date,
                    severity,
                    COUNT(*) as count
                FROM pest_alerts
                WHERE DATE(detected_at) BETWEEN ? AND ?
                GROUP BY DATE(detected_at), severity
                ORDER BY date ASC
            ");
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll();
        }
    } catch (Exception $e) {
        error_log("Failed to get chart data: " . $e->getMessage());
        return [];
    }
}

// Export functions moved to includes/export-handler.php for better organization and security

// Get data for the current page
$reportData = $reportType === 'sensor' ? getSensorReport($startDate, $endDate) : getPestReport($startDate, $endDate);
$summary = getReportSummary($startDate, $endDate);
$chartData = getChartData($reportType, $startDate, $endDate);

// Set page title for header component
$pageTitle = 'Reports - IoT Farm Monitoring System';

// Include shared header
include 'includes/header.php';
?>
<?php
// Include shared navigation component (sidebar)
include 'includes/navigation.php';
?>

<!-- Reports Content -->
<div class="p-4 max-w-7xl mx-auto">

    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-chart-bar text-green-600 mr-3"></i>
                    Reports & Analytics
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Generate and export detailed reports for sensor data and pest detection
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="showQuickGuide()" class="px-3 py-2 text-sm bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors">
                    <i class="fas fa-question-circle mr-1"></i>
                    Quick Guide
                </button>
            </div>
        </div>
        
        <!-- Breadcrumb -->
        <nav class="flex text-xs text-gray-500 dark:text-gray-400">
            <a href="dashboard.php" class="hover:text-green-600 dark:hover:text-green-400">Dashboard</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 dark:text-white">Reports</span>
        </nav>
    </div>

    <!-- Error/Success Messages -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="animate-slide-up mb-4">
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 mr-2"></i>
                    <span class="text-sm text-red-800 dark:text-red-300"><?php echo htmlspecialchars($_SESSION['error_message']); ?></span>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="animate-slide-up mb-4">
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-2"></i>
                    <span class="text-sm text-green-800 dark:text-green-300"><?php echo htmlspecialchars($_SESSION['success_message']); ?></span>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- Report Controls Card -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm mb-6">
        <!-- Card Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-filter text-green-600 mr-2"></i>
                        Report Configuration
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Select report type and date range to generate your report</p>
                </div>
                <!-- Quick Date Presets -->
                <div class="hidden lg:flex items-center space-x-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Quick Select:</span>
                    <button onclick="setDateRange('today')" class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">Today</button>
                    <button onclick="setDateRange('week')" class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">This Week</button>
                    <button onclick="setDateRange('month')" class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">This Month</button>
                </div>
            </div>
        </div>
        
        <!-- Card Body -->
        <div class="p-6">
            <form method="GET" id="reportForm" class="space-y-4">
                <!-- Report Type Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="report_type" value="sensor" <?php echo $reportType === 'sensor' ? 'checked' : ''; ?> 
                               class="peer sr-only" onchange="this.form.submit()">
                        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 hover:border-gray-300 dark:hover:border-gray-600 transition-all">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-thermometer-half text-blue-600 dark:text-blue-400"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">Sensor Data Report</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Temperature, humidity, soil moisture</p>
                                    </div>
                                </div>
                                <i class="fas fa-check-circle text-green-500 text-xl hidden peer-checked:block"></i>
                            </div>
                        </div>
                    </label>
                    
                    <label class="relative cursor-pointer">
                        <input type="radio" name="report_type" value="pest" <?php echo $reportType === 'pest' ? 'checked' : ''; ?> 
                               class="peer sr-only" onchange="this.form.submit()">
                        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 hover:border-gray-300 dark:hover:border-gray-600 transition-all">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-bug text-yellow-600 dark:text-yellow-400"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">Pest Detection Report</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">AI detections, alerts, and actions</p>
                                    </div>
                                </div>
                                <i class="fas fa-check-circle text-green-500 text-xl hidden peer-checked:block"></i>
                            </div>
                        </div>
                    </label>
                </div>
                
                <!-- Date Range and Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-calendar-alt text-gray-400 mr-1"></i>
                            Start Date
                        </label>
                        <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-calendar-alt text-gray-400 mr-1"></i>
                            End Date
                        </label>
                        <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-cog text-gray-400 mr-1"></i>
                            Actions
                        </label>
                        <div class="flex space-x-2">
                            <button type="submit" class="flex-1 bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                                <i class="fas fa-sync-alt mr-2"></i>
                                Generate Report
                            </button>
                            <div class="relative">
                                <button type="button" 
                                        onclick="toggleExportDropdown()"
                                        id="exportDropdownButton"
                                        class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                                    <i class="fas fa-download mr-1"></i>
                                    <i class="fas fa-chevron-down text-xs ml-1"></i>
                                </button>
                                <!-- Export Dropdown -->
                                <div id="exportDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 z-50">
                                    <div class="py-1">
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>"
                                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <i class="fas fa-file-csv text-green-600 mr-2"></i>
                                            Export as CSV
                                        </a>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>"
                                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                           target="_blank">
                                            <i class="fas fa-file-pdf text-red-600 mr-2"></i>
                                            Print Report
                                        </a>
                                        <button type="button" onclick="showExportOptions(); toggleExportDropdown();"
                                                class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                            <i class="fas fa-cog text-gray-600 mr-2"></i>
                                            Advanced Options
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Left Column - Charts and Data -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Summary Statistics -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-chart-pie text-green-600 mr-2"></i>
                    Key Metrics
                    <span class="ml-2 text-xs font-normal text-gray-500 dark:text-gray-400">
                        (<?php echo date('M j', strtotime($startDate)); ?> - <?php echo date('M j, Y', strtotime($endDate)); ?>)
                    </span>
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php if ($reportType === 'sensor'): ?>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-thermometer-half text-blue-600 dark:text-blue-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Total Sensors</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $summary['sensor_stats']['total_sensors']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-chart-line text-green-600 dark:text-green-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Total Readings</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($summary['sensor_stats']['total_readings']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-calendar text-purple-600 dark:text-purple-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Active Days</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $summary['sensor_stats']['active_days']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-clock text-orange-600 dark:text-orange-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Date Range</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $summary['date_range']['days']; ?> days</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-bug text-yellow-600 dark:text-yellow-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Total Alerts</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $summary['pest_stats']['total_alerts']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Critical Alerts</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $summary['pest_stats']['critical_alerts']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-list text-purple-600 dark:text-purple-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Unique Pests</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $summary['pest_stats']['unique_pests']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-percentage text-blue-600 dark:text-blue-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Avg Detection Confidence</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                    <?php echo $summary['pest_stats']['avg_confidence'] ? number_format($summary['pest_stats']['avg_confidence'], 1) . '%' : 'N/A'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
            </div>

            <!-- Data Visualization -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                    Trend Analysis
                </h2>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-chart-bar text-green-600 mr-2"></i>
                        <?php echo $reportType === 'sensor' ? 'Sensor Data Trends' : 'Pest Alert Trends'; ?>
                    </h3>
                </div>
                <div class="p-4">
                    <?php if (!empty($chartData)): ?>
                        <canvas id="reportChart" width="400" height="200"></canvas>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-chart-bar text-gray-400 dark:text-gray-500 text-2xl"></i>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">No data available for the selected date range</p>
                        </div>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Report Insights -->
        <div class="space-y-6">
            <!-- Report Summary -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-clipboard-list text-purple-600 mr-2"></i>
                    Summary
                </h2>
            </div>

            <!-- Report Insights -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                    Insights & Recommendations
                </h2>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
                <div class="space-y-3">
                    <?php if ($reportType === 'sensor'): ?>
                        <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fas fa-chart-line text-green-600 dark:text-green-400 text-xs"></i>
                                <span class="text-xs font-medium text-gray-900 dark:text-white">Data Collection</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <?php echo number_format($summary['sensor_stats']['total_readings']); ?> readings collected from <?php echo $summary['sensor_stats']['total_sensors']; ?> sensors
                            </p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fas fa-calendar text-blue-600 dark:text-blue-400 text-xs"></i>
                                <span class="text-xs font-medium text-gray-900 dark:text-white">Activity Period</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                Active data collection for <?php echo $summary['sensor_stats']['active_days']; ?> out of <?php echo $summary['date_range']['days']; ?> days
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fas fa-bug text-yellow-600 dark:text-yellow-400 text-xs"></i>
                                <span class="text-xs font-medium text-gray-900 dark:text-white">Pest Activity</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <?php echo $summary['pest_stats']['total_alerts']; ?> alerts from <?php echo $summary['pest_stats']['unique_pests']; ?> different pest types
                            </p>
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xs"></i>
                                <span class="text-xs font-medium text-gray-900 dark:text-white">Critical Issues</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <?php echo $summary['pest_stats']['critical_alerts']; ?> critical alerts requiring immediate attention
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-table text-green-600 mr-2"></i>
                Detailed <?php echo $reportType === 'sensor' ? 'Sensor Data' : 'Pest Detection'; ?> Report
            </h2>
            <?php if (!empty($reportData)): ?>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-database mr-1"></i>
                    <?php echo count($reportData); ?> records found
                </span>
            <?php endif; ?>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">

        <?php if (!empty($reportData)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                                <?php if ($reportType === 'sensor'): ?>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Sensor</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Location</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Avg Value</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Range</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Readings</th>
                                <?php else: ?>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Date/Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Pest Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Severity</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Confidence</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Suggested Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($reportData as $row): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <?php if ($reportType === 'sensor'): ?>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo date('M j', strtotime($row['date'])); ?></td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($row['sensor_name']); ?></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-3 py-1 text-xs font-semibold bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 rounded-full">
                                                <?php echo ucfirst(str_replace('_', ' ', $row['sensor_type'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            <?php echo number_format($row['avg_value'], 1) . $row['unit']; ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            <?php echo number_format($row['min_value'], 1) . ' - ' . number_format($row['max_value'], 1) . $row['unit']; ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300"><?php echo $row['reading_count']; ?></td>
                                    <?php else: ?>
                                        <td class="px-4 py-3 text-xs text-gray-900 dark:text-gray-100">
                                            <div class="font-medium"><?php echo date('M j, Y', strtotime($row['date'])); ?></div>
                                            <div class="text-gray-600 dark:text-gray-400"><?php echo date('g:i A', strtotime($row['time'])); ?></div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <?php if ($row['image_path']): ?>
                                                    <i class="fas fa-image text-blue-500 dark:text-blue-400 mr-2 text-xs"></i>
                                                <?php endif; ?>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($row['pest_type']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php
                                            $severityColors = [
                                                'low' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200',
                                                'medium' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200',
                                                'high' => 'bg-orange-100 dark:bg-orange-900/50 text-orange-800 dark:text-orange-200',
                                                'critical' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200'
                                            ];
                                            $colorClass = $severityColors[$row['severity']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold <?php echo $colorClass; ?> rounded-full">
                                                <i class="fas fa-circle text-xs mr-1"></i>
                                                <?php echo ucfirst($row['severity']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php if ($row['confidence_score']): ?>
                                                <div class="flex items-center">
                                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-2">
                                                        <div class="bg-blue-600 dark:bg-blue-500 h-2.5 rounded-full" style="width: <?php echo $row['confidence_score']; ?>%"></div>
                                                    </div>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo number_format($row['confidence_score'], 1); ?>%</span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-md">
                                            <?php if ($row['suggested_actions']): ?>
                                                <div class="line-clamp-2" title="<?php echo htmlspecialchars($row['suggested_actions']); ?>">
                                                    <?php echo htmlspecialchars($row['suggested_actions']); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-500 dark:text-gray-400 italic">No actions suggested</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-inner">
                    <i class="fas fa-inbox text-gray-400 dark:text-gray-500 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No Data Found</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                    We couldn't find any <?php echo $reportType === 'sensor' ? 'sensor readings' : 'pest alerts'; ?> for the selected date range.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <button onclick="setDateRange('month')" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Try Last 30 Days
                    </button>
                    <button onclick="document.getElementById('reportForm').reset(); document.getElementById('reportForm').submit();" 
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-redo mr-2"></i>
                        Reset Filters
                    </button>
                </div>
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg max-w-md mx-auto">
                    <p class="text-xs text-blue-800 dark:text-blue-300">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Suggestions:</strong> Try expanding your date range or switching to a different report type to see available data.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pest Details Modal -->
<div id="pestDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pest Alert Details</h3>
                <button onclick="closePestDetails()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="pestDetailsContent" class="px-6 py-4">
                <div class="flex items-center justify-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Options Modal -->
<div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Export Options</h3>
                <button onclick="hideExportOptions()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Export Format</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="exportFormat" value="csv" checked class="mr-2">
                            <span class="text-sm">CSV - Excel compatible with metadata</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="exportFormat" value="pdf" class="mr-2">
                            <span class="text-sm">PDF - Formatted PDF report</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Include Options</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" id="includeMetadata" checked class="mr-2">
                            <span class="text-sm">Include metadata and summary</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" id="includeCharts" class="mr-2">
                            <span class="text-sm">Include charts (PDF only)</span>
                        </label>
                    </div>
                </div>
                <div class="text-xs text-gray-500">
                    <p><strong>Security Note:</strong> Exports are limited to 10,000 records and logged for security purposes.</p>
                </div>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 flex justify-end space-x-3">
                <button onclick="hideExportOptions()" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    Cancel
                </button>
                <button onclick="executeExport()" class="px-4 py-2 text-sm text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Export
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Styles for Enhanced UX -->
<style>
    /* Smooth transitions */
    * {
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }
    
    /* Card hover effects */
    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    /* Loading animation */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    /* Smooth scroll */
    html {
        scroll-behavior: smooth;
    }
    
    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    /* Dark mode scrollbar */
    .dark ::-webkit-scrollbar-track {
        background: #374151;
    }
    
    .dark ::-webkit-scrollbar-thumb {
        background: #6b7280;
    }
    
    .dark ::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }
    
    /* Radio button animation */
    input[type="radio"]:checked + div {
        animation: scaleIn 0.2s ease-out;
    }
    
    @keyframes scaleIn {
        0% { transform: scale(0.95); }
        100% { transform: scale(1); }
    }
    
    /* Table row hover */
    tbody tr {
        transition: background-color 0.15s ease;
    }
    
    /* Button press effect */
    button:active {
        transform: scale(0.98);
    }
</style>

<!-- Chart.js Configuration -->
<script>
    // Toggle export dropdown
    function toggleExportDropdown() {
        const dropdown = document.getElementById('exportDropdown');
        const button = document.getElementById('exportDropdownButton');
        
        if (dropdown.classList.contains('hidden')) {
            dropdown.classList.remove('hidden');
            // Close dropdown when clicking outside
            setTimeout(() => {
                document.addEventListener('click', closeExportDropdownOnClickOutside);
            }, 0);
        } else {
            dropdown.classList.add('hidden');
            document.removeEventListener('click', closeExportDropdownOnClickOutside);
        }
    }
    
    function closeExportDropdownOnClickOutside(event) {
        const dropdown = document.getElementById('exportDropdown');
        const button = document.getElementById('exportDropdownButton');
        
        if (!dropdown.contains(event.target) && !button.contains(event.target)) {
            dropdown.classList.add('hidden');
            document.removeEventListener('click', closeExportDropdownOnClickOutside);
        }
    }

    // Quick date range presets
    function setDateRange(preset) {
        const form = document.getElementById('reportForm');
        const startDate = form.querySelector('[name="start_date"]');
        const endDate = form.querySelector('[name="end_date"]');
        const today = new Date();
        
        endDate.value = today.toISOString().split('T')[0];
        
        switch(preset) {
            case 'today':
                startDate.value = today.toISOString().split('T')[0];
                break;
            case 'week':
                const weekAgo = new Date(today);
                weekAgo.setDate(today.getDate() - 7);
                startDate.value = weekAgo.toISOString().split('T')[0];
                break;
            case 'month':
                const monthAgo = new Date(today);
                monthAgo.setMonth(today.getMonth() - 1);
                startDate.value = monthAgo.toISOString().split('T')[0];
                break;
        }
        
        form.submit();
    }
    
    // Quick guide modal
    function showQuickGuide() {
        const guideContent = `
            <div class="space-y-4">
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                        <span class="text-green-600 dark:text-green-400 font-bold">1</span>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Select Report Type</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Choose between Sensor Data (temperature, humidity, soil) or Pest Detection (AI alerts and detections)</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">2</span>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Choose Date Range</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Select start and end dates, or use quick presets (Today, This Week, This Month)</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                        <span class="text-purple-600 dark:text-purple-400 font-bold">3</span>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Generate & Export</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Click "Generate Report" to view data, then export as CSV or print as PDF</p>
                    </div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <p class="text-xs text-blue-800 dark:text-blue-300">
                        <i class="fas fa-lightbulb mr-1"></i>
                        <strong>Tip:</strong> For pest reports, click the eye icon on any alert to view detailed information including images and suggested actions.
                    </p>
                </div>
            </div>
        `;
        
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full">
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-book-open text-green-600 mr-2"></i>
                        Quick Guide
                    </h3>
                    <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="px-6 py-4">
                    ${guideContent}
                </div>
                <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                    <button onclick="this.closest('.fixed').remove()" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Got it!
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Pest details functionality
    function viewPestDetails(alertId) {
        const modal = document.getElementById('pestDetailsModal');
        const content = document.getElementById('pestDetailsContent');
        
        modal.classList.remove('hidden');
        content.innerHTML = '<div class="flex items-center justify-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i></div>';
        
        // Fetch pest alert details
        fetch(`pest_detection.php?action=get_alert&id=${alertId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.alert) {
                    const alert = data.alert;
                    content.innerHTML = `
                        <div class="space-y-4">
                            ${alert.image_path ? `
                                <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                                    <img src="${alert.image_path}" alt="Pest Detection" class="w-full h-auto">
                                </div>
                            ` : ''}
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Pest Type</label>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">${alert.pest_type}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Detected</label>
                                    <p class="text-sm text-gray-900 dark:text-white">${new Date(alert.detected_at).toLocaleString()}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Location</label>
                                    <p class="text-sm text-gray-900 dark:text-white">${alert.location}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Camera</label>
                                    <p class="text-sm text-gray-900 dark:text-white">${alert.camera_name || 'Manual Entry'}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Severity</label>
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ${getSeverityClass(alert.severity)}">
                                        ${alert.severity.toUpperCase()}
                                    </span>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Confidence</label>
                                    <p class="text-sm text-gray-900 dark:text-white">${alert.confidence_score ? alert.confidence_score + '%' : 'N/A'}</p>
                                </div>
                            </div>
                            
                            ${alert.description ? `
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Description</label>
                                    <p class="text-sm text-gray-900 dark:text-white mt-1">${alert.description}</p>
                                </div>
                            ` : ''}
                            
                            ${alert.suggested_actions ? `
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Suggested Actions</label>
                                    <p class="text-sm text-gray-900 dark:text-white mt-1">${alert.suggested_actions}</p>
                                </div>
                            ` : ''}
                        </div>
                    `;
                } else {
                    content.innerHTML = '<div class="text-center py-8 text-red-600">Failed to load pest details</div>';
                }
            })
            .catch(error => {
                console.error('Error fetching pest details:', error);
                content.innerHTML = '<div class="text-center py-8 text-red-600">Error loading pest details</div>';
            });
    }
    
    function closePestDetails() {
        document.getElementById('pestDetailsModal').classList.add('hidden');
    }
    
    function getSeverityClass(severity) {
        const classes = {
            'low': 'bg-green-100 text-green-800',
            'medium': 'bg-yellow-100 text-yellow-800',
            'high': 'bg-orange-100 text-orange-800',
            'critical': 'bg-red-100 text-red-800'
        };
        return classes[severity] || 'bg-gray-100 text-gray-800';
    }

    // Export functionality
    function showExportOptions() {
        document.getElementById('exportModal').classList.remove('hidden');
    }

    function hideExportOptions() {
        document.getElementById('exportModal').classList.add('hidden');
    }

    function executeExport() {
        const format = document.querySelector('input[name="exportFormat"]:checked').value;
        const includeMetadata = document.getElementById('includeMetadata').checked;
        const includeCharts = document.getElementById('includeCharts').checked;

        // Build export URL with options
        const params = new URLSearchParams(window.location.search);
        params.set('export', format);
        if (!includeMetadata) params.set('no_metadata', '1');
        if (includeCharts && format === 'pdf') params.set('include_charts', '1');

        // Show loading indicator
        const exportButton = event.target;
        const originalText = exportButton.innerHTML;
        exportButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Exporting...';
        exportButton.disabled = true;

        // Navigate to export URL
        window.location.href = '?' + params.toString();

        // Hide modal
        hideExportOptions();

        // Reset button after delay (in case export fails)
        setTimeout(() => {
            exportButton.innerHTML = originalText;
            exportButton.disabled = false;
        }, 3000);
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideExportOptions();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded properly');
            return;
        }

        const chartData = <?php echo json_encode($chartData); ?>;
        const reportType = '<?php echo $reportType; ?>';

        if (chartData && chartData.length > 0) {
            const canvas = document.getElementById('reportChart');
            if (!canvas) {
                console.error('Chart canvas not found');
                return;
            }
            const ctx = canvas.getContext('2d');

            if (reportType === 'sensor') {
                // Group data by sensor type
                const groupedData = {};
                chartData.forEach(item => {
                    if (!groupedData[item.sensor_type]) {
                        groupedData[item.sensor_type] = {
                            labels: [],
                            data: [],
                            unit: item.unit
                        };
                    }
                    groupedData[item.sensor_type].labels.push(item.date);
                    groupedData[item.sensor_type].data.push(parseFloat(item.avg_value));
                });

                // Create datasets for each sensor type
                const datasets = Object.keys(groupedData).map((type, index) => {
                    const colors = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B'];
                    return {
                        label: type.replace('_', ' ').toUpperCase(),
                        data: groupedData[type].data,
                        borderColor: colors[index % colors.length],
                        backgroundColor: colors[index % colors.length] + '20',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.1
                    };
                });

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [...new Set(chartData.map(item => item.date))].sort(),
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                ticks: {
                                    font: {
                                        size: 10
                                    }
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        }
                    }
                });
            } else {
                // Pest alert chart - stacked bar chart by severity
                const groupedData = {};
                chartData.forEach(item => {
                    if (!groupedData[item.date]) {
                        groupedData[item.date] = {
                            low: 0,
                            medium: 0,
                            high: 0,
                            critical: 0
                        };
                    }
                    groupedData[item.date][item.severity] = parseInt(item.count);
                });

                const labels = Object.keys(groupedData).sort();
                const severities = ['low', 'medium', 'high', 'critical'];
                const colors = {
                    low: '#10B981',
                    medium: '#F59E0B',
                    high: '#F97316',
                    critical: '#EF4444'
                };

                const datasets = severities.map(severity => ({
                    label: severity.toUpperCase(),
                    data: labels.map(date => groupedData[date][severity] || 0),
                    backgroundColor: colors[severity],
                    borderColor: colors[severity],
                    borderWidth: 1
                }));

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                ticks: {
                                    font: {
                                        size: 10
                                    }
                                }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    });
</script>

<?php
// Include shared footer
include 'includes/footer.php';
?>
</main>
</div>
</div>