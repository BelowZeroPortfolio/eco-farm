<?php
/**
 * Advanced Sensors Analytics & Visualization Page
 * Features: Multi-chart types, filtering, export, real-time updates
 */

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/language.php';
require_once 'includes/arduino-api.php';

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Get filter parameters
$filterType = $_GET['sensor_type'] ?? 'all';
$filterPeriod = $_GET['period'] ?? '24h';
$chartType = $_GET['chart'] ?? 'line';

// Get sensor logging interval from settings (in minutes, convert to seconds)
function getSensorLoggingInterval() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT setting_value 
            FROM user_settings 
            WHERE setting_key = 'sensor_logging_interval' 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        $minutes = $result ? floatval($result['setting_value']) : 30;
        // Convert minutes to seconds
        return intval($minutes * 60);
    } catch (Exception $e) {
        error_log("Failed to get sensor interval: " . $e->getMessage());
        return 1800; // Default 30 minutes = 1800 seconds
    }
}

$sensorInterval = getSensorLoggingInterval();
$sensorIntervalDisplay = $sensorInterval >= 60 ? round($sensorInterval / 60) . 'm' : $sensorInterval . 's';

// Calculate date range based on period
function getDateRange($period) {
    $now = new DateTime();
    switch ($period) {
        case '1h': return $now->modify('-1 hour')->format('Y-m-d H:i:s');
        case '6h': return $now->modify('-6 hours')->format('Y-m-d H:i:s');
        case '24h': return $now->modify('-24 hours')->format('Y-m-d H:i:s');
        case '7d': return $now->modify('-7 days')->format('Y-m-d H:i:s');
        case '30d': return $now->modify('-30 days')->format('Y-m-d H:i:s');
        default: return $now->modify('-24 hours')->format('Y-m-d H:i:s');
    }
}

