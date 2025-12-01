<?php
// Start session
session_start();

// Simple authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/language.php';
require_once 'includes/weather-api.php';
require_once 'includes/arduino-api.php';
require_once 'includes/pest-config-helper.php';

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

// Auto-sync from ngrok to database (for InfinityFree)
// This pulls live data from ngrok and saves to database for historical records
try {
    $syncUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/sync_from_ngrok.php';
    
    $ch = curl_init($syncUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
} catch (Exception $e) {
    // Silently fail - sync is optional
}

// Get sensor logging interval from settings for chart refresh
$arduinoForInterval = new ArduinoBridge();
$intervalSetting = $arduinoForInterval->getLoggingIntervalSetting();
$chartRefreshIntervalMs = ($intervalSetting['interval_minutes'] ?? 0.0833) * 60 * 1000; // Convert minutes to milliseconds
// Minimum 5 seconds (5000ms) for chart refresh
if ($chartRefreshIntervalMs < 5000) {
    $chartRefreshIntervalMs = 5000;
}

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Get weather data
$weatherData = getWeatherData();

// Helper functions are now centralized in config/database.php
// getTimeAgo() is defined in includes/notifications.php

// Get current sensor readings with dynamic remarks
function getCurrentSensorReadings()
{
    try {
        require_once 'includes/arduino-api.php';
        $arduino = new ArduinoBridge();

        // Get real-time Arduino data
        $arduinoData = null;
        if ($arduino->isHealthy()) {
            $arduinoData = $arduino->getAllSensorData();
        }

        // Get thresholds from database (from sensors table)
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
                'min' => $row['alert_threshold_min'] ?? 20,
                'max' => $row['alert_threshold_max'] ?? 28,
                'unit' => $row['sensor_type'] === 'temperature' ? '°C' : '%'
            ];
        }

        // Set defaults if not found in database
        if (!isset($thresholds['temperature'])) {
            $thresholds['temperature'] = ['min' => 20, 'max' => 28, 'unit' => '°C'];
        }
        if (!isset($thresholds['humidity'])) {
            $thresholds['humidity'] = ['min' => 60, 'max' => 80, 'unit' => '%'];
        }
        if (!isset($thresholds['soil_moisture'])) {
            $thresholds['soil_moisture'] = ['min' => 40, 'max' => 60, 'unit' => '%'];
        }

        $readings = [];

        foreach ($thresholds as $type => $threshold) {
            $value = null;
            $status = 'offline';
            $remark = 'No data available';

            if ($arduinoData && isset($arduinoData[$type]['value'])) {
                $value = $arduinoData[$type]['value'];
                $status = $arduinoData[$type]['status'] ?? 'online';

                // Generate dynamic remarks based on thresholds
                $remark = generateSensorRemark($type, $value, $threshold);
            }

            $readings[] = [
                'sensor_type' => $type,
                'avg_value' => $value ?? 0,
                'unit' => $threshold['unit'],
                'status' => $status,
                'remark' => $remark,
                'threshold_min' => $threshold['min'],
                'threshold_max' => $threshold['max'],
                'weekly_data' => [0], // Placeholder for chart
                'trend' => 'stable'
            ];
        }

        return $readings;
    } catch (Exception $e) {
        error_log("Error getting sensor readings: " . $e->getMessage());
        // Return offline data (no simulated values)
        return [
            [
                'sensor_type' => 'temperature',
                'avg_value' => 0,
                'unit' => '°C',
                'status' => 'offline',
                'remark' => 'Arduino not connected - No data available',
                'threshold_min' => 20,
                'threshold_max' => 28,
                'weekly_data' => [0],
                'trend' => 'stable'
            ],
            [
                'sensor_type' => 'humidity',
                'avg_value' => 0,
                'unit' => '%',
                'status' => 'offline',
                'remark' => 'Arduino not connected - No data available',
                'threshold_min' => 60,
                'threshold_max' => 80,
                'weekly_data' => [0],
                'trend' => 'stable'
            ],
            [
                'sensor_type' => 'soil_moisture',
                'avg_value' => 0,
                'unit' => '%',
                'status' => 'offline',
                'remark' => 'Arduino not connected - No data available',
                'threshold_min' => 40,
                'threshold_max' => 60,
                'weekly_data' => [0],
                'trend' => 'stable'
            ]
        ];
    }
}

// Generate dynamic sensor remarks based on thresholds
function generateSensorRemark($type, $value, $threshold)
{
    $min = $threshold['min'];
    $max = $threshold['max'];
    $unit = $threshold['unit'];

    $remarks = [
        'temperature' => [
            'optimal' => "Temperature is optimal at {$value}{$unit}. Perfect for crop growth.",
            'high' => "Temperature is high at {$value}{$unit}. Consider ventilation or shading. Optimal: {$min}-{$max}{$unit}.",
            'low' => "Temperature is low at {$value}{$unit}. Consider heating or greenhouse cover. Optimal: {$min}-{$max}{$unit}.",
            'critical_high' => "⚠️ CRITICAL: Temperature too high at {$value}{$unit}! Immediate action needed. Risk of heat stress.",
            'critical_low' => "⚠️ CRITICAL: Temperature too low at {$value}{$unit}! Immediate action needed. Risk of frost damage."
        ],
        'humidity' => [
            'optimal' => "Humidity is optimal at {$value}{$unit}. Good conditions for plant health.",
            'high' => "Humidity is high at {$value}{$unit}. Risk of fungal diseases. Increase ventilation. Optimal: {$min}-{$max}{$unit}.",
            'low' => "Humidity is low at {$value}{$unit}. Plants may experience water stress. Consider misting. Optimal: {$min}-{$max}{$unit}.",
            'critical_high' => "⚠️ CRITICAL: Humidity too high at {$value}{$unit}! High risk of mold and fungal diseases.",
            'critical_low' => "⚠️ CRITICAL: Humidity too low at {$value}{$unit}! Severe water stress risk."
        ],
        'soil_moisture' => [
            'optimal' => "Soil moisture is optimal at {$value}{$unit}. Perfect for crop growth.",
            'high' => "Soil is very wet at {$value}{$unit}. Risk of overwatering. Reduce irrigation.",
            'low' => "Soil is dry at {$value}{$unit}. Plants need watering soon.",
            'critical_high' => "⚠️ CRITICAL: Soil is saturated at {$value}{$unit}! Stop irrigation immediately. Risk of root rot and flooding.",
            'critical_low' => "⚠️ CRITICAL: Soil is very dry at {$value}{$unit}! Irrigate immediately to prevent wilting."
        ]
    ];

    // Determine status based on sensor type
    if ($type === 'soil_moisture') {
        // Soil moisture: 0% = very dry, 100% = very wet
        if ($value >= 41 && $value <= 60) {
            return $remarks[$type]['optimal']; // Moderate - ideal range
        } elseif ($value >= 81) {
            return $remarks[$type]['critical_high']; // Very wet/saturated
        } elseif ($value >= 61) {
            return $remarks[$type]['high']; // Moist - wet but not waterlogged
        } elseif ($value <= 20) {
            return $remarks[$type]['critical_low']; // Very dry - needs immediate watering
        } else {
            return $remarks[$type]['low']; // Dry - needs watering soon
        }
    } else {
        // Temperature and humidity use original logic
        if ($value >= $min && $value <= $max) {
            return $remarks[$type]['optimal'];
        } elseif ($value > $max) {
            // High
            if ($value > $max + ($max - $min) * 0.5) {
                return $remarks[$type]['critical_high'];
            }
            return $remarks[$type]['high'];
        } else {
            // Low
            if ($value < $min - ($max - $min) * 0.5) {
                return $remarks[$type]['critical_low'];
            }
            return $remarks[$type]['low'];
        }
    }
}

