<?php
/**
 * Sensor Readings Log - Detailed Reading History
 * 
 * Shows individual sensor readings with timestamps (not aggregated)
 */

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

// Handle filters
$sensorFilter = $_GET['sensor_id'] ?? 'all';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$limit = $_GET['limit'] ?? 100;

// Validate limit
$limit = min(max(10, (int)$limit), 1000); // Between 10 and 1000

/**
 * Get detailed sensor readings log
 */
function getSensorReadingsLog($sensorFilter, $startDate, $endDate, $limit)
{
    try {
        $pdo = getDatabaseConnection();
        
        $sql = "
            SELECT 
                sr.id,
                sr.sensor_id,
                s.sensor_name,
                s.sensor_type,
                s.location,
                sr.value,
                sr.unit,
                sr.recorded_at,
                s.alert_threshold_min,
                s.alert_threshold_max
            FROM sensor_readings sr
            JOIN sensors s ON sr.sensor_id = s.id
            WHERE DATE(sr.recorded_at) BETWEEN ? AND ?
        ";
        
        $params = [$startDate, $endDate];
        
        if ($sensorFilter !== 'all') {
            $sql .= " AND sr.sensor_id = ?";
            $params[] = $sensorFilter;
        }
        
        $sql .= " ORDER BY sr.recorded_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get sensor readings log: " . $e->getMessage());
        return [];
    }
}

/**
 * Get available sensors for filter
 */
function getAvailableSensors()
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("SELECT id, sensor_name, sensor_type FROM sensors ORDER BY sensor_name");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get sensors: " . $e->getMessage());
        return [];
    }
}

/**
 * Get reading statistics
 */