// Get filtered sensor data
function getFilteredSensorData($sensorType = 'all', $startDate) {
    try {
        $pdo = getDatabaseConnection();
        
        $whereClause = "WHERE sr.recorded_at >= ?";
        $params = [$startDate];
        
        if ($sensorType !== 'all') {
            $whereClause .= " AND s.sensor_type = ?";
            $params[] = $sensorType;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                s.id as sensor_id,
                s.sensor_name,
                s.sensor_type,
                s.location,
                sr.value,
                sr.unit,
                sr.recorded_at
            FROM sensors s
            JOIN sensor_readings sr ON s.id = sr.sensor_id
            $whereClause
            ORDER BY sr.recorded_at ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get filtered sensor data: " . $e->getMessage());
        return [];
    }
}

// Get sensor statistics
function getSensorStats($sensorType = 'all', $startDate) {
    try {
        $pdo = getDatabaseConnection();
        
        $whereClause = "WHERE sr.recorded_at >= ?";
        $params = [$startDate];
        
        if ($sensorType !== 'all') {
            $whereClause .= " AND s.sensor_type = ?";
            $params[] = $sensorType;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                s.sensor_type,
                COUNT(*) as reading_count,
                AVG(sr.value) as avg_value,
                MIN(sr.value) as min_value,
                MAX(sr.value) as max_value,
                sr.unit
            FROM sensors s
            JOIN sensor_readings sr ON s.id = sr.sensor_id
            $whereClause
            GROUP BY s.sensor_type, sr.unit
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get sensor stats: " . $e->getMessage());
        return [];
    }
}

// Get sensor thresholds from database
function getSensorThresholds() {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                sensor_type,
                AVG(alert_threshold_min) as min_threshold,
                AVG(alert_threshold_max) as max_threshold
            FROM sensors
            WHERE alert_threshold_min IS NOT NULL 
            AND alert_threshold_max IS NOT NULL
            GROUP BY sensor_type
        ");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        // Convert to associative array
        $thresholds = [];
        foreach ($results as $row) {
            $thresholds[$row['sensor_type']] = [
                'min' => floatval($row['min_threshold']),
                'max' => floatval($row['max_threshold'])
            ];
        }
        
        // Fallback to defaults if no thresholds in database
        if (empty($thresholds)) {
            return [
                'temperature' => ['min' => 20, 'max' => 28],
                'humidity' => ['min' => 60, 'max' => 80],
                'soil_moisture' => ['min' => 40, 'max' => 60]
            ];
        }
        
        return $thresholds;
    } catch (Exception $e) {
        error_log("Failed to get sensor thresholds: " . $e->getMessage());
        // Return defaults on error
        return [
            'temperature' => ['min' => 20, 'max' => 28],
            'humidity' => ['min' => 60, 'max' => 80],
            'soil_moisture' => ['min' => 40, 'max' => 60]
        ];
    }
}

$startDate = getDateRange($filterPeriod);
$sensorData = getFilteredSensorData($filterType, $startDate);
$sensorStats = getSensorStats($filterType, $startDate);
$thresholds = getSensorThresholds();

// Initialize Arduino bridge
$arduino = new ArduinoBridge();
$arduinoHealthy = $arduino->isHealthy();
$arduinoData = $arduinoHealthy ? $arduino->getAllSensorData() : null;

// Organize data by sensor type
$dataByType = [];
foreach ($sensorData as $reading) {
    $type = $reading['sensor_type'];
    if (!isset($dataByType[$type])) {
        $dataByType[$type] = [];
    }
    $dataByType[$type][] = $reading;
}

$pageTitle = 'Advanced Sensor Analytics - IoT Farm Monitoring';
include 'includes/header.php';

$currentLanguage = getCurrentLanguage();
$translations = getTranslations();
$jsTranslations = $translations[$currentLanguage] ?? $translations['en'];
?>

<script>
    const pageLanguage = '<?php echo $currentLanguage; ?>';
    const pageTranslations = <?php echo json_encode($jsTranslations); ?>;
</script>
<script src="includes/language.js"></script>

<?php include 'includes/navigation.php'; ?>

<div class="p-4 max-w-7xl mx-auto">

    <!-- Arduino Service Status -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-microchip text-blue-600 dark:text-blue-400"></i>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Arduino Sensor Service</span>
                </div>
                <span id="arduino-status-indicator" class="px-3 py-1 text-xs font-medium rounded-full <?php echo $arduinoHealthy ? 'bg-green-600 text-white' : 'bg-gray-400 text-white'; ?>">
                    <?php if ($arduinoHealthy): ?>
                        <i class="fas fa-check-circle mr-1"></i>ONLINE
                    <?php else: ?>
                        <i class="fas fa-spinner fa-spin mr-1"></i>CHECKING...
                    <?php endif; ?>
                </span>
            </div>
            <button onclick="refreshArduinoStatus()" class="px-3 py-1.5 text-xs bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <i class="fas fa-sync-alt mr-1"></i>Refresh
            </button>
        </div>
        
        <!-- Status Details Card -->
        <div id="arduino-status-card" class="mt-3 <?php echo $arduinoHealthy ? '' : 'hidden'; ?>">
            <?php if ($arduinoHealthy && $arduinoData): ?>
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-gray-900 dark:text-white text-sm">Service Running</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Arduino sensor bridge is online and collecting data</div>
                    </div>
                    <div class="flex gap-4 text-xs">
                        <?php if (isset($arduinoData['temperature'])): ?>
                        <div class="text-center">
                            <div class="text-gray-500 dark:text-gray-400">Temp</div>
                            <div class="font-bold text-gray-900 dark:text-white"><?php echo number_format($arduinoData['temperature']['value'] ?? 0, 1); ?>°C</div>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($arduinoData['humidity'])): ?>
                        <div class="text-center">
                            <div class="text-gray-500 dark:text-gray-400">Humidity</div>
                            <div class="font-bold text-gray-900 dark:text-white"><?php echo number_format($arduinoData['humidity']['value'] ?? 0, 1); ?>%</div>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($arduinoData['soil_moisture'])): ?>
                        <div class="text-center">
                            <div class="text-gray-500 dark:text-gray-400">Soil</div>
                            <div class="font-bold text-gray-900 dark:text-white"><?php echo number_format($arduinoData['soil_moisture']['value'] ?? 0, 1); ?>%</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Offline Card -->
        <div id="arduino-offline-card" class="mt-3 <?php echo $arduinoHealthy ? 'hidden' : ''; ?>">
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-gray-900 dark:text-white text-sm">Service Offline</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Arduino sensor bridge is not running or unreachable</div>
                    </div>
                </div>
                <div class="mt-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded p-2">
                    <p class="text-xs text-yellow-800 dark:text-yellow-200">
                        <i class="fas fa-info-circle mr-1"></i>
                        Start the Arduino bridge by running <code class="bg-yellow-100 dark:bg-yellow-900/50 px-1 rounded">START_MONITORING.bat</code> or <code class="bg-yellow-100 dark:bg-yellow-900/50 px-1 rounded">START_MONITORING_WITH_NGROK.bat</code> on your PC.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Error Card -->
        <div id="arduino-error-card" class="mt-3 hidden">
            <div class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-100 dark:bg-gray-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-question-circle text-gray-500 dark:text-gray-400 text-lg"></i>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white text-sm">Status Unknown</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Unable to check Arduino service status</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-3">
        <?php 
        // Get active plant thresholds for comparison
        $activePlantThresholds = null;
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->query("
                SELECT p.* 
                FROM plants p
                INNER JOIN activeplant ap ON p.PlantID = ap.SelectedPlantID
                LIMIT 1
            ");
            $activePlantThresholds = $stmt->fetch();
        } catch (Exception $e) {
            $activePlantThresholds = null;
        }
        
        foreach ($sensorStats as $stat): 
            $icons = [
                'temperature' => ['icon' => 'fa-thermometer-half', 'color' => 'red', 'emoji' => '🌡️'],
                'humidity' => ['icon' => 'fa-tint', 'color' => 'blue', 'emoji' => '💧'],
                'soil_moisture' => ['icon' => 'fa-seedling', 'color' => 'green', 'emoji' => '🌱']
            ];
            $config = $icons[$stat['sensor_type']] ?? ['icon' => 'fa-sensor', 'color' => 'gray', 'emoji' => '📊'];
            
            // Get plant thresholds for this sensor type
            $plantMin = null;
            $plantMax = null;
            $isWithinRange = true;
            
            if ($activePlantThresholds) {
                switch ($stat['sensor_type']) {
                    case 'temperature':
                        $plantMin = $activePlantThresholds['MinTemperature'];
                        $plantMax = $activePlantThresholds['MaxTemperature'];
                        break;
                    case 'humidity':
                        $plantMin = $activePlantThresholds['MinHumidity'];
                        $plantMax = $activePlantThresholds['MaxHumidity'];
                        break;
                    case 'soil_moisture':
                        $plantMin = $activePlantThresholds['MinSoilMoisture'];
                        $plantMax = $activePlantThresholds['MaxSoilMoisture'];
                        break;
                }
                
                // Check if average is within plant range
                if ($plantMin !== null && $plantMax !== null) {
                    $isWithinRange = ($stat['avg_value'] >= $plantMin && $stat['avg_value'] <= $plantMax);
                }
            }
        ?>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-lg"><?php echo $config['emoji']; ?></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        <?php echo number_format($stat['reading_count']); ?> pts
                    </span>
                </div>
                <h3 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <?php echo ucfirst(str_replace('_', ' ', $stat['sensor_type'])); ?>
                </h3>
                <div class="text-xs mb-2">
                    <span class="text-gray-500 dark:text-gray-400">Avg:</span>
                    <span class="font-bold text-gray-900 dark:text-white ml-1">
                        <?php echo number_format($stat['avg_value'], 1); ?><?php echo $stat['unit']; ?>
                    </span>
                    <?php if ($isWithinRange): ?>
                        <span class="text-green-500 ml-1">●</span>
                    <?php else: ?>
                        <span class="text-red-500 ml-1">●</span>
                    <?php endif; ?>
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500 mb-1">
                    <span class="font-medium">Data Range:</span>
                    <?php echo number_format($stat['min_value'], 1); ?> - <?php echo number_format($stat['max_value'], 1); ?>
                </div>
                <?php if ($plantMin !== null && $plantMax !== null): ?>
                <div class="text-xs <?php echo $isWithinRange ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?> font-medium">
                    <span class="opacity-75">Plant Range:</span>
                    <?php echo number_format($plantMin, 1); ?> - <?php echo number_format($plantMax, 1); ?><?php echo $stat['unit']; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Active Plant Selector -->
    <?php
    try {
        $pdo = getDatabaseConnection();
        
        // Get all plants
        $plantsStmt = $pdo->query("SELECT PlantID, PlantName, LocalName FROM plants ORDER BY PlantName");
        $plants = $plantsStmt->fetchAll();
        
        // Get active plant
        $activeStmt = $pdo->query("SELECT SelectedPlantID FROM activeplant LIMIT 1");
        $activeResult = $activeStmt->fetch();
        $activePlantID = $activeResult ? $activeResult['SelectedPlantID'] : null;
    } catch (Exception $e) {
        $plants = [];
        $activePlantID = null;
    }
    ?>
    
    <?php if (!empty($plants)): ?>
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 mb-3">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 flex-1">
                <i class="fas fa-seedling text-green-600 dark:text-green-400"></i>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Active Plant:</span>
                
                <!-- Searchable Plant Selector -->
                <div class="relative flex-1 max-w-xs">
                    <input type="text" 
                           id="plantSearchInput" 
                           list="plantsList"
                           placeholder="Type or select plant..."
                           value="<?php 
                               if ($activePlantID) {
                                   foreach ($plants as $plant) {
                                       if ($plant['PlantID'] == $activePlantID) {
                                           echo htmlspecialchars($plant['PlantName']) . ' (' . htmlspecialchars($plant['LocalName']) . ')';
                                           break;
                                       }
                                   }
                               }
                           ?>"
                           class="w-full px-3 py-1.5 text-xs bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-green-300 dark:border-green-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 font-medium"
                           onchange="handlePlantSelection()"
                           onfocus="this.select()">
                    
                    <datalist id="plantsList">
                        <?php foreach ($plants as $plant): ?>
                            <option value="<?php echo htmlspecialchars($plant['PlantName']); ?> (<?php echo htmlspecialchars($plant['LocalName']); ?>)" 
                                    data-id="<?php echo $plant['PlantID']; ?>">
                        <?php endforeach; ?>
                    </datalist>
                    
                    <!-- Hidden input to store plant ID -->
                    <input type="hidden" id="selectedPlantId" value="<?php echo $activePlantID ?? ''; ?>">
                </div>
                
                <?php if ($activePlantID): ?>
                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs rounded-full font-medium">
                        <i class="fas fa-check-circle mr-1"></i>Active
                    </span>
                    
                    <?php
                    // Get latest violation count for active plant
                    try {
                        $stmt = $pdo->prepare("
                            SELECT WarningLevel 
                            FROM sensorreadings 
                            WHERE PlantID = ? 
                            ORDER BY ReadingTime DESC 
                            LIMIT 1
                        ");
                        $stmt->execute([$activePlantID]);
                        $latestReading = $stmt->fetch();
                        $violationCount = $latestReading ? $latestReading['WarningLevel'] : 0;
                    } catch (Exception $e) {
                        $violationCount = 0;
                    }
                    ?>
                    
                    <span id="violation-counter" class="px-2 py-1 <?php echo $violationCount >= 5 ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' : ($violationCount > 0 ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300' : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'); ?> text-xs rounded-full font-medium">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Violations: <span id="violation-count"><?php echo $violationCount; ?></span> / <?php 
                        // Get warning trigger for active plant
                        try {
                            $stmt = $pdo->prepare("SELECT WarningTrigger FROM plants WHERE PlantID = ?");
                            $stmt->execute([$activePlantID]);
                            $plantData = $stmt->fetch();
                            echo $plantData ? $plantData['WarningTrigger'] : 5;
                        } catch (Exception $e) {
                            echo 5;
                        }
                        ?>
                    </span>
                    
                    <span id="scan-interval-badge" class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs rounded-full font-medium">
                        <i class="fas fa-clock mr-1"></i>Scan: <span id="scan-interval-display"><?php echo $sensorIntervalDisplay; ?></span>
                    </span>
                    
                    <!-- Reset Violations Button (Admin only) -->
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <button onclick="resetViolations()" class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 text-xs rounded-full font-medium" title="Reset violation counter">
                        <i class="fas fa-redo mr-1"></i>Reset
                    </button>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs rounded-full">
                        <i class="fas fa-exclamation-circle mr-1"></i>Inactive
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Compact Filters -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-3">
        <div class="flex flex-wrap items-center gap-2">
            <select id="sensorTypeFilter" onchange="applyFilters()" class="px-3 py-1.5 text-xs bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Sensors</option>
                <option value="temperature" <?php echo $filterType === 'temperature' ? 'selected' : ''; ?>>🌡️ Temperature</option>
                <option value="humidity" <?php echo $filterType === 'humidity' ? 'selected' : ''; ?>>💧 Humidity</option>
                <option value="soil_moisture" <?php echo $filterType === 'soil_moisture' ? 'selected' : ''; ?>>🌱 Soil Moisture</option>
            </select>
            
            <select id="periodFilter" onchange="applyFilters()" class="px-3 py-1.5 text-xs bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="1h" <?php echo $filterPeriod === '1h' ? 'selected' : ''; ?>>1 Hour</option>
                <option value="6h" <?php echo $filterPeriod === '6h' ? 'selected' : ''; ?>>6 Hours</option>
                <option value="24h" <?php echo $filterPeriod === '24h' ? 'selected' : ''; ?>>24 Hours</option>
                <option value="7d" <?php echo $filterPeriod === '7d' ? 'selected' : ''; ?>>7 Days</option>
                <option value="30d" <?php echo $filterPeriod === '30d' ? 'selected' : ''; ?>>30 Days</option>
            </select>
            
            <select id="chartTypeFilter" onchange="applyFilters()" class="px-3 py-1.5 text-xs bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="line" <?php echo $chartType === 'line' ? 'selected' : ''; ?>>📈 Line</option>
                <option value="bar" <?php echo $chartType === 'bar' ? 'selected' : ''; ?>>📊 Bar</option>
                <option value="area" <?php echo $chartType === 'area' ? 'selected' : ''; ?>>📉 Area</option>
            </select>
            
            <span class="ml-auto text-xs text-gray-500 dark:text-gray-400">
                <?php echo count($sensorData); ?> readings
            </span>
        </div>
    </div>
    
    <!-- Main Chart -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-3">
        <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-xs font-semibold text-gray-900 dark:text-white">
                📊 Visualization
            </h3>
        </div>
        <div class="p-3">
            <div class="chart-container" style="position: relative; height: 250px;">
                <canvas id="mainChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Individual Sensor Type Charts -->
    <?php if ($filterType === 'all'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 mb-3">
        <?php 
        $sensorTypes = [
            'temperature' => ['name' => 'Temperature', 'emoji' => '🌡️', 'color' => 'red'],
            'humidity' => ['name' => 'Humidity', 'emoji' => '💧', 'color' => 'blue'],
            'soil_moisture' => ['name' => 'Soil Moisture', 'emoji' => '🌱', 'color' => 'green']
        ];
        
        foreach ($sensorTypes as $type => $config): 
            if (!isset($dataByType[$type]) || empty($dataByType[$type])) continue;
        ?>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                    <h4 class="text-xs font-medium text-gray-900 dark:text-white">
                        <?php echo $config['emoji']; ?> <?php echo $config['name']; ?>
                    </h4>
                </div>
                <div class="p-2">
                    <div class="chart-container" style="position: relative; height: 140px;">
                        <canvas id="chart-<?php echo $type; ?>"></canvas>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Data Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-semibold text-gray-900 dark:text-white">
                    📋 Recent Readings
                </h3>
                <input type="text" id="tableSearch" placeholder="Search..." class="px-2 py-1 text-xs bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
        </div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="dataTable">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer" onclick="sortTable(0)">
                            Time <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer" onclick="sortTable(1)">
                            Type <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer" onclick="sortTable(2)">
                            Sensor <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer" onclick="sortTable(3)">
                            Value <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php 
                    $typeEmojis = [
                        'temperature' => '🌡️',
                        'humidity' => '💧',
                        'soil_moisture' => '🌱'
                    ];
                    
                    // Limit to last 50 readings for better UX
                    $displayData = array_slice($sensorData, -50);
                    
                    foreach (array_reverse($displayData) as $reading): 
                        $value = $reading['value'];
                        $type = $reading['sensor_type'];
                        $threshold = $thresholds[$type] ?? ['min' => 0, 'max' => 100];
                        
                        $isOptimal = ($value >= $threshold['min'] && $value <= $threshold['max']);
                        $statusColor = $isOptimal ? 'green' : ($value < $threshold['min'] ? 'blue' : 'red');
                        $statusText = $isOptimal ? '✓' : ($value < $threshold['min'] ? '↓' : '↑');
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-3 py-2 text-xs text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                <?php echo date('M j, g:i A', strtotime($reading['recorded_at'])); ?>
                            </td>
                            <td class="px-3 py-2 text-xs">
                                <span class="inline-flex items-center">
                                    <?php echo $typeEmojis[$type] ?? '📊'; ?>
                                    <span class="ml-1 text-gray-700 dark:text-gray-300">
                                        <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                                    </span>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($reading['sensor_name']); ?>
                            </td>
                            <td class="px-3 py-2 text-sm font-bold text-gray-900 dark:text-white">
                                <?php echo number_format($reading['value'], 1); ?><?php echo htmlspecialchars($reading['unit']); ?>
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 text-sm bg-<?php echo $statusColor; ?>-100 dark:bg-<?php echo $statusColor; ?>-900/50 text-<?php echo $statusColor; ?>-700 dark:text-<?php echo $statusColor; ?>-300 rounded-full">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (empty($sensorData)): ?>
            <div class="p-6 text-center">
                <div class="text-4xl mb-2">📊</div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No data for selected filters
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
    }

    const isDarkMode = document.documentElement.classList.contains('dark');
    const chartType = '<?php echo $chartType; ?>';
    
    // Color schemes
    const colorSchemes = {
        temperature: {
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.2)',
            pointBackgroundColor: '#ef4444'
        },
        humidity: {
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            pointBackgroundColor: '#3b82f6'
        },
        soil_moisture: {
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.2)',
            pointBackgroundColor: '#10b981'
        }
    };

    // Prepare data
    const sensorData = <?php echo json_encode($sensorData); ?>;
    const dataByType = <?php echo json_encode($dataByType); ?>;
    
    // Create main chart
    createMainChart(sensorData, chartType, isDarkMode);
    
    // Create individual charts if showing all sensors
    <?php if ($filterType === 'all'): ?>
    Object.keys(dataByType).forEach(type => {
        if (dataByType[type].length > 0) {
            createTypeChart(type, dataByType[type], chartType, isDarkMode);
        }
    });
    <?php endif; ?>

    function createMainChart(data, type, darkMode) {
        const canvas = document.getElementById('mainChart');
        if (!canvas || data.length === 0) return;

        const ctx = canvas.getContext('2d');
        
        // Group by sensor type
        const grouped = {};
        data.forEach(reading => {
            if (!grouped[reading.sensor_type]) {
                grouped[reading.sensor_type] = {
                    labels: [],
                    values: []
                };
            }
            const time = new Date(reading.recorded_at).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
            grouped[reading.sensor_type].labels.push(time);
            grouped[reading.sensor_type].values.push(parseFloat(reading.value));
        });

        // Create datasets
        const datasets = Object.keys(grouped).map(sensorType => {
            const colors = colorSchemes[sensorType] || colorSchemes.temperature;
            return {
                label: sensorType.replace('_', ' ').toUpperCase(),
                data: grouped[sensorType].values,
                borderColor: colors.borderColor,
                backgroundColor: type === 'area' ? colors.backgroundColor : 'transparent',
                pointBackgroundColor: colors.pointBackgroundColor,
                pointBorderColor: '#ffffff',
                fill: type === 'area',
                tension: 0.4,
                borderWidth: 2,
                pointRadius: type === 'scatter' ? 4 : 2,
                pointHoverRadius: 6
            };
        });

        // Use first sensor type's labels (or merge all unique labels)
        const allLabels = Object.values(grouped)[0]?.labels || [];

        new Chart(ctx, {
            type: type === 'area' ? 'line' : type,
            data: {
                labels: allLabels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: darkMode ? '#e5e7eb' : '#374151',
                            font: { size: 12 },
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: darkMode ? '#1f2937' : '#ffffff',
                        titleColor: darkMode ? '#f9fafb' : '#111827',
                        bodyColor: darkMode ? '#e5e7eb' : '#374151',
                        borderColor: darkMode ? '#374151' : '#e5e7eb',
                        borderWidth: 1,
                        cornerRadius: 8,
                        padding: 12
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            color: darkMode ? '#374151' : '#e5e7eb',
                            drawBorder: false
                        },
                        ticks: {
                            color: darkMode ? '#9ca3af' : '#6b7280',
                            font: { size: 10 },
                            maxTicksLimit: 12
                        }
                    },
                    y: {
                        display: true,
                        grid: {
                            color: darkMode ? '#374151' : '#e5e7eb',
                            drawBorder: false
                        },
                        ticks: {
                            color: darkMode ? '#9ca3af' : '#6b7280',
                            font: { size: 11 }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    function createTypeChart(sensorType, data, type, darkMode) {
        const canvas = document.getElementById(`chart-${sensorType}`);
        if (!canvas || data.length === 0) return;

        const ctx = canvas.getContext('2d');
        const colors = colorSchemes[sensorType] || colorSchemes.temperature;

        const labels = data.map(r => {
            const date = new Date(r.recorded_at);
            return date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
        });
        const values = data.map(r => parseFloat(r.value));

        new Chart(ctx, {
            type: type === 'area' ? 'line' : type,
            data: {
                labels: labels,
                datasets: [{
                    label: sensorType.replace('_', ' ').toUpperCase(),
                    data: values,
                    borderColor: colors.borderColor,
                    backgroundColor: type === 'area' ? colors.backgroundColor : 'transparent',
                    pointBackgroundColor: colors.pointBackgroundColor,
                    pointBorderColor: '#ffffff',
                    fill: type === 'area',
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: type === 'scatter' ? 3 : 1.5,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: darkMode ? '#1f2937' : '#ffffff',
                        titleColor: darkMode ? '#f9fafb' : '#111827',
                        bodyColor: darkMode ? '#e5e7eb' : '#374151',
                        borderColor: darkMode ? '#374151' : '#e5e7eb',
                        borderWidth: 1,
                        cornerRadius: 6,
                        padding: 8
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: { display: false },
                        ticks: {
                            color: darkMode ? '#9ca3af' : '#6b7280',
                            font: { size: 8 },
                            maxTicksLimit: 6
                        }
                    },
                    y: {
                        display: true,
                        grid: {
                            color: darkMode ? '#374151' : '#e5e7eb',
                            drawBorder: false
                        },
                        ticks: {
                            color: darkMode ? '#9ca3af' : '#6b7280',
                            font: { size: 9 }
                        }
                    }
                }
            }
        });
    }
});

// Filter functions
function applyFilters() {
    const sensorType = document.getElementById('sensorTypeFilter').value;
    const period = document.getElementById('periodFilter').value;
    const chart = document.getElementById('chartTypeFilter').value;
    
    window.location.href = `sensors.php?sensor_type=${sensorType}&period=${period}&chart=${chart}`;
}

// Handle plant selection from searchable input
function handlePlantSelection() {
    const input = document.getElementById('plantSearchInput');
    const selectedValue = input.value;
    
    // Find matching plant ID from the datalist
    const datalist = document.getElementById('plantsList');
    const options = datalist.querySelectorAll('option');
    
    let plantId = null;
    for (let option of options) {
        if (option.value === selectedValue) {
            plantId = option.getAttribute('data-id');
            break;
        }
    }
    
    if (plantId) {
        document.getElementById('selectedPlantId').value = plantId;
        changeActivePlant(plantId, selectedValue);
    } else {
        // If no match, try to find partial match
        const searchTerm = selectedValue.toLowerCase();
        for (let option of options) {
            if (option.value.toLowerCase().includes(searchTerm)) {
                plantId = option.getAttribute('data-id');
                input.value = option.value; // Set to full match
                document.getElementById('selectedPlantId').value = plantId;
                changeActivePlant(plantId, option.value);
                break;
            }
        }
    }
}

// Change active plant
async function changeActivePlant(plantId, plantName) {
    if (!plantId) {
        plantId = document.getElementById('selectedPlantId').value;
    }
    
    if (!plantId) {
        alert('Please select a valid plant');
        return;
    }
    
    if (!plantName) {
        plantName = document.getElementById('plantSearchInput').value;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'set_active');
        formData.append('id', plantId);
        
        const response = await fetch('plant_database.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Create success notification
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
            notification.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>Active plant set to: ${plantName}</span>
            `;
            document.body.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.remove();
                // Reload to update status badge
                window.location.reload();
            }, 2000);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error changing active plant: ' + error.message);
    }
}

// Table search
document.getElementById('tableSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#dataTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Table sorting
let sortDirection = {};
function sortTable(columnIndex) {
    const table = document.getElementById('dataTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const direction = sortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
    sortDirection[columnIndex] = direction;
    
    rows.sort((a, b) => {
        let aText = a.cells[columnIndex].textContent.trim();
        let bText = b.cells[columnIndex].textContent.trim();
        
        // For value column, extract numeric value
        if (columnIndex === 3) {
            aText = aText.replace(/[^0-9.-]/g, '');
            bText = bText.replace(/[^0-9.-]/g, '');
        }
        
        // Try numeric comparison
        const aNum = parseFloat(aText);
        const bNum = parseFloat(bText);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return direction === 'asc' ? aNum - bNum : bNum - aNum;
        }
        
        // String comparison
        return direction === 'asc' 
            ? aText.localeCompare(bText)
            : bText.localeCompare(aText);
    });
    
    rows.forEach(row => tbody.appendChild(row));
}



// Real-time updates if Arduino is connected
<?php if ($arduinoHealthy): ?>
let realTimeUpdateInterval;
let lastRealTimeUpdate = 0;

function updateRealTimeData() {
    fetch('arduino_sync.php?action=get_all')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                console.log('Real-time Arduino data:', data.data);
                
                // Update real-time indicators
                updateRealTimeIndicators(data.data);
                
                // Update timestamp
                const timestampElement = document.getElementById('realtime-timestamp');
                if (timestampElement) {
                    timestampElement.textContent = 'Last update: ' + new Date().toLocaleTimeString();
                }
                
                lastRealTimeUpdate = Date.now();
            }
        })
        .catch(error => {
            console.log('Arduino update failed:', error);
            
            // Show connection error
            const timestampElement = document.getElementById('realtime-timestamp');
            if (timestampElement) {
                timestampElement.textContent = 'Connection error - ' + new Date().toLocaleTimeString();
                timestampElement.className = 'text-xs text-red-500 dark:text-red-400';
            }
        });
}

function updateRealTimeIndicators(sensorData) {
    // Update real-time values in the statistics cards
    Object.keys(sensorData).forEach(sensorType => {
        const data = sensorData[sensorType];
        if (data.value !== undefined) {
            // Find and update the corresponding stat card
            const statCards = document.querySelectorAll('.bg-white.dark\\:bg-gray-800.border');
            statCards.forEach(card => {
                const typeText = card.textContent.toLowerCase();
                if (typeText.includes(sensorType.replace('_', ' '))) {
                    // Update the average value display
                    const avgElement = card.querySelector('.font-bold.text-gray-900.dark\\:text-white');
                    if (avgElement) {
                        const unit = sensorType === 'temperature' ? '°C' : '%';
                        avgElement.innerHTML = `${parseFloat(data.value).toFixed(1)}${unit} <span class="text-xs text-green-500">●</span>`;
                    }
                }
            });
        }
    });
}

// Get sensor logging interval from PHP (for violation counting)
const loggingInterval = <?php echo $sensorInterval; ?> * 1000; // Convert to milliseconds

// Real-time Arduino updates (every 5 seconds for dashboard responsiveness)
const realtimeInterval = 5000; // 5 seconds for real-time display

// Start real-time updates for dashboard (fast updates)
realTimeUpdateInterval = setInterval(updateRealTimeData, realtimeInterval);
updateRealTimeData(); // Initial update

// Automatic background sync - runs the same logic as manual_sync_test.php
async function automaticBackgroundSync() {
    try {
        // This will trigger the same process as manual_sync_test.php
        // It reads Arduino data and processes it through the plant monitor
        const response = await fetch('auto_sync_background.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('✓ Auto-sync:', data.consecutive_violations + '/' + data.warning_trigger, 
                        'violations', data.notification_triggered ? '🔔 NOTIFICATION!' : '');
            
            // Update violation counter in real-time
            const violationCountElement = document.getElementById('violation-count');
            if (violationCountElement) {
                violationCountElement.textContent = data.consecutive_violations;
            }
            
            // Update counter color
            const violationCounterElement = document.getElementById('violation-counter');
            if (violationCounterElement) {
                const warningTrigger = data.warning_trigger;
                if (data.consecutive_violations >= warningTrigger) {
                    violationCounterElement.className = 'px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs rounded-full font-medium animate-pulse';
                } else if (data.consecutive_violations > 0) {
                    violationCounterElement.className = 'px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 text-xs rounded-full font-medium';
                } else {
                    violationCounterElement.className = 'px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs rounded-full font-medium';
                }
            }
            
            // Show notification popup if triggered
            if (data.notification_triggered && data.violations) {
                data.violations.forEach(violation => {
                    showPlantNotification(
                        data.plant_name,
                        violation.sensor,
                        violation.status,
                        violation.current,
                        violation.range
                    );
                });
            }
        }
    } catch (error) {
        console.error('Auto-sync error:', error);
    }
}

// Start automatic background sync based on LOGGING interval (for violation counting)
// This is the interval set in settings.php (5 min, 15 min, 1 hour, etc.)
// This function handles BOTH database logging AND violation counting
setInterval(automaticBackgroundSync, loggingInterval);
// NOTE: No initial sync on page load - waits for first interval to prevent counting on refresh

console.log('🌱 Plant Monitoring System Started');
console.log('📊 Real-time updates: every ' + (realtimeInterval / 1000) + ' seconds');
console.log('⚠️ Violation checks: every ' + (loggingInterval / 1000) + ' seconds (' + (loggingInterval / 60000) + ' minutes)');
console.log('💾 Database logging: every ' + (loggingInterval / 60000) + ' minutes');

// Add real-time status indicator
document.addEventListener('DOMContentLoaded', function() {
    // Add real-time indicator to the page
    const filtersDiv = document.querySelector('.bg-white.dark\\:bg-gray-800.border.border-gray-200.dark\\:border-gray-700.rounded-lg.p-3.mb-3');
    if (filtersDiv) {
        const realTimeIndicator = document.createElement('div');
        realTimeIndicator.className = 'flex items-center justify-between mt-2 pt-2 border-t border-gray-200 dark:border-gray-600';
        realTimeIndicator.innerHTML = `
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-xs text-gray-600 dark:text-gray-400">Real-time Arduino data</span>
            </div>
            <span id="realtime-timestamp" class="text-xs text-gray-500 dark:text-gray-400">Connecting...</span>
        `;
        filtersDiv.appendChild(realTimeIndicator);
    }
    
});

// ============================================================================
// PLANT THRESHOLD MONITORING
// ============================================================================

/**
 * Refresh scan interval display from settings
 */
async function refreshScanInterval() {
    try {
        const response = await fetch('api/get_sensor_interval.php');
        const data = await response.json();
        
        if (data.success) {
            const intervalSeconds = data.interval_seconds;
            const displayText = intervalSeconds >= 60 ? Math.round(intervalSeconds / 60) + 'm' : intervalSeconds + 's';
            
            const displayElement = document.getElementById('scan-interval-display');
            if (displayElement) {
                displayElement.textContent = displayText;
            }
        }
    } catch (error) {
        console.error('Error refreshing scan interval:', error);
    }
}

/**
 * Check plant thresholds and show notifications
 */
async function checkPlantThresholds() {
    try {
        const response = await fetch('check_plant_thresholds.php');
        const data = await response.json();
        
        if (data.success) {
            // Update violation counter
            const violationCountElement = document.getElementById('violation-count');
            const violationCounterElement = document.getElementById('violation-counter');
            
            if (violationCountElement && violationCounterElement) {
                const warningLevel = data.warning_level || 0;
                
                // Extract the trigger value from the counter text (e.g., "5 / 5" -> 5)
                const counterText = violationCounterElement.textContent;
                const triggerMatch = counterText.match(/\/\s*(\d+)/);
                const warningTrigger = triggerMatch ? parseInt(triggerMatch[1]) : 5;
                
                // Update the count while preserving the trigger display
                violationCountElement.textContent = warningLevel;
                
                // Change color based on warning level
                if (warningLevel >= warningTrigger) {
                    violationCounterElement.className = 'px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs rounded-full font-medium animate-pulse';
                } else if (warningLevel > 0) {
                    violationCounterElement.className = 'px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 text-xs rounded-full font-medium';
                } else {
                    violationCounterElement.className = 'px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs rounded-full font-medium';
                }
                
                // Log for debugging
                console.log(`Violation Check: ${warningLevel}/${warningTrigger} violations`, data.violations);
            }
            
            // Show notification if triggered
            if (data.notification_triggered) {
                console.log('🔔 NOTIFICATION TRIGGERED!', data);
                data.violations.forEach(violation => {
                    showPlantNotification(
                        data.plant,
                        violation.sensor,
                        violation.status,
                        violation.current,
                        violation.range
                    );
                });
            }
        } else {
            console.log('Threshold check:', data.message);
        }
    } catch (error) {
        console.error('Error checking plant thresholds:', error);
    }
}

/**
 * Show plant threshold notification
 */
function showPlantNotification(plantName, sensor, status, currentValue, range) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-3 rounded-lg shadow-lg z-50 max-w-md';
    notification.innerHTML = `
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-2xl"></i>
            <div>
                <div class="font-bold mb-1">⚠️ ${plantName} Alert</div>
                <div class="text-sm">
                    <strong>${sensor}:</strong> ${status}<br>
                    Current: ${currentValue} | Required: ${range}
                </div>
                <div class="text-xs mt-2 opacity-90">
                    <i class="fas fa-lightbulb mr-1"></i>Check notifications page for suggested actions
                </div>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    document.body.appendChild(notification);
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
        notification.remove();
    }, 10000);
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
        warning: 'bg-yellow-500'
    };
    
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-4 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

/**
 * Reset violation counter with countdown
 */
async function resetViolations() {
    if (!confirm('Reset violation counter to 0? This will clear the consecutive violation count.')) {
        return;
    }
    
    // Show countdown notification
    const countdownNotification = document.createElement('div');
    countdownNotification.className = 'fixed top-4 right-4 bg-yellow-500 text-white px-6 py-4 rounded-lg shadow-lg z-50';
    countdownNotification.innerHTML = `
        <div class="flex items-center gap-3">
            <i class="fas fa-hourglass-half text-2xl"></i>
            <div>
                <div class="font-bold">Resetting in <span id="countdown">3</span> seconds...</div>
                <div class="text-sm opacity-90">Click anywhere to cancel</div>
            </div>
        </div>
    `;
    document.body.appendChild(countdownNotification);
    
    let cancelled = false;
    
    // Cancel on click
    countdownNotification.onclick = () => {
        cancelled = true;
        countdownNotification.remove();
        showNotification('Reset cancelled', 'info');
    };
    
    // Countdown from 3 to 1
    for (let i = 3; i > 0; i--) {
        if (cancelled) return;
        
        const countdownElement = document.getElementById('countdown');
        if (countdownElement) {
            countdownElement.textContent = i;
        }
        
        await new Promise(resolve => setTimeout(resolve, 1000));
    }
    
    if (cancelled) return;
    
    countdownNotification.remove();
    
    // Proceed with reset
    try {
        const response = await fetch('reset_plant_violations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('✓ Violation counter reset successfully', 'success');
            
            // Update UI
            const violationCountElement = document.getElementById('violation-count');
            const violationCounterElement = document.getElementById('violation-counter');
            
            if (violationCountElement) {
                violationCountElement.textContent = '0';
            }
            
            if (violationCounterElement) {
                violationCounterElement.className = 'px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs rounded-full font-medium';
            }
        } else {
            showNotification('Failed to reset: ' + data.message, 'error');
        }
    } catch (error) {
        showNotification('Error resetting violations: ' + error.message, 'error');
    }
}

<?php endif; ?>

/**
 * Arduino Service Status Functions
 */
let arduinoStatusCheckInterval = null;

async function checkArduinoStatus() {
    const indicator = document.getElementById('arduino-status-indicator');
    const statusCard = document.getElementById('arduino-status-card');
    const offlineCard = document.getElementById('arduino-offline-card');
    const errorCard = document.getElementById('arduino-error-card');
    
    if (!indicator) return;
    
    // Show checking state
    indicator.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>CHECKING...';
    indicator.className = 'px-3 py-1 bg-gray-400 text-white text-xs font-medium rounded-full';
    
    try {
        // Use the ngrok endpoint to check Arduino service
        const response = await fetch('api/get_sensor_data_ngrok.php', {
            method: 'GET',
            headers: {'Accept': 'application/json'}
        });
        
        const data = await response.json();
        
        if (data.success && data.data) {
            // Service is online
            indicator.innerHTML = '<i class="fas fa-check-circle mr-1"></i>ONLINE';
            indicator.className = 'px-3 py-1 bg-green-600 text-white text-xs font-medium rounded-full';
            
            // Show status card, hide offline/error cards
            statusCard.classList.remove('hidden');
            offlineCard.classList.add('hidden');
            errorCard.classList.add('hidden');
            
            // Update status card with live data
            updateArduinoStatusCard(data.data);
            
        } else {
            // Service is offline
            indicator.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>OFFLINE';
            indicator.className = 'px-3 py-1 bg-red-600 text-white text-xs font-medium rounded-full';
            
            // Show offline card, hide status/error cards
            statusCard.classList.add('hidden');
            offlineCard.classList.remove('hidden');
            errorCard.classList.add('hidden');
        }
    } catch (error) {
        console.error('Error checking Arduino status:', error);
        
        // Show error state
        indicator.innerHTML = '<i class="fas fa-question-circle mr-1"></i>ERROR';
        indicator.className = 'px-3 py-1 bg-gray-600 text-white text-xs font-medium rounded-full';
        
        // Show error card, hide status/offline cards
        statusCard.classList.add('hidden');
        offlineCard.classList.add('hidden');
        errorCard.classList.remove('hidden');
    }
}

function updateArduinoStatusCard(sensorData) {
    const statusCard = document.getElementById('arduino-status-card');
    if (!statusCard) return;
    
    let sensorHtml = '';
    if (sensorData.temperature) {
        const tempValue = sensorData.temperature.value ?? sensorData.temperature;
        sensorHtml += `
            <div class="text-center">
                <div class="text-gray-500 dark:text-gray-400">Temp</div>
                <div class="font-bold text-gray-900 dark:text-white">${parseFloat(tempValue || 0).toFixed(1)}°C</div>
            </div>
        `;
    }
    if (sensorData.humidity) {
        const humValue = sensorData.humidity.value ?? sensorData.humidity;
        sensorHtml += `
            <div class="text-center">
                <div class="text-gray-500 dark:text-gray-400">Humidity</div>
                <div class="font-bold text-gray-900 dark:text-white">${parseFloat(humValue || 0).toFixed(1)}%</div>
            </div>
        `;
    }
    if (sensorData.soil_moisture) {
        const soilValue = sensorData.soil_moisture.value ?? sensorData.soil_moisture;
        sensorHtml += `
            <div class="text-center">
                <div class="text-gray-500 dark:text-gray-400">Soil</div>
                <div class="font-bold text-gray-900 dark:text-white">${parseFloat(soilValue || 0).toFixed(1)}%</div>
            </div>
        `;
    }
    
    statusCard.innerHTML = `
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-lg"></i>
                </div>
                <div class="flex-1">
                    <div class="font-medium text-gray-900 dark:text-white text-sm">Service Running</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Arduino sensor bridge is online and collecting data</div>
                </div>
                ${sensorHtml ? `<div class="flex gap-4 text-xs">${sensorHtml}</div>` : ''}
            </div>
        </div>
    `;
}

function refreshArduinoStatus() {
    showNotification('Refreshing Arduino service status...', 'info');
    checkArduinoStatus();
}

// Check Arduino status on page load and periodically
document.addEventListener('DOMContentLoaded', function() {
    // Initial check after a short delay
    setTimeout(checkArduinoStatus, 1000);
    
    // Check every 30 seconds
    arduinoStatusCheckInterval = setInterval(checkArduinoStatus, 30000);
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (arduinoStatusCheckInterval) {
        clearInterval(arduinoStatusCheckInterval);
    }
});
</script>

<?php
include 'includes/footer.php';
?>
</main>
</div>
</div>