function getRecentPestAlerts()
{
    try {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->query("
            SELECT 
                id,
                pest_type,
                severity,
                confidence_score,
                detected_at,
                is_read
            FROM pest_alerts 
            ORDER BY detected_at DESC 
            LIMIT 5
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching pest alerts: " . $e->getMessage());
        return [];
    }
}

function getDailyStatistics()
{
    try {
        $pdo = getDatabaseConnection();

        // Get alert statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_alerts,
                COUNT(CASE WHEN is_read = FALSE THEN 1 END) as new_alerts,
                COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_alerts,
                COUNT(CASE WHEN DATE(detected_at) = CURDATE() THEN 1 END) as today_alerts
            FROM pest_alerts
        ");
        $alertStats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculate weekly reports (based on days with pest alerts this week)
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT DATE(detected_at)) as reports_generated
            FROM pest_alerts
            WHERE detected_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $reportStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $weeklyReports = max(1, $reportStats['reports_generated']); // At least 1 report

        return [
            'daily_averages' => [
                ['sensor_type' => 'temperature', 'daily_avg' => 24.5, 'unit' => '°C'],
                ['sensor_type' => 'humidity', 'daily_avg' => 68.2, 'unit' => '%'],
                ['sensor_type' => 'soil_moisture', 'daily_avg' => 45.8, 'unit' => '%']
            ],
            'alert_stats' => $alertStats,
            'sensor_stats' => [
                'total_sensors' => 9,
                'online_sensors' => 9,
                'offline_sensors' => 0
            ],
            'weekly_reports' => $weeklyReports
        ];
    } catch (Exception $e) {
        error_log("Error fetching daily statistics: " . $e->getMessage());
        return [
            'daily_averages' => [
                ['sensor_type' => 'temperature', 'daily_avg' => 24.5, 'unit' => '°C'],
                ['sensor_type' => 'humidity', 'daily_avg' => 68.2, 'unit' => '%'],
                ['sensor_type' => 'soil_moisture', 'daily_avg' => 45.8, 'unit' => '%']
            ],
            'alert_stats' => [
                'total_alerts' => 0,
                'new_alerts' => 0,
                'critical_alerts' => 0,
                'today_alerts' => 0
            ],
            'sensor_stats' => [
                'total_sensors' => 9,
                'online_sensors' => 9,
                'offline_sensors' => 0
            ],
            'weekly_reports' => 7 // Default to 7 (one per day)
        ];
    }
}

// Initialize Arduino bridge for real-time data
$arduino = new ArduinoBridge();
$arduinoHealthy = false;
$arduinoData = null;

if ($arduino->isHealthy()) {
    $arduinoHealthy = true;
    $arduinoData = $arduino->getAllSensorData();

    // Auto-sync all sensor data to database
    if ($arduinoData) {
        foreach ($arduinoData as $sensorType => $data) {
            if (isset($data['value']) && $data['value'] !== null) {
                $unit = ($sensorType === 'temperature') ? '°C' : '%';
                $arduino->storeSensorReading($sensorType, $data['value'], $unit);
            }
        }
    }
}

// Get data for dashboard
$sensorReadings = getCurrentSensorReadings();
$recentAlerts = getRecentPestAlerts();
$dailyStats = getDailyStatistics();
$pestStats = getPestStatistics();

// Set page title for header component
$pageTitle = 'Dashboard - IoT Farm Monitoring System';

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

