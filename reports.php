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
            // Redirect to clean CSV export to avoid header issues
            $params = [
                'export' => 'csv',
                'report_type' => $reportType,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
            
            // Pass through inline parameter if present
            if (isset($_GET['inline'])) {
                $params['inline'] = $_GET['inline'];
            }
            header('Location: clean_csv_export.php?' . http_build_query($params));
            exit();
        } elseif ($format === 'pdf') {
            $exportHandler->exportToPDF($currentUser['id'], $currentUser['role'], $reportType, $startDate, $endDate);
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
 * Get pest data report for date range
 */
function getPestReport($startDate, $endDate)
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT 
                pest_type,
                location,
                severity,
                status,
                description,
                detected_at,
                DATE(detected_at) as date,
                COUNT(*) OVER (PARTITION BY pest_type) as type_count,
                COUNT(*) OVER (PARTITION BY severity) as severity_count
            FROM pest_alerts
            WHERE DATE(detected_at) BETWEEN ? AND ?
            ORDER BY detected_at DESC
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

        // Pest statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_alerts,
                COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_alerts,
                COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_alerts,
                COUNT(DISTINCT pest_type) as unique_pests
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

    <!-- Error/Success Messages -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="animate-slide-up">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                    <span class="text-sm text-red-800"><?php echo htmlspecialchars($_SESSION['error_message']); ?></span>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="animate-slide-up">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                    <span class="text-sm text-green-800"><?php echo htmlspecialchars($_SESSION['success_message']); ?></span>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- Report Controls -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Report Type</label>
                <select name="report_type" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    <option value="sensor" <?php echo $reportType === 'sensor' ? 'selected' : ''; ?>>Sensor Data</option>
                    <option value="pest" <?php echo $reportType === 'pest' ? 'selected' : ''; ?>>Pest Alerts</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>"
                    class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>"
                    class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="flex-1 bg-green-600 text-white text-sm font-medium px-3 py-2 rounded-md hover:bg-green-700 transition-colors">
                    <i class="fas fa-search mr-1"></i>
                </button>
                
            </div>
            <div class="flex space-x-2">
            <div class="flex space-x-1">
                    <div class="relative group">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>"
                            class="bg-blue-600 text-white text-sm px-3 py-2 rounded-md hover:bg-blue-700 transition-colors flex items-center"
                            title="Download CSV file - Excel compatible format with metadata">
                            <i class="fas fa-file-csv mr-1"></i>
                            <span class="hidden sm:inline">CSV</span>
                        </a>
                        <!-- Dropdown for CSV options -->
                        <div class="absolute right-0 mt-1 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-600 hidden group-hover:block z-10">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>"
                               class="block px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-download mr-1"></i> Download CSV
                            </a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv', 'inline' => '1'])); ?>"
                               class="block px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                               target="_blank">
                                <i class="fas fa-eye mr-1"></i> View CSV (Copy/Paste)
                            </a>
                        </div>
                    </div>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>"
                        class="bg-red-600 text-white text-sm px-3 py-2 rounded-md hover:bg-red-700 transition-colors flex items-center"
                        title="Print-friendly Report - Opens in new window, use browser's Print to PDF"
                        target="_blank">
                        <i class="fas fa-print mr-1"></i>
                        <span class="hidden sm:inline">Print</span>
                    </a>
                    <button type="button" onclick="showExportOptions()"
                        class="bg-gray-600 text-white text-sm px-3 py-2 rounded-md hover:bg-gray-700 transition-colors"
                        title="Advanced export options">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>

            </div>
        </form>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <!-- Left Column - Charts and Data -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Summary Statistics -->
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
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Resolved</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $summary['pest_stats']['resolved_alerts']; ?></p>
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
                <?php endif; ?>
            </div>

            <!-- Data Visualization -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
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

        <!-- Right Column - Report Insights -->
        <div class="space-y-4">
            <!-- Report Summary -->
            <div class="bg-purple-600 text-white rounded-xl p-4">
                <h3 class="text-white/80 text-xs font-medium mb-3">Report Summary</h3>
                <div class="space-y-3">
                    <div class="text-center">
                        <div class="text-2xl font-bold mb-1">
                            <?php echo $reportType === 'sensor' ? number_format($summary['sensor_stats']['total_readings']) : $summary['pest_stats']['total_alerts']; ?>
                        </div>
                        <div class="text-white/80 text-xs mb-3"><?php echo $reportType === 'sensor' ? 'Total Readings' : 'Total Alerts'; ?></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="text-center p-2 bg-white/10 rounded">
                            <div class="font-bold">
                                <?php echo $reportType === 'sensor' ? $summary['sensor_stats']['total_sensors'] : $summary['pest_stats']['critical_alerts']; ?>
                            </div>
                            <div class="text-white/80"><?php echo $reportType === 'sensor' ? 'Sensors' : 'Critical'; ?></div>
                        </div>
                        <div class="text-center p-2 bg-white/10 rounded">
                            <div class="font-bold">
                                <?php echo $reportType === 'sensor' ? $summary['sensor_stats']['active_days'] : $summary['pest_stats']['resolved_alerts']; ?>
                            </div>
                            <div class="text-white/80"><?php echo $reportType === 'sensor' ? 'Days' : 'Resolved'; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Insights -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Insights</h3>
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

    <!-- Data Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-table text-green-600 mr-2"></i>
                <?php echo $reportType === 'sensor' ? 'Sensor Data Report' : 'Pest Alert Report'; ?>
            </h3>
        </div>

        <?php if (!empty($reportData)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                                <?php if ($reportType === 'sensor'): ?>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sensor</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Avg Value</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Range</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Readings</th>
                                <?php else: ?>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pest Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Severity</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($reportData as $row): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <?php if ($reportType === 'sensor'): ?>
                                        <td class="px-4 py-2 text-xs text-gray-900"><?php echo date('M j', strtotime($row['date'])); ?></td>
                                        <td class="px-4 py-2 text-xs font-medium text-gray-900"><?php echo htmlspecialchars($row['sensor_name']); ?></td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                                <?php echo ucfirst(str_replace('_', ' ', $row['sensor_type'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-600"><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td class="px-4 py-2 text-xs font-medium text-gray-900">
                                            <?php echo number_format($row['avg_value'], 1) . $row['unit']; ?>
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-600">
                                            <?php echo number_format($row['min_value'], 1) . ' - ' . number_format($row['max_value'], 1) . $row['unit']; ?>
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-600"><?php echo $row['reading_count']; ?></td>
                                    <?php else: ?>
                                        <td class="px-4 py-2 text-xs text-gray-900"><?php echo date('M j', strtotime($row['date'])); ?></td>
                                        <td class="px-4 py-2 text-xs font-medium text-gray-900"><?php echo htmlspecialchars($row['pest_type']); ?></td>
                                        <td class="px-4 py-2 text-xs text-gray-600"><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td class="px-4 py-2">
                                            <?php
                                            $severityColors = [
                                                'low' => 'bg-green-100 text-green-800',
                                                'medium' => 'bg-yellow-100 text-yellow-800',
                                                'high' => 'bg-orange-100 text-orange-800',
                                                'critical' => 'bg-red-100 text-red-800'
                                            ];
                                            $colorClass = $severityColors[$row['severity']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-medium <?php echo $colorClass; ?> rounded-full">
                                                <?php echo ucfirst($row['severity']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <?php
                                            $statusColors = [
                                                'new' => 'bg-red-100 text-red-800',
                                                'acknowledged' => 'bg-yellow-100 text-yellow-800',
                                                'resolved' => 'bg-green-100 text-green-800'
                                            ];
                                            $statusColorClass = $statusColors[$row['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-medium <?php echo $statusColorClass; ?> rounded-full">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars($row['description']); ?>">
                                            <?php echo htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : ''); ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
        <?php else: ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-table text-gray-400 dark:text-gray-500 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No data available</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">No data available for the selected criteria</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Try adjusting the date range or report type</p>
            </div>
        <?php endif; ?>
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

<!-- Chart.js Configuration -->
<script>
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