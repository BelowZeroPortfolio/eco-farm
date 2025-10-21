<?php

/**
 * Sensors Management Page for IoT Farm Monitoring System
 * 
 * Displays sensor overview table with status indicators and Chart.js visualizations
 */

// Start session
session_start();

// Simple authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/language.php';
require_once 'includes/arduino-api.php';

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Get all sensors with their latest readings
function getAllSensorsWithReadings()
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("
            SELECT 
                s.id,
                s.sensor_name,
                s.sensor_type,
                s.location,
                s.status,
                s.created_at,
                sr.value as latest_value,
                sr.unit,
                sr.recorded_at as last_reading
            FROM sensors s
            LEFT JOIN (
                SELECT 
                    sensor_id,
                    value,
                    unit,
                    recorded_at,
                    ROW_NUMBER() OVER (PARTITION BY sensor_id ORDER BY recorded_at DESC) as rn
                FROM sensor_readings
            ) sr ON s.id = sr.sensor_id AND sr.rn = 1
            ORDER BY s.sensor_type, s.sensor_name
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get sensors with readings: " . $e->getMessage());
        return [];
    }
}

// Get sensor readings for charts (last 24 hours)
function getSensorChartData($sensorType = null)
{
    try {
        $pdo = getDatabaseConnection();

        $whereClause = $sensorType ? "AND s.sensor_type = ?" : "";
        $params = $sensorType ? [$sensorType] : [];

        $stmt = $pdo->prepare("
            SELECT 
                s.sensor_name,
                s.sensor_type,
                sr.value,
                sr.unit,
                sr.recorded_at
            FROM sensors s
            JOIN sensor_readings sr ON s.id = sr.sensor_id
            WHERE sr.recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            $whereClause
            ORDER BY sr.recorded_at ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get sensor chart data: " . $e->getMessage());
        return [];
    }
}

// Get sensor statistics
function getSensorStatistics()
{
    try {
        $pdo = getDatabaseConnection();

        // Get sensor counts by type and status
        $stmt = $pdo->query("
            SELECT 
                sensor_type,
                status,
                COUNT(*) as count
            FROM sensors
            GROUP BY sensor_type, status
        ");
        $statusCounts = $stmt->fetchAll();

        // Get latest readings summary
        $stmt = $pdo->query("
            SELECT 
                s.sensor_type,
                AVG(sr.value) as avg_value,
                MIN(sr.value) as min_value,
                MAX(sr.value) as max_value,
                sr.unit
            FROM sensors s
            JOIN sensor_readings sr ON s.id = sr.sensor_id
            WHERE sr.recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY s.sensor_type, sr.unit
        ");
        $readingSummary = $stmt->fetchAll();

        return [
            'status_counts' => $statusCounts,
            'reading_summary' => $readingSummary
        ];
    } catch (Exception $e) {
        error_log("Failed to get sensor statistics: " . $e->getMessage());
        return [
            'status_counts' => [],
            'reading_summary' => []
        ];
    }
}

// Initialize Arduino bridge
$arduino = new ArduinoBridge();

// Get real-time Arduino data
$arduinoData = null;
$arduinoHealthy = false;
if ($arduino->isHealthy()) {
    $arduinoHealthy = true;
    $arduinoData = $arduino->getAllSensorData();
    
    // Auto-sync Arduino data to database if available
    if ($arduinoData) {
        $arduino->syncToDatabase();
    }
}

// Get data for the page
$sensors = getAllSensorsWithReadings();
$chartData = getSensorChartData();
$statistics = getSensorStatistics();

// Organize chart data by sensor type
$chartDataByType = [];
foreach ($chartData as $reading) {
    $type = $reading['sensor_type'];
    if (!isset($chartDataByType[$type])) {
        $chartDataByType[$type] = [];
    }
    $chartDataByType[$type][] = $reading;
}

// Set page title for header component
$pageTitle = 'Sensors - IoT Farm Monitoring System';

// Include shared header
include 'includes/header.php';

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
<?php
// Include shared navigation component (sidebar)
include 'includes/navigation.php';
?>

<!-- Sensors Management Content -->
<div class="p-4 max-w-7xl mx-auto">

    <!-- Sensor Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <?php
        $sensorTypeStats = [];
        foreach ($statistics['status_counts'] as $stat) {
            if (!isset($sensorTypeStats[$stat['sensor_type']])) {
                $sensorTypeStats[$stat['sensor_type']] = ['online' => 0, 'offline' => 0];
            }
            $sensorTypeStats[$stat['sensor_type']][$stat['status']] = $stat['count'];
        }

        $sensorIcons = [
            'temperature' => [
                'icon' => 'fa-thermometer-half',
                'color' => 'red',
                'name' => 'Temperature Sensors',
                'description' => 'Monitor ambient and soil temperature',
                'optimal_range' => '20-28°C',
                'current_status' => 'Optimal'
            ],
            'humidity' => [
                'icon' => 'fa-tint',
                'color' => 'blue',
                'name' => 'Humidity Sensors',
                'description' => 'Track air moisture levels',
                'optimal_range' => '60-80%',
                'current_status' => 'Good'
            ],
            'soil_moisture' => [
                'icon' => 'fa-seedling',
                'color' => 'green',
                'name' => 'Soil Moisture Sensors',
                'description' => 'Monitor soil water content',
                'optimal_range' => '40-60%',
                'current_status' => 'Needs Attention'
            ]
        ];

        foreach ($sensorIcons as $type => $config):
            $online = $sensorTypeStats[$type]['online'] ?? 0;
            $offline = $sensorTypeStats[$type]['offline'] ?? 0;
            $total = $online + $offline;

            // Get current reading for this type
            $currentReading = null;
            foreach ($statistics['reading_summary'] as $reading) {
                if ($reading['sensor_type'] === $type) {
                    $currentReading = $reading;
                    break;
                }
            }
        ?>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-<?php echo $config['color']; ?>-100 dark:bg-<?php echo $config['color']; ?>-900 rounded-lg flex items-center justify-center">
                            <i class="fas <?php echo $config['icon']; ?> text-<?php echo $config['color']; ?>-600 dark:text-<?php echo $config['color']; ?>-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo $config['name']; ?></h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400"><?php echo $config['description']; ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-xs text-gray-600 dark:text-gray-400"><?php echo $online; ?> online</span>
                        </div>
                        <?php if ($offline > 0): ?>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                <span class="text-xs text-gray-600 dark:text-gray-400"><?php echo $offline; ?> offline</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Current Average</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            <?php echo $currentReading ? number_format($currentReading['avg_value'], 1) . $currentReading['unit'] : '--'; ?>
                        </p>
                        <?php if ($currentReading): ?>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Range: <?php echo number_format($currentReading['min_value'], 1) . '-' . number_format($currentReading['max_value'], 1) . $currentReading['unit']; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Optimal Range</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white"><?php echo $config['optimal_range']; ?></p>
                        <p class="text-xs text-<?php echo $config['color']; ?>-600 dark:text-<?php echo $config['color']; ?>-400 font-medium">
                            <?php echo $config['current_status']; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Arduino Live Sensors -->
    <?php if ($arduinoHealthy && $arduinoData): ?>
    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900/20 dark:to-gray-800/20 border border-gray-200 dark:border-gray-800 rounded-xl p-6 mb-4">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-microchip text-gray-600 dark:text-gray-400 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Arduino Live Sensors</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">DHT22 + Soil Moisture from pins 2 & A10</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-sm text-green-600 dark:text-green-400 font-medium">Live</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Temperature -->
            <?php if (isset($arduinoData['temperature'])): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-thermometer-half text-red-600 dark:text-red-400"></i>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">Temperature</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">DHT22 Sensor</p>
                    </div>
                </div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400 mb-1" id="arduino-temperature">
                    <?php echo number_format($arduinoData['temperature']['value'], 1); ?>°C
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400" id="arduino-temp-time">
                    <?php echo $arduinoData['temperature']['timestamp'] ?? 'Unknown'; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Humidity -->
            <?php if (isset($arduinoData['humidity'])): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-tint text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">Humidity</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">DHT22 Sensor</p>
                    </div>
                </div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-1" id="arduino-humidity">
                    <?php echo number_format($arduinoData['humidity']['value'], 1); ?>%
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400" id="arduino-hum-time">
                    <?php echo $arduinoData['humidity']['timestamp'] ?? 'Unknown'; ?>
                </div>
                
                <!-- Humidity Status -->
                <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600 dark:text-gray-400">Status:</span>
                        <span class="font-medium <?php 
                            $value = $arduinoData['humidity']['value'];
                            if ($value >= 60 && $value <= 80) {
                                echo 'text-green-600 dark:text-green-400';
                                $status = 'Optimal';
                            } elseif ($value >= 50 && $value < 90) {
                                echo 'text-yellow-600 dark:text-yellow-400';
                                $status = 'Acceptable';
                            } else {
                                echo 'text-red-600 dark:text-red-400';
                                $status = 'Needs Attention';
                            }
                            echo '" id="arduino-hum-status">' . $status;
                        ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Soil Moisture -->
            <?php if (isset($arduinoData['soil_moisture'])): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-seedling text-green-600 dark:text-green-400"></i>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">Soil Moisture</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Analog A10</p>
                    </div>
                </div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400 mb-1" id="arduino-soil">
                    <?php echo number_format($arduinoData['soil_moisture']['value'], 1); ?>%
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400" id="arduino-soil-time">
                    <?php echo $arduinoData['soil_moisture']['timestamp'] ?? 'Unknown'; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-4 flex items-center justify-between">
            <button onclick="refreshArduinoData()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-900 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>
                Refresh All
            </button>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Live updates: 5s
            </div>
        </div>
    </div>
    <?php elseif (!$arduinoHealthy): ?>
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 mb-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Arduino Bridge Offline</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Start the bridge service to see live humidity data. 
                    <a href="#" onclick="showArduinoInstructions()" class="text-yellow-600 dark:text-yellow-400 hover:underline">Setup instructions</a>
                </p>
            </div>
        </div>
    </div>
    <?php elseif (!$arduinoHealthy): ?>
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 mb-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Arduino Bridge Offline</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Arduino bridge service is not running. 
                    <a href="#" onclick="showArduinoInstructions()" class="text-yellow-600 dark:text-yellow-400 hover:underline">Click here for setup instructions</a>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <!-- Left Column - Charts -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Sensor Data Charts -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-chart-line text-green-600 mr-2"></i>
                        Sensor Data Visualization
                    </h3>
                    <select id="sensor-type-select" class="bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-xs border border-gray-300 dark:border-gray-600 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="temperature">Temperature</option>
                        <option value="humidity">Humidity</option>
                        <option value="soil_moisture">Soil Moisture</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <?php foreach ($sensorIcons as $type => $config): ?>
                        <?php if (isset($chartDataByType[$type]) && !empty($chartDataByType[$type])): ?>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h4 class="text-xs font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                    <i class="fas <?php echo $config['icon']; ?> text-<?php echo $config['color']; ?>-600 dark:text-<?php echo $config['color']; ?>-400 mr-2"></i>
                                    <?php echo $config['name']; ?>
                                </h4>
                                <div class="chart-container" style="height: 200px;">
                                    <canvas id="chart-<?php echo $type; ?>"></canvas>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sensor Alerts & Recommendations -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                    Smart Insights & Recommendations
                </h3>
                <div class="space-y-3">
                    <div class="flex items-start gap-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-seedling text-yellow-600 dark:text-yellow-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Irrigation Needed</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Soil moisture in Zone A dropped to 35%. Optimal range is 40-60% for current crops.</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-xs text-yellow-600 dark:text-yellow-400 font-medium">Priority: Medium</span>
                                <span class="text-xs text-gray-500">•</span>
                                <span class="text-xs text-gray-500">Est. 2-3 hours irrigation needed</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-thermometer-half text-blue-600 dark:text-blue-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Temperature Optimal</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">All zones maintaining 22-26°C. Perfect conditions for vegetative growth phase.</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-xs text-green-600 dark:text-green-400 font-medium">Status: Excellent</span>
                                <span class="text-xs text-gray-500">•</span>
                                <span class="text-xs text-gray-500">No action required</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="w-8 h-8 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-tint text-red-600 dark:text-red-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">High Humidity Alert</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Greenhouse humidity at 85%. Risk of fungal diseases above 80%.</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-xs text-red-600 dark:text-red-400 font-medium">Priority: High</span>
                                <span class="text-xs text-gray-500">•</span>
                                <span class="text-xs text-gray-500">Increase ventilation</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                        <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-brain text-purple-600 dark:text-purple-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">AI Prediction</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Based on weather forecast, soil moisture will drop 15% in next 48 hours.</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-xs text-purple-600 dark:text-purple-400 font-medium">Forecast</span>
                                <span class="text-xs text-gray-500">•</span>
                                <span class="text-xs text-gray-500">Schedule irrigation for tomorrow</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Additional Info -->
        <div class="space-y-4">
            <!-- Current Conditions -->
            <div class="bg-green-600 text-white rounded-xl p-4">
                <h3 class="text-white/80 text-xs font-medium mb-3">Live Farm Conditions</h3>
                <div class="space-y-3">
                    <div class="text-center">
                        <div class="text-2xl font-bold mb-1">
                            <?php
                            $tempReading = null;
                            foreach ($statistics['reading_summary'] as $reading) {
                                if ($reading['sensor_type'] === 'temperature') {
                                    $tempReading = $reading;
                                    break;
                                }
                            }
                            echo $tempReading ? number_format($tempReading['avg_value'], 1) . '°C' : '24.5°C';
                            ?>
                        </div>
                        <div class="text-white/80 text-xs mb-3">Average Temperature</div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="text-center p-2 bg-white/10 rounded">
                            <div class="font-bold">
                                <?php
                                $humidityReading = null;
                                foreach ($statistics['reading_summary'] as $reading) {
                                    if ($reading['sensor_type'] === 'humidity') {
                                        $humidityReading = $reading;
                                        break;
                                    }
                                }
                                echo $humidityReading ? number_format($humidityReading['avg_value'], 0) . '%' : '68%';
                                ?>
                            </div>
                            <div class="text-white/80">Humidity</div>
                        </div>
                        <div class="text-center p-2 bg-white/10 rounded">
                            <div class="font-bold">
                                <?php
                                $soilReading = null;
                                foreach ($statistics['reading_summary'] as $reading) {
                                    if ($reading['sensor_type'] === 'soil_moisture') {
                                        $soilReading = $reading;
                                        break;
                                    }
                                }
                                echo $soilReading ? number_format($soilReading['avg_value'], 0) . '%' : '46%';
                                ?>
                            </div>
                            <div class="text-white/80">Soil Moisture</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sensor Performance Metrics -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Performance Metrics</h3>
                <div class="space-y-4">
                    <?php foreach ($sensorIcons as $type => $config):
                        $online = $sensorTypeStats[$type]['online'] ?? 0;
                        $total = ($sensorTypeStats[$type]['online'] ?? 0) + ($sensorTypeStats[$type]['offline'] ?? 0);
                        $percentage = $total > 0 ? ($online / $total) * 100 : 0;

                        // Calculate uptime and data quality metrics
                        $uptime = $percentage;
                        $dataQuality = rand(85, 99); // Simulated data quality score
                        $lastMaintenance = rand(15, 45); // Days since last maintenance
                    ?>
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <i class="fas <?php echo $config['icon']; ?> text-<?php echo $config['color']; ?>-600 dark:text-<?php echo $config['color']; ?>-400 text-sm"></i>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white"><?php echo ucfirst(str_replace('_', ' ', $type)); ?></span>
                                </div>
                                <span class="text-xs text-gray-600 dark:text-gray-400"><?php echo $online; ?>/<?php echo $total; ?> online</span>
                            </div>

                            <!-- Uptime -->
                            <div class="mb-2">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Uptime</span>
                                    <span class="text-gray-900 dark:text-white font-medium"><?php echo number_format($uptime, 1); ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="bg-<?php echo $config['color']; ?>-600 h-1.5 rounded-full" style="width: <?php echo $uptime; ?>%"></div>
                                </div>
                            </div>

                            <!-- Data Quality -->
                            <div class="mb-2">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Data Quality</span>
                                    <span class="text-gray-900 dark:text-white font-medium"><?php echo $dataQuality; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="bg-green-600 h-1.5 rounded-full" style="width: <?php echo $dataQuality; ?>%"></div>
                                </div>
                            </div>

                            <!-- Last Maintenance -->
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-600 dark:text-gray-400">Last Maintenance</span>
                                <span class="text-gray-900 dark:text-white"><?php echo $lastMaintenance; ?> days ago</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Environmental Context -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                    <i class="fas fa-leaf text-green-600 mr-2"></i>
                    Growing Conditions
                </h3>
                <div class="space-y-3">
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-thermometer-half text-red-600 dark:text-red-400 text-xs"></i>
                            <span class="text-xs font-medium text-gray-900 dark:text-white">Temperature</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Optimal for most crops: 20-28°C</p>
                        <p class="text-xs text-green-600 dark:text-green-400">✓ Current conditions ideal for growth</p>
                    </div>

                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-tint text-blue-600 dark:text-blue-400 text-xs"></i>
                            <span class="text-xs font-medium text-gray-900 dark:text-white">Humidity</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Ideal range: 60-80% RH</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">→ Monitor for disease prevention</p>
                    </div>

                    <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-seedling text-green-600 dark:text-green-400 text-xs"></i>
                            <span class="text-xs font-medium text-gray-900 dark:text-white">Soil Moisture</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Target: 40-60% for most vegetables</p>
                        <p class="text-xs text-yellow-600 dark:text-yellow-400">⚠ Zone A needs attention</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sensor Overview Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-table text-green-600 mr-2"></i>
                Detailed Sensor Overview
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Sensor
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Location
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Latest Reading
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Last Updated
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($sensors as $sensor):
                        $statusColor = $sensor['status'] === 'online' ? 'green' : 'red';
                        $typeIcon = $sensorIcons[$sensor['sensor_type']]['icon'] ?? 'fa-sensor';
                        $typeColor = $sensorIcons[$sensor['sensor_type']]['color'] ?? 'gray';
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-<?php echo $typeColor; ?>-100 dark:bg-<?php echo $typeColor; ?>-900 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                        <i class="fas <?php echo $typeIcon; ?> text-<?php echo $typeColor; ?>-600 dark:text-<?php echo $typeColor; ?>-400 text-sm"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            <?php echo htmlspecialchars($sensor['sensor_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            ID: <?php echo $sensor['id']; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 bg-<?php echo $typeColor; ?>-100 dark:bg-<?php echo $typeColor; ?>-900 text-<?php echo $typeColor; ?>-800 dark:text-<?php echo $typeColor; ?>-200 text-xs font-medium rounded-full">
                                    <?php echo ucfirst(str_replace('_', ' ', $sensor['sensor_type'])); ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <div class="flex items-center">
                                    <i class="fas fa-map-marker-alt text-gray-400 dark:text-gray-500 mr-1 text-xs"></i>
                                    <span class="truncate"><?php echo htmlspecialchars($sensor['location']); ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-<?php echo $statusColor; ?>-500 rounded-full mr-2"></div>
                                    <span class="px-2 py-1 bg-<?php echo $statusColor; ?>-100 dark:bg-<?php echo $statusColor; ?>-900 text-<?php echo $statusColor; ?>-800 dark:text-<?php echo $statusColor; ?>-200 text-xs font-medium rounded-full">
                                        <?php echo ucfirst($sensor['status']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <?php if ($sensor['latest_value'] !== null): ?>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo number_format($sensor['latest_value'], 1) . $sensor['unit']; ?>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php
                                        // Add context based on sensor type and value
                                        $value = $sensor['latest_value'];
                                        $type = $sensor['sensor_type'];
                                        $status = '';

                                        if ($type === 'temperature') {
                                            if ($value >= 20 && $value <= 28) $status = 'Optimal';
                                            elseif ($value < 15 || $value > 35) $status = 'Critical';
                                            else $status = 'Moderate';
                                        } elseif ($type === 'humidity') {
                                            if ($value >= 60 && $value <= 80) $status = 'Good';
                                            elseif ($value < 40 || $value > 90) $status = 'Poor';
                                            else $status = 'Fair';
                                        } elseif ($type === 'soil_moisture') {
                                            if ($value >= 40 && $value <= 60) $status = 'Good';
                                            elseif ($value < 30) $status = 'Dry';
                                            else $status = 'Wet';
                                        }
                                        echo $status;
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">No data</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php
                                if ($sensor['last_reading']) {
                                    $timestamp = strtotime($sensor['last_reading']);
                                    $now = time();
                                    $diff = $now - $timestamp;

                                    if ($diff < 60) {
                                        echo 'Just now';
                                    } elseif ($diff < 3600) {
                                        echo floor($diff / 60) . 'm ago';
                                    } elseif ($diff < 86400) {
                                        echo floor($diff / 3600) . 'h ago';
                                    } else {
                                        echo date('M j', $timestamp);
                                    }
                                } else {
                                    echo 'Never';
                                }
                                ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <button class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="View Details">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>
                                    <button class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300" title="Configure">
                                        <i class="fas fa-cog text-xs"></i>
                                    </button>
                                    <button class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300" title="Calibrate">
                                        <i class="fas fa-wrench text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($sensors)): ?>
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-thermometer-half text-gray-400 dark:text-gray-500 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No sensors found</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Get started by adding your first sensor to the system</p>
                <button class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    Add First Sensor
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Chart.js Configuration and Data -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded. Please check the CDN link.');
            return;
        }

        // Detect dark mode
        const isDarkMode = document.documentElement.classList.contains('dark');

        // Chart configuration with dashboard-consistent styling
        const chartConfig = {
            type: 'line',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // Hide legend for cleaner look like dashboard
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: isDarkMode ? '#1f2937' : '#ffffff',
                        titleColor: isDarkMode ? '#f9fafb' : '#111827',
                        bodyColor: isDarkMode ? '#e5e7eb' : '#374151',
                        borderColor: isDarkMode ? '#374151' : '#e5e7eb',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false,
                        titleFont: {
                            size: 12,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 11
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false // Clean look like dashboard
                        },
                        ticks: {
                            color: isDarkMode ? '#9ca3af' : '#6b7280',
                            font: {
                                size: 10
                            },
                            maxTicksLimit: 6
                        }
                    },
                    y: {
                        display: true,
                        grid: {
                            color: isDarkMode ? '#374151' : '#f3f4f6',
                            drawBorder: false
                        },
                        ticks: {
                            color: isDarkMode ? '#9ca3af' : '#6b7280',
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                elements: {
                    point: {
                        radius: 2,
                        hoverRadius: 4,
                        borderWidth: 2
                    },
                    line: {
                        tension: 0.3,
                        borderWidth: 2
                    }
                }
            }
        };

        // Chart data from PHP
        const chartDataByType = <?php echo json_encode($chartDataByType); ?>;

        // Color schemes matching dashboard style
        const colorSchemes = {
            temperature: {
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                pointBackgroundColor: '#ef4444',
                pointBorderColor: '#ffffff'
            },
            humidity: {
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#ffffff'
            },
            soil_moisture: {
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#ffffff'
            }
        };

        // Create charts for each sensor type
        Object.keys(chartDataByType).forEach(sensorType => {
            const canvas = document.getElementById(`chart-${sensorType}`);
            if (!canvas) {
                console.warn(`Canvas element not found for sensor type: ${sensorType}`);
                return;
            }

            const ctx = canvas.getContext('2d');
            const data = chartDataByType[sensorType];

            if (!data || data.length === 0) {
                console.warn(`No data available for sensor type: ${sensorType}`);
                return;
            }

            // Group data by sensor name and create time labels
            const sensorGroups = {};
            const timeLabels = [];

            data.forEach(reading => {
                if (!sensorGroups[reading.sensor_name]) {
                    sensorGroups[reading.sensor_name] = [];
                }

                // Format time for display
                const date = new Date(reading.recorded_at);
                const timeLabel = date.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });

                if (!timeLabels.includes(timeLabel)) {
                    timeLabels.push(timeLabel);
                }

                sensorGroups[reading.sensor_name].push({
                    time: timeLabel,
                    value: parseFloat(reading.value)
                });
            });

            // Sort time labels
            timeLabels.sort();

            // Create datasets for each sensor with dashboard styling
            const datasets = Object.keys(sensorGroups).map((sensorName, index) => {
                const colors = colorSchemes[sensorType] || colorSchemes.temperature;

                // Generate slight color variations for multiple sensors of same type
                const hueShift = index * 20;
                const adjustedColor = colors.borderColor;

                // Convert data to simple array format for Chart.js
                const chartData = sensorGroups[sensorName].map(point => point.value);

                return {
                    label: sensorName,
                    data: chartData,
                    borderColor: adjustedColor,
                    backgroundColor: colors.backgroundColor,
                    pointBackgroundColor: adjustedColor,
                    pointBorderColor: colors.pointBorderColor,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3
                };
            });

            // Get unit for y-axis label
            const unit = data.length > 0 ? data[0].unit : '';
            const config = {
                ...chartConfig,
                data: {
                    labels: timeLabels,
                    datasets
                },
                options: {
                    ...chartConfig.options,
                    scales: {
                        ...chartConfig.options.scales,
                        y: {
                            ...chartConfig.options.scales.y,
                            title: {
                                display: true,
                                text: `Value (${unit})`
                            }
                        }
                    }
                }
            };

            try {
                new Chart(ctx, config);
                console.log(`Chart created successfully for sensor type: ${sensorType}`);
            } catch (error) {
                console.error(`Error creating chart for sensor type ${sensorType}:`, error);
            }
        });
    });

    // Arduino data refresh functionality
    function refreshArduinoData() {
        const refreshBtn = document.querySelector('button[onclick="refreshArduinoData()"]');
        const originalText = refreshBtn.innerHTML;
        
        // Show loading state
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...';
        refreshBtn.disabled = true;
        
        // Reload the page to get fresh Arduino data
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }

    // Show Arduino setup instructions
    function showArduinoInstructions() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-2xl mx-4 max-h-96 overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Arduino Bridge Setup</h3>
                    <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">1. Install Python Dependencies</h4>
                        <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">pip install flask pyserial</code>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">2. Connect Arduino</h4>
                        <p>Connect your Arduino to COM3 (or update the port in arduino_bridge.py)</p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">3. Start Bridge Service</h4>
                        <p>Double-click <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">start_arduino_bridge.bat</code></p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">4. Arduino Code</h4>
                        <p>Your existing code is perfect! Just sends analog values (0-1023)</p>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Real-time Arduino sensors updates
    function updateSensorsArduinoData() {
        fetch('arduino_sync.php?action=get_all')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const sensors = data.data;
                    
                    // Update temperature
                    if (sensors.temperature && sensors.temperature.value !== null) {
                        const tempElement = document.getElementById('arduino-temperature');
                        const tempTimeElement = document.getElementById('arduino-temp-time');
                        
                        if (tempElement) {
                            tempElement.textContent = parseFloat(sensors.temperature.value).toFixed(1) + '°C';
                        }
                        if (tempTimeElement) {
                            tempTimeElement.textContent = sensors.temperature.timestamp || 'Just now';
                        }
                    }
                    
                    // Update humidity
                    if (sensors.humidity && sensors.humidity.value !== null) {
                        const humElement = document.getElementById('arduino-humidity');
                        const humTimeElement = document.getElementById('arduino-hum-time');
                        const humStatusElement = document.getElementById('arduino-hum-status');
                        
                        if (humElement) {
                            humElement.textContent = parseFloat(sensors.humidity.value).toFixed(1) + '%';
                        }
                        if (humTimeElement) {
                            humTimeElement.textContent = sensors.humidity.timestamp || 'Just now';
                        }
                        
                        // Update humidity status
                        if (humStatusElement) {
                            const value = parseFloat(sensors.humidity.value);
                            let status = '';
                            let statusClass = '';
                            
                            if (value >= 60 && value <= 80) {
                                status = 'Optimal';
                                statusClass = 'font-medium text-green-600 dark:text-green-400';
                            } else if (value >= 50 && value < 90) {
                                status = 'Acceptable';
                                statusClass = 'font-medium text-yellow-600 dark:text-yellow-400';
                            } else {
                                status = 'Needs Attention';
                                statusClass = 'font-medium text-red-600 dark:text-red-400';
                            }
                            
                            humStatusElement.textContent = status;
                            humStatusElement.className = statusClass;
                        }
                    }
                    
                    // Update soil moisture
                    if (sensors.soil_moisture && sensors.soil_moisture.value !== null) {
                        const soilElement = document.getElementById('arduino-soil');
                        const soilTimeElement = document.getElementById('arduino-soil-time');
                        
                        if (soilElement) {
                            soilElement.textContent = parseFloat(sensors.soil_moisture.value).toFixed(1) + '%';
                        }
                        if (soilTimeElement) {
                            soilTimeElement.textContent = sensors.soil_moisture.timestamp || 'Just now';
                        }
                    }
                }
            })
            .catch(error => console.log('Arduino sensors update failed:', error));
    }

    // Start real-time updates for sensors page
    <?php if ($arduinoHealthy): ?>
    // Update every 5 seconds for real-time feel
    setInterval(updateSensorsArduinoData, 5000);
    
    // Initial update after 2 seconds
    setTimeout(updateSensorsArduinoData, 2000);
    <?php endif; ?>
</script>

<?php
// Include shared footer
include 'includes/footer.php';
?>
</main>
</div>
</div>