<!-- Dashboard Content -->
<div class="p-4 max-w-7xl mx-auto">

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
        <!-- Arduino Temperature -->
        <?php
        $tempReading = null;
        foreach ($sensorReadings as $reading) {
            if ($reading['sensor_type'] === 'temperature') {
                $tempReading = $reading;
                break;
            }
        }
        ?>
        <div class="bg-red-600 text-white rounded-xl p-3" id="temperature-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-white/80 text-xs font-medium">Temperature</h3>
                <i class="fas fa-thermometer-half text-xs"></i>
            </div>
            <?php if ($tempReading && $tempReading['avg_value'] > 0): ?>
                <div class="text-xl font-bold" id="temperature-value">
                    <?php echo number_format($tempReading['avg_value'], 1); ?>°C
                </div>
                <div class="text-white/90 text-xs mt-1" style="line-height: 1.2;">
                    <?php
                    // Show short remark
                    if (strpos($tempReading['remark'], 'optimal') !== false) {
                        echo '✓ Optimal';
                    } elseif (strpos($tempReading['remark'], 'CRITICAL') !== false) {
                        echo '⚠ Critical!';
                    } elseif (strpos($tempReading['remark'], 'high') !== false) {
                        echo '↑ Too High';
                    } elseif (strpos($tempReading['remark'], 'low') !== false) {
                        echo '↓ Too Low';
                    } else {
                        echo $tempReading['status'] === 'online' ? 'Live' : 'Database';
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="text-xl font-bold text-white/60">--</div>
                <div class="text-white/60 text-xs">Offline</div>
            <?php endif; ?>
        </div>

        <!-- Arduino Humidity -->
        <?php
        $humReading = null;
        foreach ($sensorReadings as $reading) {
            if ($reading['sensor_type'] === 'humidity') {
                $humReading = $reading;
                break;
            }
        }
        ?>
        <div class="bg-blue-600 text-white rounded-xl p-3" id="humidity-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-white/80 text-xs font-medium">Humidity</h3>
                <i class="fas fa-tint text-xs"></i>
            </div>
            <?php if ($humReading && $humReading['avg_value'] > 0): ?>
                <div class="text-xl font-bold" id="humidity-value">
                    <?php echo number_format($humReading['avg_value'], 1); ?>%
                </div>
                <div class="text-white/90 text-xs mt-1" style="line-height: 1.2;">
                    <?php
                    // Show short remark
                    if (strpos($humReading['remark'], 'optimal') !== false) {
                        echo '✓ Optimal';
                    } elseif (strpos($humReading['remark'], 'CRITICAL') !== false) {
                        echo '⚠ Critical!';
                    } elseif (strpos($humReading['remark'], 'high') !== false) {
                        echo '↑ Too High';
                    } elseif (strpos($humReading['remark'], 'low') !== false) {
                        echo '↓ Too Low';
                    } else {
                        echo $humReading['status'] === 'online' ? 'Live' : 'Database';
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="text-xl font-bold text-white/60">--</div>
                <div class="text-white/60 text-xs">Offline</div>
            <?php endif; ?>
        </div>

        <!-- Arduino Soil Moisture -->
        <?php
        $soilReading = null;
        foreach ($sensorReadings as $reading) {
            if ($reading['sensor_type'] === 'soil_moisture') {
                $soilReading = $reading;
                break;
            }
        }
        ?>
        <div class="bg-green-600 text-white rounded-xl p-3" id="soil-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-white/80 text-xs font-medium">Soil Moisture</h3>
                <i class="fas fa-seedling text-xs"></i>
            </div>
            <?php if ($soilReading && $soilReading['avg_value'] > 0): ?>
                <div class="text-xl font-bold" id="soil-value">
                    <?php echo number_format($soilReading['avg_value'], 1); ?>%
                </div>
                <div class="text-white/90 text-xs mt-1" style="line-height: 1.2;">
                    <?php
                    // Show short remark for soil moisture
                    if (strpos($soilReading['remark'], 'optimal') !== false) {
                        echo '✓ Optimal';
                    } elseif (strpos($soilReading['remark'], 'CRITICAL') !== false) {
                        if (strpos($soilReading['remark'], 'saturated') !== false) {
                            echo '⚠ Flooded!';
                        } else {
                            echo '⚠ Very Dry!';
                        }
                    } elseif (strpos($soilReading['remark'], 'very wet') !== false) {
                        echo '💧 Very Wet';
                    } elseif (strpos($soilReading['remark'], 'dry') !== false) {
                        echo '🏜️ Dry';
                    } else {
                        echo $soilReading['status'] === 'online' ? 'Live' : 'Database';
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="text-xl font-bold text-white/60">--</div>
                <div class="text-white/60 text-xs">Offline</div>
            <?php endif; ?>
        </div>

        <!-- Reports -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 dark:text-gray-400 text-xs font-medium" data-translate="weekly_reports_generated">Weekly Reports</h3>
                <i class="fas fa-chart-bar text-blue-600 dark:text-blue-400 text-xs"></i>
            </div>
            <div class="text-xl font-bold text-gray-900 dark:text-white"><?php echo $dailyStats['weekly_reports']; ?></div>
            <div class="text-gray-500 text-xs">This week</div>
        </div>


        <!-- Live Time -->
        <div class="bg-gray-900 dark:bg-white border border-gray-800 dark:border-gray-200 rounded-xl p-3 relative overflow-hidden">
            <div class="text-center">
                <div class="text-3xl font-bold text-white dark:text-gray-900 leading-none mb-1">
                    <span id="live-time"><?php echo date('H:i'); ?></span>
                </div>
                <div class="text-gray-300 dark:text-gray-600 text-xs font-medium">
                    <span id="live-date"><?php echo date('D, j M'); ?></span>
                </div>
            </div>
        </div>

        <script>
            // Update time and date every second with cool animation
            function updateTimeAndDate() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', {
                    hour12: false,
                    hour: '2-digit',
                    minute: '2-digit'
                });
                const dayString = now.toLocaleDateString('en-US', {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short'
                });

                const timeElement = document.getElementById('live-time');
                const dateElement = document.getElementById('live-date');

                if (timeElement) {
                    timeElement.style.transform = 'scale(1.1)';
                    timeElement.textContent = timeString;
                    setTimeout(() => {
                        timeElement.style.transform = 'scale(1)';
                    }, 200);
                }

                if (dateElement) {
                    dateElement.textContent = dayString;
                }
            }

            // Update immediately and then every second
            updateTimeAndDate();
            setInterval(updateTimeAndDate, 1000);
        </script>
    </div>

    <!-- Sensor Logging Interval Display -->
    <?php include 'includes/sensor-interval-display.php'; ?>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Sensor Analytics - Improved -->
            <div class="bg-gray-900 dark:bg-white border border-gray-800 dark:border-gray-200 rounded-xl p-4">
                <!-- Header -->
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-white dark:text-gray-900">Sensor Data</h3>
                        <span id="arduino-chart-status" class="w-2 h-2 bg-gray-400 rounded-full" title="Arduino Status"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <select id="sensor-type-select" class="bg-gray-800 dark:bg-gray-100 text-white dark:text-gray-900 text-xs border border-gray-700 dark:border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="temperature">🌡️ Temperature</option>
                            <option value="humidity">💧 Humidity</option>
                            <option value="soil_moisture">🌱 Soil Moisture</option>
                        </select>
                    </div>
                </div>
                
                <!-- Current Value Display -->
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <span id="current-value-display" class="text-3xl font-bold text-white dark:text-gray-900">--</span>
                        <div class="flex flex-col">
                            <span id="trend-indicator" class="text-xs text-gray-400 dark:text-gray-500">--</span>
                            <span id="status-badge" class="text-xs px-2 py-0.5 rounded-full bg-gray-700 dark:bg-gray-200 text-gray-300 dark:text-gray-600">Loading...</span>
                        </div>
                    </div>
                    <div class="text-right text-xs text-gray-400 dark:text-gray-500">
                        <div>Min: <span id="stat-min" class="text-blue-400 dark:text-blue-600 font-medium">--</span></div>
                        <div>Max: <span id="stat-max" class="text-red-400 dark:text-red-600 font-medium">--</span></div>
                        <div>Avg: <span id="stat-avg" class="text-green-400 dark:text-green-600 font-medium">--</span></div>
                    </div>
                </div>
                
                <!-- Chart Area with Y-axis labels -->
                <div class="flex gap-2">
                    <!-- Y-axis labels -->
                    <div class="flex flex-col justify-between text-xs text-gray-500 dark:text-gray-400 py-1" style="width: 30px;">
                        <span id="y-max">100</span>
                        <span id="y-mid">50</span>
                        <span id="y-min">0</span>
                    </div>
                    <!-- Chart container -->
                    <div class="flex-1 relative" style="height: 120px;">
                        <!-- Threshold lines -->
                        <div id="threshold-max-line" class="absolute w-full border-t border-dashed border-red-500/50 z-10" style="top: 20%;"></div>
                        <div id="threshold-min-line" class="absolute w-full border-t border-dashed border-blue-500/50 z-10" style="top: 80%;"></div>
                        <!-- Chart bars -->
                        <div class="flex items-end justify-between h-full gap-1" id="chart-container">
                            <!-- Chart will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- X-axis time labels -->
                <div class="flex gap-2 mt-1">
                    <div style="width: 30px;"></div>
                    <div class="flex-1 flex justify-between text-xs text-gray-500 dark:text-gray-400" id="time-labels-container">
                        <!-- Time labels will be populated by JavaScript -->
                    </div>
                </div>
                
                <!-- Footer with legend -->
                <div class="flex items-center justify-between mt-3 pt-2 border-t border-gray-700 dark:border-gray-200">
                    <div class="flex items-center gap-4 text-xs text-gray-400 dark:text-gray-500">
                        <div class="flex items-center gap-1">
                            <span class="w-3 h-0.5 bg-red-500/50"></span>
                            <span>Max threshold</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-3 h-0.5 bg-blue-500/50"></span>
                            <span>Min threshold</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-gray-400 dark:text-gray-500">Live Data</span>
                    </div>
                </div>
            </div>

            <script>
                // Real-time Arduino sensor data with initial values from PHP
                const sensorData = {
                    temperature: {
                        data: [null, null, null, null, null, null, null],
                        timeLabels: ['--', '--', '--', '--', '--', '--', 'Now'],
                        unit: '°C',
                        colors: {
                            normal: '#ef4444',
                            today: '#dc2626'
                        },
                        currentValue: <?php 
                            $tempReading = null;
                            foreach ($sensorReadings as $r) {
                                if ($r['sensor_type'] === 'temperature') { $tempReading = $r; break; }
                            }
                            echo ($tempReading && $tempReading['avg_value'] > 0) ? $tempReading['avg_value'] : 'null';
                        ?>,
                        thresholdMin: <?php echo $tempReading['threshold_min'] ?? 20; ?>,
                        thresholdMax: <?php echo $tempReading['threshold_max'] ?? 28; ?>
                    },
                    humidity: {
                        data: [null, null, null, null, null, null, null],
                        timeLabels: ['--', '--', '--', '--', '--', '--', 'Now'],
                        unit: '%',
                        colors: {
                            normal: '#3b82f6',
                            today: '#2563eb'
                        },
                        currentValue: <?php 
                            $humReading = null;
                            foreach ($sensorReadings as $r) {
                                if ($r['sensor_type'] === 'humidity') { $humReading = $r; break; }
                            }
                            echo ($humReading && $humReading['avg_value'] > 0) ? $humReading['avg_value'] : 'null';
                        ?>,
                        thresholdMin: <?php echo $humReading['threshold_min'] ?? 60; ?>,
                        thresholdMax: <?php echo $humReading['threshold_max'] ?? 80; ?>
                    },
                    soil_moisture: {
                        data: [null, null, null, null, null, null, null],
                        timeLabels: ['--', '--', '--', '--', '--', '--', 'Now'],
                        unit: '%',
                        colors: {
                            normal: '#10b981',
                            today: '#059669'
                        },
                        currentValue: <?php 
                            $soilReading = null;
                            foreach ($sensorReadings as $r) {
                                if ($r['sensor_type'] === 'soil_moisture') { $soilReading = $r; break; }
                            }
                            echo ($soilReading && $soilReading['avg_value'] > 0) ? $soilReading['avg_value'] : 'null';
                        ?>,
                        thresholdMin: <?php echo $soilReading['threshold_min'] ?? 40; ?>,
                        thresholdMax: <?php echo $soilReading['threshold_max'] ?? 60; ?>
                    }
                };

                // Fetch real Arduino data (live current value only)
                function fetchArduinoData() {
                    const statusIndicator = document.getElementById('arduino-chart-status');

                    fetch('arduino_sync.php?action=get_all')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data) {
                                // Update status indicator - Arduino connected
                                statusIndicator.className = 'w-2 h-2 bg-green-500 rounded-full animate-pulse';
                                statusIndicator.title = 'Arduino Connected - Live Data';

                                // Update current values only (last bar = "Now")
                                if (data.data.temperature && data.data.temperature.value !== null) {
                                    sensorData.temperature.currentValue = parseFloat(data.data.temperature.value);
                                    sensorData.temperature.data[6] = sensorData.temperature.currentValue; // Update "Now" bar
                                }

                                if (data.data.humidity && data.data.humidity.value !== null) {
                                    sensorData.humidity.currentValue = parseFloat(data.data.humidity.value);
                                    sensorData.humidity.data[6] = sensorData.humidity.currentValue; // Update "Now" bar
                                }

                                if (data.data.soil_moisture && data.data.soil_moisture.value !== null) {
                                    sensorData.soil_moisture.currentValue = parseFloat(data.data.soil_moisture.value);
                                    sensorData.soil_moisture.data[6] = sensorData.soil_moisture.currentValue; // Update "Now" bar
                                }

                                // Update the current chart
                                const currentSensorType = document.getElementById('sensor-type-select').value;
                                updateChart(currentSensorType);
                            } else {
                                // Arduino service not responding properly
                                statusIndicator.className = 'w-2 h-2 bg-yellow-500 rounded-full';
                                statusIndicator.title = 'Arduino Service Issues';
                                handleOfflineState();
                            }
                        })
                        .catch(error => {
                            console.log('Arduino data fetch failed:', error);
                            // Update status indicator - Arduino disconnected
                            statusIndicator.className = 'w-2 h-2 bg-red-500 rounded-full';
                            statusIndicator.title = 'Arduino Disconnected - Offline';
                            // Show offline state instead of simulated data
                            handleOfflineState();
                        });
                }

                // Handle offline state when Arduino is not available (no fake data)
                function handleOfflineState() {
                    // Set all values to null to indicate offline
                    sensorData.temperature.currentValue = null;
                    sensorData.humidity.currentValue = null;
                    sensorData.soil_moisture.currentValue = null;

                    // Clear data arrays (set to null for "no data" display)
                    Object.keys(sensorData).forEach(type => {
                        sensorData[type].data = [null, null, null, null, null, null, null];
                    });

                    const currentSensorType = document.getElementById('sensor-type-select').value;
                    updateChart(currentSensorType);
                }

                // Time labels are now fetched from database with actual timestamps

                function updateChart(sensorType) {
                    const data = sensorData[sensorType];
                    const chartContainer = document.getElementById('chart-container');
                    const timeLabelsContainer = document.getElementById('time-labels-container');
                    
                    // Get UI elements
                    const currentValueDisplay = document.getElementById('current-value-display');
                    const trendIndicator = document.getElementById('trend-indicator');
                    const statusBadge = document.getElementById('status-badge');
                    const statMin = document.getElementById('stat-min');
                    const statMax = document.getElementById('stat-max');
                    const statAvg = document.getElementById('stat-avg');
                    const yMax = document.getElementById('y-max');
                    const yMid = document.getElementById('y-mid');
                    const yMin = document.getElementById('y-min');
                    const thresholdMaxLine = document.getElementById('threshold-max-line');
                    const thresholdMinLine = document.getElementById('threshold-min-line');

                    // Get threshold values first (needed for calculations)
                    const threshMin = data.thresholdMin || 0;
                    const threshMax = data.thresholdMax || 100;

                    // Calculate statistics (filter out null and zero values)
                    const validData = data.data.filter(val => val !== null && val > 0);
                    const dataMin = validData.length > 0 ? Math.min(...validData) : null;
                    const dataMax = validData.length > 0 ? Math.max(...validData) : null;
                    const dataAvg = validData.length > 0 ? validData.reduce((a, b) => a + b, 0) / validData.length : null;
                    const currentVal = data.currentValue;
                    const isOffline = currentVal === null || currentVal === undefined;
                    
                    // Update display based on online/offline state
                    if (isOffline) {
                        // Offline state - show dashes
                        currentValueDisplay.textContent = '--';
                        currentValueDisplay.classList.add('text-gray-500');
                        trendIndicator.innerHTML = '<i class="fas fa-plug text-gray-400"></i> No connection';
                        trendIndicator.className = 'text-xs text-gray-400 dark:text-gray-500';
                        statusBadge.textContent = '⚠ Offline';
                        statusBadge.className = 'text-xs px-2 py-0.5 rounded-full bg-gray-500/20 text-gray-400';
                        statMin.textContent = '--';
                        statMax.textContent = '--';
                        statAvg.textContent = '--';
                    } else {
                        // Online state - show actual values
                        currentValueDisplay.textContent = currentVal.toFixed(1) + data.unit;
                        currentValueDisplay.classList.remove('text-gray-500');
                        
                        // Calculate trend (compare current to average of previous readings)
                        const prevAvg = data.data.slice(0, -1).filter(v => v !== null && v > 0);
                        const prevAvgVal = prevAvg.length > 0 ? prevAvg.reduce((a, b) => a + b, 0) / prevAvg.length : currentVal;
                        const trendDiff = currentVal - prevAvgVal;
                        
                        // Update trend indicator
                        if (Math.abs(trendDiff) < 0.5) {
                            trendIndicator.innerHTML = '<i class="fas fa-minus text-gray-400"></i> Stable';
                            trendIndicator.className = 'text-xs text-gray-400 dark:text-gray-500';
                        } else if (trendDiff > 0) {
                            trendIndicator.innerHTML = '<i class="fas fa-arrow-up text-red-400"></i> +' + trendDiff.toFixed(1);
                            trendIndicator.className = 'text-xs text-red-400';
                        } else {
                            trendIndicator.innerHTML = '<i class="fas fa-arrow-down text-blue-400"></i> ' + trendDiff.toFixed(1);
                            trendIndicator.className = 'text-xs text-blue-400';
                        }
                        
                        // Update status badge based on thresholds
                        if (currentVal >= threshMin && currentVal <= threshMax) {
                            statusBadge.textContent = '✓ Optimal';
                            statusBadge.className = 'text-xs px-2 py-0.5 rounded-full bg-green-500/20 text-green-400';
                        } else if (currentVal < threshMin - 10 || currentVal > threshMax + 10) {
                            statusBadge.textContent = '⚠ Critical';
                            statusBadge.className = 'text-xs px-2 py-0.5 rounded-full bg-red-500/20 text-red-400';
                        } else {
                            statusBadge.textContent = '! Warning';
                            statusBadge.className = 'text-xs px-2 py-0.5 rounded-full bg-yellow-500/20 text-yellow-400';
                        }
                        
                        // Update statistics
                        statMin.textContent = dataMin !== null ? dataMin.toFixed(1) + data.unit : '--';
                        statMax.textContent = dataMax !== null ? dataMax.toFixed(1) + data.unit : '--';
                        statAvg.textContent = dataAvg !== null ? dataAvg.toFixed(1) + data.unit : '--';
                    }
                    
                    // Calculate scale for chart
                    let scaleMin, scaleMax;
                    if (sensorType === 'temperature') {
                        scaleMin = Math.floor(Math.min(dataMin, threshMin) - 5);
                        scaleMax = Math.ceil(Math.max(dataMax, threshMax) + 5);
                    } else {
                        scaleMin = 0;
                        scaleMax = 100;
                    }
                    
                    // Update Y-axis labels
                    yMax.textContent = scaleMax;
                    yMid.textContent = Math.round((scaleMax + scaleMin) / 2);
                    yMin.textContent = scaleMin;
                    
                    // Position threshold lines
                    const threshMaxPercent = ((scaleMax - threshMax) / (scaleMax - scaleMin)) * 100;
                    const threshMinPercent = ((scaleMax - threshMin) / (scaleMax - scaleMin)) * 100;
                    thresholdMaxLine.style.top = threshMaxPercent + '%';
                    thresholdMinLine.style.top = threshMinPercent + '%';

                    // Clear existing chart
                    chartContainer.innerHTML = '';
                    timeLabelsContainer.innerHTML = '';

                    // Use time labels from data (actual logged times)
                    const timeLabels = data.timeLabels;
                    
                    const containerHeight = 120; // pixels
                    
                    // Create bars
                    data.data.forEach((value, index) => {
                        const isNow = index === 6;
                        const hasData = value !== null && value > 0;
                        
                        // Calculate bar height
                        let pixelHeight;
                        if (hasData) {
                            const heightPercent = ((value - scaleMin) / (scaleMax - scaleMin)) * 100;
                            const normalizedPercent = Math.max(Math.min(heightPercent, 100), 8);
                            pixelHeight = (normalizedPercent / 100) * containerHeight;
                        } else {
                            pixelHeight = 8; // Minimal height for "no data" indicator
                        }

                        // Bar container
                        const barContainer = document.createElement('div');
                        barContainer.className = 'flex flex-col items-center justify-end flex-1 relative group';
                        barContainer.style.height = '100%';

                        // Value label on hover
                        const valueLabel = document.createElement('div');
                        valueLabel.className = 'absolute -top-6 left-1/2 transform -translate-x-1/2 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800 text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-20';
                        valueLabel.textContent = hasData ? value.toFixed(1) + data.unit : 'No Data';

                        // Bar
                        const bar = document.createElement('div');
                        bar.className = 'w-6 rounded-t transition-all cursor-pointer hover:opacity-80';
                        bar.style.height = pixelHeight + 'px';
                        
                        // Color based on value vs thresholds (or gray for no data)
                        let barColor;
                        if (!hasData) {
                            barColor = '#4b5563'; // Gray for no data
                        } else if (value >= threshMin && value <= threshMax) {
                            barColor = isNow ? data.colors.today : data.colors.normal;
                        } else if (value < threshMin - 10 || value > threshMax + 10) {
                            barColor = '#ef4444'; // Red for critical
                        } else {
                            barColor = '#f59e0b'; // Yellow for warning
                        }
                        bar.style.backgroundColor = barColor;
                        
                        if (isNow && hasData) {
                            bar.style.boxShadow = '0 0 8px ' + barColor;
                        }

                        barContainer.appendChild(valueLabel);
                        barContainer.appendChild(bar);
                        chartContainer.appendChild(barContainer);
                        
                        // Time label
                        const timeLabel = document.createElement('span');
                        timeLabel.className = isNow ? 'font-bold text-green-400' : (hasData ? '' : 'text-gray-600');
                        timeLabel.textContent = timeLabels[index];
                        timeLabelsContainer.appendChild(timeLabel);
                    });
                }

                // Fetch historical data from database - get last 6 readings with timestamps
                function fetchHistoricalData() {
                    fetch('arduino_sync.php?action=get_historical')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data) {
                                ['temperature', 'humidity', 'soil_moisture'].forEach(type => {
                                    // Reset slots 0-5
                                    for (let i = 0; i < 6; i++) {
                                        sensorData[type].data[i] = null;
                                        sensorData[type].timeLabels[i] = '--';
                                    }
                                    
                                    if (data.data[type] && data.data[type].length > 0) {
                                        const readings = data.data[type];
                                        // Fill slots with data and time labels
                                        for (let i = 0; i < readings.length && i < 6; i++) {
                                            sensorData[type].data[i] = parseFloat(readings[i].value);
                                            sensorData[type].timeLabels[i] = readings[i].time_label || '--';
                                        }
                                    }
                                    // Slot 6 is always "Now"
                                    sensorData[type].timeLabels[6] = 'Now';
                                });
                                
                                const currentSensorType = document.getElementById('sensor-type-select').value;
                                updateChart(currentSensorType);
                            }
                        })
                        .catch(error => console.log('Historical data fetch failed:', error));
                }

                // Initialize chart with real Arduino data
                document.addEventListener('DOMContentLoaded', function() {
                    // Data is already initialized from PHP with current values
                    // Just update the chart
                    updateChart('temperature');

                    // Fetch current live Arduino data first (for "Now" bar)
                    fetchArduinoData();
                    
                    // Then fetch historical data from database
                    fetchHistoricalData();

                    // Add event listener for dropdown
                    document.getElementById('sensor-type-select').addEventListener('change', function() {
                        updateChart(this.value);
                    });

                    // Auto-update based on sensor logging interval from settings
                    const chartRefreshInterval = <?php echo $chartRefreshIntervalMs; ?>; // milliseconds
                    console.log('Chart refresh interval:', chartRefreshInterval, 'ms');
                    setInterval(fetchArduinoData, chartRefreshInterval);
                    
                    // Also refresh historical data periodically (every interval)
                    setInterval(fetchHistoricalData, chartRefreshInterval);
                });
            </script>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4" data-translate="quick_actions">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="sensors.php" class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-center hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors group">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-thermometer-half text-blue-600 dark:text-blue-400 text-lg"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Sensors</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Monitor data</p>
                    </a>
                    <a href="pest_detection.php" class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-center hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-colors group">
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-bug text-yellow-600 dark:text-yellow-400 text-lg"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Pest Detection</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">AI monitoring</p>
                    </a>
                    <a href="reports.php" class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-center hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors group">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-chart-bar text-green-600 dark:text-green-400 text-lg"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Reports</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Generate data</p>
                    </a>
                    <a href="pest_detection.php" class="p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg text-center hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors group">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-camera text-purple-600 dark:text-purple-400 text-lg"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Cameras</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Manage feeds</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-4">
            <!-- Arduino Live Sensors -->
            <?php if ($arduinoHealthy && $arduinoData): ?>
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 text-white rounded-xl p-4" id="arduino-sensors-widget">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-white/80 text-xs font-medium">Arduino Live Sensors</h3>
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                            <span class="text-white/80 text-xs">Live</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <?php if (isset($arduinoData['temperature'])): ?>
                            <div class="flex items-center justify-between p-2 bg-white/10 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-thermometer-half text-red-400 text-sm"></i>
                                    <span class="text-sm">Temperature</span>
                                </div>
                                <span class="font-bold" id="widget-temperature">
                                    <?php echo number_format($arduinoData['temperature']['value'], 1); ?>°C
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($arduinoData['humidity'])): ?>
                            <div class="flex items-center justify-between p-2 bg-white/10 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-tint text-blue-400 text-sm"></i>
                                    <span class="text-sm">Humidity</span>
                                </div>
                                <span class="font-bold" id="widget-humidity">
                                    <?php echo number_format($arduinoData['humidity']['value'], 1); ?>%
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($arduinoData['soil_moisture'])): ?>
                            <div class="flex items-center justify-between p-2 bg-white/10 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-seedling text-green-400 text-sm"></i>
                                    <span class="text-sm">Soil Moisture</span>
                                </div>
                                <span class="font-bold" id="widget-soil">
                                    <?php echo number_format($arduinoData['soil_moisture']['value'], 1); ?>%
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="text-center mt-3 pt-3 border-t border-white/20">
                        <div class="text-white/60 text-xs">
                            Last update: <span id="widget-timestamp">
                                <?php
                                $lastUpdate = '';
                                foreach ($arduinoData as $data) {
                                    if (isset($data['timestamp'])) {
                                        $lastUpdate = $data['timestamp'];
                                        break;
                                    }
                                }
                                echo $lastUpdate ?: 'Unknown';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Current Conditions -->
            <div class="bg-green-600 text-white rounded-xl p-4">
                <h3 class="text-white/80 text-xs font-medium mb-2" data-translate="weekly_average_conditions">Weekly Average Conditions</h3>
                <div class="text-center">
                    <?php 
                    $tempAvg = isset($sensorReadings[0]) && $sensorReadings[0]['avg_value'] > 0 ? $sensorReadings[0]['avg_value'] : null;
                    $humAvg = isset($sensorReadings[1]) && $sensorReadings[1]['avg_value'] > 0 ? $sensorReadings[1]['avg_value'] : null;
                    $soilAvg = isset($sensorReadings[2]) && $sensorReadings[2]['avg_value'] > 0 ? $sensorReadings[2]['avg_value'] : null;
                    ?>
                    <div class="text-2xl font-bold mb-1"><?php echo $tempAvg !== null ? number_format($tempAvg, 1) . '°C' : '--'; ?></div>
                    <div class="text-white/80 text-xs mb-2" data-translate="temperature">Temperature</div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div><span data-translate="humidity">Humidity</span>: <?php echo $humAvg !== null ? number_format($humAvg, 1) . '%' : '--'; ?></div>
                        <div>Soil: <?php echo $soilAvg !== null ? number_format($soilAvg, 1) . '%' : '--'; ?></div>
                    </div>
                </div>
            </div>

            <!-- Pest Detection -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white" data-translate="pest_detection">Pest Detection</h3>
                    <a href="pest_detection.php" class="text-green-600 hover:text-green-700 text-xs font-medium">View All</a>
                </div>

                <?php if (empty($recentAlerts)): ?>
                    <div class="text-center py-4">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-shield-alt text-green-600 dark:text-green-400"></i>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">No threats detected</p>
                        <p class="text-xs text-green-600 font-medium">Farm is secure</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <?php
                        $severityColors = [
                            'critical' => ['bg' => 'red', 'icon' => 'fa-exclamation-triangle'],
                            'high' => ['bg' => 'orange', 'icon' => 'fa-exclamation-circle'],
                            'medium' => ['bg' => 'yellow', 'icon' => 'fa-info-circle'],
                            'low' => ['bg' => 'blue', 'icon' => 'fa-check-circle']
                        ];

                        foreach ($recentAlerts as $alert):
                            $color = $severityColors[$alert['severity']] ?? $severityColors['low'];
                            $detectedTime = date('M j, g:i A', strtotime($alert['detected_at']));
                        ?>
                            <a href="pest_detection.php?alert_id=<?php echo $alert['id']; ?>"
                                class="block p-2 bg-<?php echo $color['bg']; ?>-50 dark:bg-<?php echo $color['bg']; ?>-900/20 border border-<?php echo $color['bg']; ?>-200 dark:border-<?php echo $color['bg']; ?>-800 rounded-lg hover:bg-<?php echo $color['bg']; ?>-100 dark:hover:bg-<?php echo $color['bg']; ?>-900/30 transition-colors">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 bg-<?php echo $color['bg']; ?>-100 dark:bg-<?php echo $color['bg']; ?>-900 rounded flex items-center justify-center flex-shrink-0">
                                        <i class="fas <?php echo $color['icon']; ?> text-<?php echo $color['bg']; ?>-600 dark:text-<?php echo $color['bg']; ?>-400 text-xs"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-gray-900 dark:text-white truncate">
                                            <?php echo htmlspecialchars($alert['pest_type']); ?>
                                        </p>
                                        <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                            <span><?php echo round($alert['confidence_score'], 1); ?>%</span>
                                            <span>•</span>
                                            <span><?php echo $detectedTime; ?></span>
                                        </div>
                                    </div>
                                    <?php if (!$alert['is_read']): ?>
                                        <span class="w-2 h-2 bg-<?php echo $color['bg']; ?>-500 rounded-full flex-shrink-0"></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php
                    // Count by severity
                    $criticalCount = count(array_filter($recentAlerts, fn($a) => $a['severity'] === 'critical'));
                    $highCount = count(array_filter($recentAlerts, fn($a) => $a['severity'] === 'high'));
                    ?>

                    <?php if ($criticalCount > 0 || $highCount > 0): ?>
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between text-xs">
                                <?php if ($criticalCount > 0): ?>
                                    <span class="text-red-600 dark:text-red-400 font-medium">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        <?php echo $criticalCount; ?> Critical
                                    </span>
                                <?php endif; ?>
                                <?php if ($highCount > 0): ?>
                                    <span class="text-orange-600 dark:text-orange-400 font-medium">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <?php echo $highCount; ?> High
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Pest Database Statistics -->
            <?php if ($pestStats && $pestStats['total'] > 0): ?>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-database text-blue-600 mr-2"></i>
                            Pest Database
                        </h3>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <a href="pest_config.php" class="text-blue-600 hover:text-blue-700 text-xs font-medium">Manage</a>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Total Pests</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white"><?php echo $pestStats['total']; ?></span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-red-600 dark:text-red-400">Critical</span>
                                    <span class="font-bold text-red-700 dark:text-red-300"><?php echo $pestStats['critical']; ?></span>
                                </div>
                            </div>
                            <div class="p-2 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-orange-600 dark:text-orange-400">High</span>
                                    <span class="font-bold text-orange-700 dark:text-orange-300"><?php echo $pestStats['high']; ?></span>
                                </div>
                            </div>
                            <div class="p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-yellow-600 dark:text-yellow-400">Medium</span>
                                    <span class="font-bold text-yellow-700 dark:text-yellow-300"><?php echo $pestStats['medium']; ?></span>
                                </div>
                            </div>
                            <div class="p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-green-600 dark:text-green-400">Low</span>
                                    <span class="font-bold text-green-700 dark:text-green-300"><?php echo $pestStats['low']; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-600 dark:text-gray-400">Active Pests</span>
                                <span class="font-medium text-green-600 dark:text-green-400">
                                    <i class="fas fa-check-circle mr-1"></i><?php echo $pestStats['active']; ?>
                                </span>
                            </div>
                        </div>

                        <div class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-xs text-blue-800 dark:text-blue-300">
                                <i class="fas fa-info-circle mr-1"></i>
                                YOLO model can detect all <?php echo $pestStats['total']; ?> pest types with severity levels and treatment recommendations.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Additional Content Row to Fill Space -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3" data-translate="recent_activity">Recent Activity</h3>
            <div class="space-y-2">
                <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="w-6 h-6 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-thermometer-half text-green-600 dark:text-green-400 text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-900 dark:text-white">Temperature sensor updated</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">2 min ago</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-camera text-blue-600 dark:text-blue-400 text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-900 dark:text-white">Camera stream started</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">5 min ago</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="w-6 h-6 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-chart-bar text-purple-600 dark:text-purple-400 text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-900 dark:text-white">Report generated</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">10 min ago</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weather Conditions - Live Data -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold">Weather</h3>
                <span class="text-xs opacity-75">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    Sagay, N.O.
                </span>
            </div>
            <div class="text-center mb-3">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas <?php echo $weatherData['icon']; ?> text-white text-2xl"></i>
                </div>
                <p class="text-3xl font-bold mb-1"><?php echo $weatherData['temperature']; ?>°C</p>
                <p class="text-sm opacity-90"><?php echo $weatherData['description']; ?></p>
                <p class="text-xs opacity-75 mt-1">Feels like <?php echo $weatherData['feels_like']; ?>°C</p>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="text-center p-2 bg-white/10 backdrop-blur-sm rounded-lg">
                    <div class="flex items-center justify-center mb-1">
                        <i class="fas fa-cloud-rain mr-1"></i>
                        <span class="opacity-75">Rain</span>
                    </div>
                    <p class="font-bold"><?php echo $weatherData['rain_chance']; ?>%</p>
                </div>
                <div class="text-center p-2 bg-white/10 backdrop-blur-sm rounded-lg">
                    <div class="flex items-center justify-center mb-1">
                        <i class="fas fa-wind mr-1"></i>
                        <span class="opacity-75">Wind</span>
                    </div>
                    <p class="font-bold"><?php echo $weatherData['wind_speed']; ?> km/h</p>
                </div>
                <div class="text-center p-2 bg-white/10 backdrop-blur-sm rounded-lg">
                    <div class="flex items-center justify-center mb-1">
                        <i class="fas fa-tint mr-1"></i>
                        <span class="opacity-75">Humidity</span>
                    </div>
                    <p class="font-bold"><?php echo $weatherData['humidity']; ?>%</p>
                </div>
                <div class="text-center p-2 bg-white/10 backdrop-blur-sm rounded-lg">
                    <div class="flex items-center justify-center mb-1">
                        <i class="fas fa-eye mr-1"></i>
                        <span class="opacity-75">Visibility</span>
                    </div>
                    <p class="font-bold"><?php echo $weatherData['visibility']; ?> km</p>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-white/20 flex items-center justify-between text-xs opacity-75">
                <span>
                    <i class="fas fa-sunrise mr-1"></i>
                    <?php echo $weatherData['sunrise']; ?>
                </span>
                <span>
                    <i class="fas fa-sunset mr-1"></i>
                    <?php echo $weatherData['sunset']; ?>
                </span>
            </div>
            <div class="mt-2 text-center text-xs opacity-60">
                Updated: <?php echo $weatherData['last_updated']; ?>
            </div>
        </div>

        <!-- Quick Reports -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Quick Reports</h3>
                <a href="reports.php" class="text-green-600 hover:text-green-700 text-xs font-medium">Generate</a>
            </div>
            <div class="space-y-2">
                <button class="w-full text-left p-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-thermometer-half text-red-600 dark:text-red-400 text-xs"></i>
                        <span class="text-xs font-medium text-gray-900 dark:text-white">Temperature Report</span>
                    </div>
                </button>
                <button class="w-full text-left p-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-tint text-blue-600 dark:text-blue-400 text-xs"></i>
                        <span class="text-xs font-medium text-gray-900 dark:text-white">Humidity Report</span>
                    </div>
                </button>
                <button class="w-full text-left p-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-seedling text-green-600 dark:text-green-400 text-xs"></i>
                        <span class="text-xs font-medium text-gray-900 dark:text-white">Soil Report</span>
                    </div>
                </button>
            </div>
        </div>
    </div>