function getReadingStatistics($sensorFilter, $startDate, $endDate)
{
    try {
        $pdo = getDatabaseConnection();
        
        $sql = "
            SELECT 
                COUNT(*) as total_readings,
                COUNT(DISTINCT sr.sensor_id) as unique_sensors,
                COUNT(DISTINCT DATE(sr.recorded_at)) as unique_days,
                MIN(sr.recorded_at) as first_reading,
                MAX(sr.recorded_at) as last_reading
            FROM sensor_readings sr
            WHERE DATE(sr.recorded_at) BETWEEN ? AND ?
        ";
        
        $params = [$startDate, $endDate];
        
        if ($sensorFilter !== 'all') {
            $sql .= " AND sr.sensor_id = ?";
            $params[] = $sensorFilter;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Failed to get statistics: " . $e->getMessage());
        return null;
    }
}

// Get data
$readingsLog = getSensorReadingsLog($sensorFilter, $startDate, $endDate, $limit);
$sensors = getAvailableSensors();
$statistics = getReadingStatistics($sensorFilter, $startDate, $endDate);

// Set page title
$pageTitle = 'Sensor Readings Log - IoT Farm Monitoring System';

// Include shared header
include 'includes/header.php';
include 'includes/navigation.php';
?>

<!-- Sensor Readings Log Content -->
<div class="p-4 max-w-7xl mx-auto">

    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-list-alt text-blue-600 mr-3"></i>
                    Sensor Readings Log
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Detailed history of individual sensor readings with timestamps
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="reports.php" class="px-3 py-2 text-sm bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-lg hover:bg-green-200 dark:hover:bg-green-800 transition-colors">
                    <i class="fas fa-chart-bar mr-1"></i>
                    View Reports
                </a>
            </div>
        </div>
        
        <!-- Breadcrumb -->
        <nav class="flex text-xs text-gray-500 dark:text-gray-400">
            <a href="dashboard.php" class="hover:text-green-600 dark:hover:text-green-400">Dashboard</a>
            <span class="mx-2">/</span>
            <a href="sensors.php" class="hover:text-green-600 dark:hover:text-green-400">Sensors</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 dark:text-white">Readings Log</span>
        </nav>
    </div>

    <!-- Statistics Cards -->
    <?php if ($statistics): ?>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-database text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Readings</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo number_format($statistics['total_readings']); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-thermometer-half text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sensors</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $statistics['unique_sensors']; ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-calendar text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Days</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $statistics['unique_days']; ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-clock text-orange-600 dark:text-orange-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">First Reading</p>
                    <p class="text-xs font-semibold text-gray-900 dark:text-white"><?php echo date('M j, g:i A', strtotime($statistics['first_reading'])); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-clock text-red-600 dark:text-red-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Last Reading</p>
                    <p class="text-xs font-semibold text-gray-900 dark:text-white"><?php echo date('M j, g:i A', strtotime($statistics['last_reading'])); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filters Card -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-filter text-green-600 mr-2"></i>
                Filter Readings
            </h2>
        </div>
        
        <div class="p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-thermometer-half text-gray-400 mr-1"></i>
                        Sensor
                    </label>
                    <select name="sensor_id" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="all" <?php echo $sensorFilter === 'all' ? 'selected' : ''; ?>>All Sensors</option>
                        <?php foreach ($sensors as $sensor): ?>
                            <option value="<?php echo $sensor['id']; ?>" <?php echo $sensorFilter == $sensor['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sensor['sensor_name']); ?> (<?php echo ucfirst($sensor['sensor_type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar-alt text-gray-400 mr-1"></i>
                        Start Date
                    </label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar-alt text-gray-400 mr-1"></i>
                        End Date
                    </label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-list-ol text-gray-400 mr-1"></i>
                        Limit
                    </label>
                    <select name="limit" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 readings</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 readings</option>
                        <option value="250" <?php echo $limit == 250 ? 'selected' : ''; ?>>250 readings</option>
                        <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500 readings</option>
                        <option value="1000" <?php echo $limit == 1000 ? 'selected' : ''; ?>>1000 readings</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        &nbsp;
                    </label>
                    <button type="submit" class="w-full bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                        <i class="fas fa-search mr-2"></i>
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Readings Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-table text-blue-600 mr-2"></i>
                    Individual Readings
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Showing <?php echo count($readingsLog); ?> of <?php echo number_format($statistics['total_readings'] ?? 0); ?> total readings
                </p>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                <i class="fas fa-info-circle mr-1"></i>
                Most recent first
            </div>
        </div>

        <?php if (!empty($readingsLog)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Date & Time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Sensor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Value</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($readingsLog as $reading): ?>
                            <?php
                            // Determine status based on thresholds
                            $status = 'normal';
                            $statusColor = 'green';
                            $statusIcon = 'check-circle';
                            
                            if ($reading['alert_threshold_min'] && $reading['alert_threshold_max']) {
                                $min = $reading['alert_threshold_min'];
                                $max = $reading['alert_threshold_max'];
                                $value = $reading['value'];
                                
                                if ($value < $min) {
                                    $status = 'low';
                                    $statusColor = 'blue';
                                    $statusIcon = 'arrow-down';
                                } elseif ($value > $max) {
                                    $status = 'high';
                                    $statusColor = 'red';
                                    $statusIcon = 'arrow-up';
                                }
                            }
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">#<?php echo $reading['id']; ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                    <div class="font-medium"><?php echo date('M j, Y', strtotime($reading['recorded_at'])); ?></div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400"><?php echo date('g:i:s A', strtotime($reading['recorded_at'])); ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    <?php echo htmlspecialchars($reading['sensor_name']); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 rounded-full">
                                        <?php echo ucfirst(str_replace('_', ' ', $reading['sensor_type'])); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    <?php echo htmlspecialchars($reading['location']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-gray-100">
                                    <?php echo number_format($reading['value'], 2); ?><?php echo htmlspecialchars($reading['unit']); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-<?php echo $statusColor; ?>-100 dark:bg-<?php echo $statusColor; ?>-900/50 text-<?php echo $statusColor; ?>-800 dark:text-<?php echo $statusColor; ?>-200 rounded-full">
                                        <i class="fas fa-<?php echo $statusIcon; ?> mr-1"></i>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-inbox text-gray-400 dark:text-gray-500 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No Readings Found</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                    No sensor readings found for the selected filters.
                </p>
                <button onclick="window.location.href='sensor_readings_log.php'" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                    <i class="fas fa-redo mr-2"></i>
                    Reset Filters
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Info Box -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-1 mr-3"></i>
            <div class="text-sm text-blue-800 dark:text-blue-300">
                <p class="font-semibold mb-1">About This Log</p>
                <p>This page shows <strong>individual sensor readings</strong> with exact timestamps and advanced filtering options. For a quick view of recent readings organized by sensor type, visit the <a href="sensors.php" class="underline font-semibold">Sensors page</a>. For aggregated daily summaries with trend analysis, visit the <a href="reports.php" class="underline font-semibold">Reports page</a>.</p>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
</main>
</div>
</div>