</div>
<!-- Real-time Arduino Data JavaScript -->
<script>
    // Real-time Arduino sensor updates
    function updateArduinoSensors() {
        fetch('arduino_sync.php?action=get_all')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const sensors = data.data;

                    // Update temperature card
                    if (sensors.temperature && sensors.temperature.value !== null) {
                        const tempValue = document.getElementById('temperature-value');
                        if (tempValue) {
                            tempValue.textContent = parseFloat(sensors.temperature.value).toFixed(1) + '°C';
                        }

                        // Update widget
                        const widgetTemp = document.getElementById('widget-temperature');
                        if (widgetTemp) {
                            widgetTemp.textContent = parseFloat(sensors.temperature.value).toFixed(1) + '°C';
                        }

                        // Update chart if temperature is selected
                        const sensorSelect = document.getElementById('sensor-type-select');
                        if (sensorSelect && sensorSelect.value === 'temperature' && typeof sensorData !== 'undefined') {
                            sensorData.temperature.data.push(parseFloat(sensors.temperature.value));
                            sensorData.temperature.data.shift();
                            updateChart('temperature');
                        }
                    }

                    // Update humidity card
                    if (sensors.humidity && sensors.humidity.value !== null) {
                        const humidityValue = document.getElementById('humidity-value');
                        if (humidityValue) {
                            humidityValue.textContent = parseFloat(sensors.humidity.value).toFixed(1) + '%';
                        }

                        // Update widget
                        const widgetHumidity = document.getElementById('widget-humidity');
                        if (widgetHumidity) {
                            widgetHumidity.textContent = parseFloat(sensors.humidity.value).toFixed(1) + '%';
                        }

                        // Update chart if humidity is selected
                        const sensorSelect = document.getElementById('sensor-type-select');
                        if (sensorSelect && sensorSelect.value === 'humidity' && typeof sensorData !== 'undefined') {
                            sensorData.humidity.data.push(parseFloat(sensors.humidity.value));
                            sensorData.humidity.data.shift();
                            updateChart('humidity');
                        }
                    }

                    // Update soil moisture card
                    if (sensors.soil_moisture && sensors.soil_moisture.value !== null) {
                        const soilValue = document.getElementById('soil-value');
                        if (soilValue) {
                            soilValue.textContent = parseFloat(sensors.soil_moisture.value).toFixed(1) + '%';
                        }

                        // Update widget
                        const widgetSoil = document.getElementById('widget-soil');
                        if (widgetSoil) {
                            widgetSoil.textContent = parseFloat(sensors.soil_moisture.value).toFixed(1) + '%';
                        }

                        // Update chart if soil moisture is selected
                        const sensorSelect = document.getElementById('sensor-type-select');
                        if (sensorSelect && sensorSelect.value === 'soil_moisture' && typeof sensorData !== 'undefined') {
                            sensorData.soil_moisture.data.push(parseFloat(sensors.soil_moisture.value));
                            sensorData.soil_moisture.data.shift();
                            updateChart('soil_moisture');
                        }
                    }

                    // Update widget timestamp
                    const widgetTimestamp = document.getElementById('widget-timestamp');
                    if (widgetTimestamp) {
                        let latestTimestamp = '';
                        for (const sensorType in sensors) {
                            if (sensors[sensorType].timestamp) {
                                latestTimestamp = sensors[sensorType].timestamp;
                                break;
                            }
                        }
                        widgetTimestamp.textContent = latestTimestamp || 'Just now';
                    }
                }
            })
            .catch(error => {
                console.log('Arduino sensors update failed:', error);
            });
    }

    // Start real-time updates
    <?php if ($arduinoHealthy): ?>
        // Update every 5 seconds for real-time feel
        setInterval(updateArduinoSensors, 5000);

        // Initial update after 2 seconds
        setTimeout(updateArduinoSensors, 2000);
    <?php endif; ?>
</script>

<?php
// Include shared footer
include 'includes/footer.php';
?>
</main>
</div>
</div>